<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    /**
     * Liste paginée des abonnements, statistiques réelles et plans.
     * Supporte l'export CSV via ?export=csv (mêmes filtres que la liste).
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $plan = $request->input('plan');
        $perPage = (int) $request->input('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $baseQuery = Subscription::with(['user:id,name,email', 'plan'])
            ->when($search, function ($q, $search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('stripe_id', 'like', "%{$search}%")
                        ->orWhere('plan_name', 'like', "%{$search}%")
                        ->orWhere('plan_code', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQ) use ($search) {
                            $userQ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status, fn ($q, $status) => $q->where('status', $status))
            ->when($plan, fn ($q, $plan) => $q->where('plan_code', $plan));

        // Export CSV réel (flux streamé), mêmes filtres que la page
        if ($request->input('export') === 'csv') {
            return $this->streamCsv((clone $baseQuery)->orderBy('created_at', 'desc')->get());
        }

        $subscriptions = (clone $baseQuery)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Billing/Subscriptions', [
            'subscriptions' => $subscriptions,
            'filters' => $request->only(['search', 'status', 'plan', 'per_page']),
            'stats' => $this->buildStats(),
            'plans' => SubscriptionPlan::orderBy('price')->get(),
            'users' => User::select('id', 'name', 'email')->orderBy('name')->get(),
            'selectedSubscription' => $this->resolveSelectedSubscription($request),
        ]);
    }

    /** La création se fait via le dialog "Nouveau Abonnement" sur la liste. */
    public function create()
    {
        return redirect()->route('billing.subscriptions.index');
    }

    /** L'édition se fait via la fiche abonné sur la liste. */
    public function edit(Subscription $subscription)
    {
        return redirect()->route('billing.subscriptions.index', ['selected' => $subscription->id]);
    }

    /** Redirige vers la liste en ouvrant la fiche abonné (?selected=ID). */
    public function show(Subscription $subscription)
    {
        return redirect()->route('billing.subscriptions.index', ['selected' => $subscription->id]);
    }

    /** Création manuelle d'un abonnement (admin, hors tunnel de paiement). */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_code' => 'required|exists:subscription_plans,code',
            'starts_at' => 'nullable|date',
        ]);

        $plan = SubscriptionPlan::where('code', $validated['plan_code'])->firstOrFail();
        $startsAt = !empty($validated['starts_at']) ? Carbon::parse($validated['starts_at']) : now();

        DB::transaction(function () use ($validated, $plan, $startsAt) {
            Subscription::create([
                'user_id' => $validated['user_id'],
                'name' => $plan->code,
                'plan_code' => $plan->code,
                'plan_name' => $plan->name,
                'plan_price' => $plan->price,
                'currency' => $plan->currency,
                'interval' => $plan->interval,
                'quantity' => 1,
                'status' => 'active',
                'stripe_status' => 'active',
                'stripe_id' => 'sub_' . Str::random(24),
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMonths($plan->duration_months),
                // NOTE : création manuelle par un admin. Aucun appel Stripe n'est effectué ;
                // total_paid reflète le prix catalogue du plan.
                'total_paid' => $plan->price,
            ]);
        });

        return redirect()->route('billing.subscriptions.index')
            ->with('success', 'Abonnement créé avec succès.');
    }

    /**
     * Mise à jour d'un abonnement.
     * Accepte un statut seul (résiliation / réactivation) ou des champs d'édition.
     */
    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:active,trialing,past_due,canceled,expired',
            'plan_code' => 'sometimes|exists:subscription_plans,code',
            'quantity' => 'sometimes|integer|min:1',
            'ends_at' => 'nullable|date',
        ]);

        if (array_key_exists('status', $validated) && count($validated) === 1) {
            return $validated['status'] === 'canceled'
                ? $this->applyCancel($subscription)
                : $this->applyResume($subscription, $validated['status']);
        }

        if (!empty($validated['plan_code'])) {
            $plan = SubscriptionPlan::where('code', $validated['plan_code'])->firstOrFail();
            $validated['plan_name'] = $plan->name;
            $validated['plan_price'] = $plan->price;
            $validated['currency'] = $plan->currency;
            $validated['interval'] = $plan->interval;
        }

        $subscription->update($validated);

        return redirect()->route('billing.subscriptions.index')
            ->with('success', 'Abonnement mis à jour.');
    }

    /** Suppression : uniquement les abonnements résiliés (historique admin). */
    public function destroy(Subscription $subscription)
    {
        if ($subscription->status !== 'canceled') {
            return redirect()->back()
                ->with('error', 'Seuls les abonnements résiliés peuvent être supprimés.');
        }

        $subscription->delete();

        return redirect()->back()->with('success', 'Abonnement supprimé.');
    }

    /** Résiliation immédiate : statut canceled + date de fin = maintenant. */
    public function cancel(Subscription $subscription)
    {
        return $this->applyCancel($subscription);
    }

    /** Réactivation d'un abonnement résilié : statut active, date de fin effacée. */
    public function resume(Subscription $subscription)
    {
        return $this->applyResume($subscription, 'active');
    }

    private function applyCancel(Subscription $subscription)
    {
        if ($subscription->status === 'canceled') {
            return redirect()->back()->with('warning', 'Cet abonnement est déjà résilié.');
        }

        $subscription->update([
            'status' => 'canceled',
            'stripe_status' => 'canceled',
            'ends_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Abonnement résilié.');
    }

    private function applyResume(Subscription $subscription, string $status = 'active')
    {
        if ($subscription->status === 'active') {
            return redirect()->back()->with('info', 'Cet abonnement est déjà actif.');
        }

        $subscription->update([
            'status' => $status,
            'stripe_status' => $status,
            'ends_at' => null,
        ]);

        return redirect()->back()->with('success', 'Abonnement réactivé.');
    }

    private function resolveSelectedSubscription(Request $request): ?Subscription
    {
        $id = $request->input('selected');

        if (!$id) {
            return null;
        }

        return Subscription::with(['user:id,name,email', 'plan'])->find($id);
    }

    /**
     * Statistiques calculées depuis la base.
     *
     * MRR : somme des prix mensuels normalisés des abonnements actifs
     *       (interval = year -> prix / 12). Arpu : MRR / nb actifs.
     * Churn : résiliés / total — ratio de vie simple, PAS un taux de churn
     *         mensuel cohorté (nécessite Stripe / historique de statuts pour
     *         être précis). Retourne null quand la base est vide -> "—" côté UI.
     */
    private function buildStats(): array
    {
        $actives = Subscription::where('status', 'active')->get(['plan_price', 'interval']);

        $mrrCents = 0;
        foreach ($actives as $sub) {
            $price = (int) ($sub->plan_price ?? 0);
            $mrrCents += $sub->interval === 'year' ? intdiv($price, 12) : $price;
        }

        $total = Subscription::count();
        $totalActive = $actives->count();
        $totalCanceled = Subscription::where('status', 'canceled')->count();
        $totalExpired = Subscription::where('status', 'expired')->count();
        $totalCollectedCents = (int) Subscription::sum('total_paid');

        $churnRate = $total > 0 ? round($totalCanceled / $total * 100, 1) : null;
        $arpuCents = $totalActive > 0 ? intdiv($mrrCents, $totalActive) : 0;

        return [
            'total' => $total,
            'totalActive' => $totalActive,
            'totalCanceled' => $totalCanceled,
            'totalExpired' => $totalExpired,
            'mrrCents' => $mrrCents,
            'arpuCents' => $arpuCents,
            'totalCollectedCents' => $totalCollectedCents,
            'churnRate' => $churnRate,
            'churnNonDispo' => $churnRate === null,
            'currency' => 'USD',
        ];
    }

    private function streamCsv($subscriptions)
    {
        $fileName = 'abonnements_export_' . now()->format('Y-m-d_H-i') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate',
        ];

        $callback = function () use ($subscriptions) {
            $file = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['ID', 'Utilisateur', 'Email', 'Plan', 'Code plan', 'Intervalle', 'Prix (USD)', 'Statut', 'Total payé (USD)', 'Créé le', 'Fin le'], ';');

            foreach ($subscriptions as $sub) {
                fputcsv($file, [
                    $sub->id,
                    $sub->user->name ?? 'N/A',
                    $sub->user->email ?? 'N/A',
                    $sub->plan_name ?? $sub->plan?->name ?? '—',
                    $sub->plan_code ?? '—',
                    $sub->interval === 'year' ? 'Annuel' : ($sub->interval === 'month' ? 'Mensuel' : ($sub->interval ?? '—')),
                    $sub->plan_price !== null ? number_format($sub->plan_price / 100, 2, '.', '') : '—',
                    $sub->status,
                    number_format(($sub->total_paid ?? 0) / 100, 2, '.', ''),
                    $sub->created_at?->format('Y-m-d H:i'),
                    $sub->ends_at ? $sub->ends_at->format('Y-m-d') : '—',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
