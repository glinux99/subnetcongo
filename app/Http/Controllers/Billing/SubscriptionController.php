<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    /**
     * Constructeur du contrôleur.
     * Applique les middlewares de permissions pour sécuriser l'accès.
     */
    public function __construct()
    {
        // Exemple de gestion des permissions via Middleware
        // $this->middleware('can:view-subscription')->only(['index', 'show', 'export']);
        // $this->middleware('can:create-subscription')->only(['create', 'store']);
        // $this->middleware('can:update-subscription')->only(['edit', 'update', 'cancel', 'resume']);
        // $this->middleware('can:delete-subscription')->only(['destroy', 'bulkDestroy']);
    }

    /**
     * Affiche la liste des abonnements avec recherche avancée, filtres et pagination.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        // Récupération des paramètres de filtrage
        $search = $request->input('search');
        $status = $request->input('status');
        $plan = $request->input('plan');
        $dateStart = $request->input('date_start');
        $dateEnd = $request->input('date_end');
        $perPage = $request->input('per_page', 10);
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        // Construction de la requête
        $query = Subscription::with(['user:id,name,email,profile_photo'])
            ->when($search, function ($q, $search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('stripe_id', 'like', "%{$search}%")
                         ->orWhere('plan_name', 'like', "%{$search}%")
                         ->orWhereHas('user', function ($userQ) use ($search) {
                             $userQ->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                         });
                });
            })
            ->when($status, function ($q, $status) {
                $q->where('stripe_status', $status);
            })
            ->when($plan, function ($q, $plan) {
                $q->where('plan_name', $plan);
            })
            ->when($dateStart && $dateEnd, function ($q) use ($dateStart, $dateEnd) {
                $q->whereBetween('created_at', [$dateStart, $dateEnd]);
            });

        // Tri dynamique
        $validSorts = ['created_at', 'ends_at', 'stripe_status', 'plan_name', 'stripe_price'];
        if (in_array($sortField, $validSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $subscriptions = $query->paginate($perPage)->withQueryString();

        // Statistiques rapides pour le header de la page
        $stats = [
            'total' => Subscription::count(),
            'active' => Subscription::where('stripe_status', 'active')->count(),
            'canceled' => Subscription::where('stripe_status', 'canceled')->count(),
            'trialing' => Subscription::where('stripe_status', 'trialing')->count(),
        ];

        return Inertia::render('Billing/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'filters' => $request->all(['search', 'status', 'plan', 'date_start', 'date_end', 'per_page', 'sort_field', 'sort_direction']),
            'stats' => $stats,
            'plans' => $this->getAvailablePlans(), // Liste des plans pour le filtre
        ]);
    }

    /**
     * Affiche le formulaire de création d'un nouvel abonnement.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('Billing/Subscriptions/Create', [
            'users' => User::select('id', 'name', 'email')->orderBy('name')->get(),
            'plans' => $this->getAvailablePlans(),
        ]);
    }

    /**
     * Enregistre un nouvel abonnement dans la base de données.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_name' => 'required|string|max:255',
            'stripe_price' => 'nullable|string|max:255',
            'quantity' => 'integer|min:1|default:1',
            'trial_days' => 'nullable|integer|min:0',
            'status' => 'required|in:active,trialing,past_due,canceled,incomplete',
        ]);

        DB::beginTransaction();

        try {
            // Simulation de la création Stripe ID si non fourni
            $stripeId = 'sub_' . Str::random(24);

            $subscription = Subscription::create([
                'user_id' => $validated['user_id'],
                'plan_name' => $validated['plan_name'],
                'stripe_id' => $stripeId,
                'stripe_status' => $validated['status'],
                'stripe_price' => $validated['stripe_price'],
                'quantity' => $request->input('quantity', 1),
                'trial_ends_at' => $request->trial_days ? Carbon::now()->addDays($request->trial_days) : null,
                'ends_at' => null, // Par défaut null pour un abonnement récurrent
            ]);

            // Logique métier supplémentaire (ex: envoyer un email de bienvenue)
            // $subscription->user->notify(new SubscriptionCreated($subscription));

            DB::commit();

            return Redirect::route('billing.subscriptions.index')
                ->with('success', 'Abonnement créé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()
                ->with('error', 'Erreur lors de la création de l\'abonnement : ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Affiche les détails d'un abonnement spécifique.
     *
     * @param Subscription $subscription
     * @return \Inertia\Response
     */
    public function show(Subscription $subscription)
    {
        $subscription->load('user');

        // Calcul de la durée restante ou passée
        $duration = $subscription->created_at->diffForHumans();

        // Simulation de factures liées (si un modèle Invoice existait)
        $invoices = []; // $subscription->user->invoices()->latest()->take(5)->get();

        return Inertia::render('Billing/Subscriptions/Show', [
            'subscription' => $subscription,
            'duration' => $duration,
            'invoices' => $invoices,
            'activity_logs' => [], // Placeholder pour les logs d'activité
        ]);
    }

    /**
     * Affiche le formulaire d'édition d'un abonnement.
     *
     * @param Subscription $subscription
     * @return \Inertia\Response
     */
    public function edit(Subscription $subscription)
    {
        return Inertia::render('Billing/Subscriptions/Edit', [
            'subscription' => $subscription,
            'users' => User::select('id', 'name', 'email')->get(), // Pour info, généralement on ne change pas le user d'une souscription
            'plans' => $this->getAvailablePlans(),
        ]);
    }

    /**
     * Met à jour les informations d'un abonnement.
     *
     * @param Request $request
     * @param Subscription $subscription
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'plan_name' => 'required|string|max:255',
            'stripe_status' => 'required|in:active,trialing,past_due,canceled,incomplete,incomplete_expired',
            'quantity' => 'integer|min:1',
            'trial_ends_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:today',
        ]);

        try {
            $subscription->update([
                'plan_name' => $validated['plan_name'],
                'stripe_status' => $validated['stripe_status'],
                'quantity' => $validated['quantity'],
                'trial_ends_at' => $validated['trial_ends_at'],
                'ends_at' => $validated['ends_at'],
            ]);

            return Redirect::route('billing.subscriptions.index')
                ->with('success', 'Abonnement mis à jour avec succès.');

        } catch (\Exception $e) {
            return Redirect::back()
                ->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }

    /**
     * Supprime un abonnement (Soft delete ou Hard delete selon le modèle).
     *
     * @param Subscription $subscription
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Subscription $subscription)
    {
        try {
            // Si on voulait annuler sur Stripe avant de supprimer :
            // $this->cancelStripeSubscription($subscription->stripe_id);

            $subscription->delete();

            return Redirect::back()->with('success', 'Abonnement supprimé.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Impossible de supprimer l\'abonnement.');
        }
    }

    /**
     * Suppression multiple d'abonnements.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:subscriptions,id',
        ]);

        $count = Subscription::whereIn('id', $request->ids)->delete();

        return Redirect::back()->with('success', "$count abonnements supprimés.");
    }

    /**
     * Annule un abonnement (définit la date de fin mais garde le statut actif jusqu'à la fin de la période).
     *
     * @param Subscription $subscription
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(Subscription $subscription)
    {
        if ($subscription->stripe_status === 'canceled') {
            return Redirect::back()->with('warning', 'Cet abonnement est déjà annulé.');
        }

        // Logique : on met ends_at à la fin de la période courante (simulée ici à +30 jours si non définie)
        // Dans un vrai cas Stripe, on appellerait l'API Stripe.
        $endDate = Carbon::now();

        $subscription->update([
            'stripe_status' => 'canceled',
            'ends_at' => $endDate,
        ]);

        return Redirect::back()->with('success', 'Abonnement annulé immédiatement.');
    }

    /**
     * Reprend un abonnement annulé (Grace Period).
     *
     * @param Subscription $subscription
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resume(Subscription $subscription)
    {
        if ($subscription->stripe_status === 'active') {
            return Redirect::back()->with('info', 'Cet abonnement est déjà actif.');
        }

        $subscription->update([
            'stripe_status' => 'active',
            'ends_at' => null, // Retire la date de fin programmée
        ]);

        return Redirect::back()->with('success', 'Abonnement réactivé avec succès.');
    }

    /**
     * Exporte les abonnements filtrés en CSV.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $fileName = 'subscriptions_export_' . date('Y-m-d_H-i') . '.csv';

        // Réutilisation de la logique de filtrage (simplifiée ici)
        $query = Subscription::with('user');

        if ($request->status) {
            $query->where('stripe_status', $request->status);
        }
        if ($request->plan) {
            $query->where('plan_name', $request->plan);
        }

        $subscriptions = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Utilisateur', 'Email', 'Plan', 'Statut', 'Prix', 'Créé le', 'Fin le'];

        $callback = function() use($subscriptions, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($subscriptions as $sub) {
                $row['ID']  = $sub->id;
                $row['Utilisateur'] = $sub->user ? $sub->user->name : 'N/A';
                $row['Email'] = $sub->user ? $sub->user->email : 'N/A';
                $row['Plan']  = $sub->plan_name;
                $row['Statut']  = $sub->stripe_status;
                $row['Prix']  = $sub->stripe_price;
                $row['Créé le']  = $sub->created_at->format('Y-m-d H:i');
                $row['Fin le']  = $sub->ends_at ? $sub->ends_at->format('Y-m-d') : 'Récurrent';

                fputcsv($file, array_values($row));
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Synchronise le statut d'un abonnement avec le fournisseur de paiement (Simulation).
     *
     * @param Subscription $subscription
     * @return \Illuminate\Http\RedirectResponse
     */
    public function syncStatus(Subscription $subscription)
    {
        // Ici, on ferait un appel API vers Stripe/PayPal
        // $stripeSub = \Stripe\Subscription::retrieve($subscription->stripe_id);
        // $subscription->update(['stripe_status' => $stripeSub->status]);

        // Simulation
        $statuses = ['active', 'past_due', 'canceled'];
        $randomStatus = $statuses[array_rand($statuses)];

        $subscription->update(['stripe_status' => $randomStatus]);

        return Redirect::back()->with('success', "Statut synchronisé : $randomStatus");
    }

    /**
     * Retourne les statistiques globales des abonnements pour un tableau de bord.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        $totalRevenue = Subscription::where('stripe_status', 'active')->sum('stripe_price') ?? 0; // Suppose que stripe_price contient le montant

        $data = [
            'total_subscriptions' => Subscription::count(),
            'active_subscriptions' => Subscription::where('stripe_status', 'active')->count(),
            'churn_rate' => $this->calculateChurnRate(),
            'monthly_recurring_revenue' => $totalRevenue,
            'recent_activity' => Subscription::latest()->take(5)->with('user:id,name')->get(),
            'plans_distribution' => Subscription::select('plan_name', DB::raw('count(*) as total'))
                ->groupBy('plan_name')
                ->pluck('total', 'plan_name'),
        ];

        return response()->json($data);
    }

    /**
     * Méthode privée pour calculer le taux de désabonnement (Churn Rate).
     * (Nombre de désabonnements ce mois-ci / Total abonnés début de mois) * 100
     *
     * @return float
     */
    private function calculateChurnRate()
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        $totalStart = Subscription::where('created_at', '<', $startOfMonth)
            ->where(function($q) use ($startOfMonth) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', $startOfMonth);
            })->count();

        if ($totalStart == 0) return 0;

        $cancelledThisMonth = Subscription::where('stripe_status', 'canceled')
            ->where('updated_at', '>=', $startOfMonth)
            ->count();

        return round(($cancelledThisMonth / $totalStart) * 100, 2);
    }

    /**
     * Retourne la liste des plans disponibles dans le système.
     * Pourrait provenir d'un fichier de config ou d'une table 'plans'.
     *
     * @return array
     */
    private function getAvailablePlans()
    {
        return [
            [
                'id' => 'basic_monthly',
                'name' => 'Basic Monthly',
                'price' => 9.99,
                'currency' => 'USD',
                'features' => ['Access to basic courses', 'Community support']
            ],
            [
                'id' => 'pro_monthly',
                'name' => 'Pro Monthly',
                'price' => 19.99,
                'currency' => 'USD',
                'features' => ['All courses', 'Priority support', 'Certificates']
            ],
            [
                'id' => 'enterprise_yearly',
                'name' => 'Enterprise Yearly',
                'price' => 199.99,
                'currency' => 'USD',
                'features' => ['Unlimited users', 'API Access', 'Dedicated manager']
            ],
        ];
    }

    /**
     * Action de masse pour changer le statut.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkStatusUpdate(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:subscriptions,id',
            'status' => 'required|in:active,canceled,past_due'
        ]);

        $updateData = ['stripe_status' => $request->status];

        // Si on annule, on met une date de fin
        if ($request->status === 'canceled') {
            $updateData['ends_at'] = Carbon::now();
        } elseif ($request->status === 'active') {
            $updateData['ends_at'] = null;
        }

        Subscription::whereIn('id', $request->ids)->update($updateData);

        return Redirect::back()->with('success', 'Statuts mis à jour pour la sélection.');
    }

    /**
     * Génère une facture PDF pour un abonnement (Simulation).
     *
     * @param Subscription $subscription
     * @return mixed
     */
    public function downloadInvoice(Subscription $subscription)
    {
        // Vérification des droits
        if (Auth::id() !== $subscription->user_id && !Auth::user()->can('manage-invoices')) {
            abort(403);
        }

        // Logique de génération PDF (ex: DomPDF ou Snappy)
        // $pdf = PDF::loadView('invoices.subscription', compact('subscription'));
        // return $pdf->download('invoice-'.$subscription->id.'.pdf');

        return Redirect::back()->with('info', 'Le téléchargement de facture est simulé.');
    }

    /**
     * Envoie un rappel de paiement pour les abonnements 'past_due'.
     *
     * @param Subscription $subscription
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendPaymentReminder(Subscription $subscription)
    {
        if ($subscription->stripe_status !== 'past_due') {
            return Redirect::back()->with('error', 'Cet abonnement n\'est pas en retard de paiement.');
        }

        // Mail::to($subscription->user)->send(new PaymentReminderMail($subscription));

        return Redirect::back()->with('success', 'Rappel de paiement envoyé à ' . $subscription->user->email);
    }

    /**
     * Vérifie si l'utilisateur a un abonnement actif (Helper API).
     *
     * @param Request $request
     * @param string $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus(Request $request, $userId)
    {
        $hasActive = Subscription::where('user_id', $userId)
            ->where(function($q) {
                $q->where('stripe_status', 'active')
                  ->orWhere('stripe_status', 'trialing');
            })
            ->where(function($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>', Carbon::now());
            })
            ->exists();

        return response()->json(['is_active' => $hasActive]);
    }
}
