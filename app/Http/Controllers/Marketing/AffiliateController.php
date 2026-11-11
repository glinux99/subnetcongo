<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliatePayout;
use App\Models\Referral;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AffiliateController extends Controller
{
    private const STATUSES = ['active', 'pending', 'inactive', 'banned'];
    private const TIERS = ['bronze', 'silver', 'gold', 'platinum'];
    private const REFERRAL_SUCCESS_STATUSES = ['approved', 'paid'];

    public function index(Request $request)
    {
        return Inertia::render('Marketing/Affiliates', $this->indexProps($request));
    }

    public function create(Request $request)
    {
        return Redirect::route('marketing.affiliates.index');
    }

    public function edit(Request $request, Affiliate $affiliate)
    {
        return Redirect::route('marketing.affiliates.index');
    }

    public function show(Request $request, Affiliate $affiliate)
    {
        $affiliate->loadMissing('user:id,name,email');

        $latestReferrals = $affiliate->referrals()
            ->with(['course:id,title', 'user:id,name'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Referral $referral) => [
                'id' => $referral->id,
                'course' => $referral->course?->title ?? 'Cours supprimé',
                'buyer' => $referral->user?->name ?? '—',
                'amount' => (float) $referral->amount,
                'commission' => (float) $referral->commission,
                'status' => $referral->status,
                'date' => ($referral->converted_at ?? $referral->created_at)?->format('Y-m-d'),
            ])
            ->values();

        $latestPayouts = $affiliate->payouts()
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (AffiliatePayout $payout) => [
                'id' => $payout->id,
                'amount' => (float) $payout->amount,
                'status' => $payout->status,
                'method' => $payout->method,
                'payout_date' => $payout->payout_date?->format('Y-m-d'),
                'created_at' => $payout->created_at?->format('Y-m-d'),
            ])
            ->values();

        $detail = array_merge($this->transformAffiliateRow($affiliate), [
            'referrals' => $latestReferrals,
            'payouts' => $latestPayouts,
            'payment_email' => $affiliate->payment_email,
            'notes' => $affiliate->notes,
        ]);

        return Inertia::render('Marketing/Affiliates', array_merge(
            $this->indexProps($request),
            ['affiliateDetail' => $detail]
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:250', 'regex:/^[^@\s]+@[^@\s]+\.[^@\s]{2,}$/'],
            'tier' => ['required', Rule::in(self::TIERS)],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $email = mb_strtolower(trim($validated['email']));
        $name = trim($validated['name']);

        try {
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'status' => 'actif']
            );

            if (Affiliate::where('user_id', $user->id)->exists()) {
                return Redirect::back()
                    ->with('error', 'Cet utilisateur est déjà inscrit au programme d’affiliation.')
                    ->withInput();
            }

            DB::transaction(function () use ($user, $email, $validated, $name) {
                Affiliate::create([
                    'user_id' => $user->id,
                    'referral_code' => $this->generateUniqueCode($name),
                    'commission_rate' => $validated['commission_rate'],
                    'status' => 'pending',
                    'payment_email' => $email,
                    'tier' => $validated['tier'],
                    'notes' => $validated['notes'] ?? null,
                    'clicks' => 0,
                    'conversions' => 0,
                    'total_earnings' => 0,
                    'unpaid_earnings' => 0,
                ]);
            });
        } catch (\Throwable $e) {
            report($e);

            return Redirect::back()
                ->with('error', 'Impossible de créer l’affilié pour le moment.')
                ->withInput();
        }

        return Redirect::route('marketing.affiliates.index')
            ->with('success', 'Affilié créé pour ' . $name . '. Sa demande est en attente d’approbation.');
    }

    public function update(Request $request, Affiliate $affiliate)
    {
        $validated = $request->validate([
            'referral_code' => ['nullable', 'string', 'max:50', Rule::unique('affiliates')->ignore($affiliate->id)],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'payment_email' => ['required', 'email:rfc', 'max:250'],
            'tier' => ['required', Rule::in(self::TIERS)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $affiliate->update($validated);
        } catch (\Throwable $e) {
            report($e);

            return Redirect::back()->with('error', 'Impossible de mettre à jour l’affilié.');
        }

        return Redirect::route('marketing.affiliates.index')->with('success', 'Affilié mis à jour.');
    }

    public function destroy(Affiliate $affiliate)
    {
        try {
            $affiliate->update(['status' => 'inactive']);
        } catch (\Throwable $e) {
            report($e);

            return Redirect::back()->with('error', 'Impossible de désactiver l’affilié.');
        }

        return Redirect::route('marketing.affiliates.index')->with('success', 'Affilié désactivé. L’historique est conservé.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:affiliates,id'],
        ]);

        $count = Affiliate::whereIn('id', $request->ids)->update(['status' => 'inactive']);

        return Redirect::back()->with('success', $count . ' affilié(s) désactivé(s).');
    }

    public function approve(Affiliate $affiliate)
    {
        if ($affiliate->status !== 'pending') {
            return Redirect::back()->with('error', 'Seuls les affiliés en attente peuvent être approuvés.');
        }

        $affiliate->update(['status' => 'active']);

        return Redirect::back()->with('success', 'Affilié approuvé et activé.');
    }

    public function reject(Request $request, Affiliate $affiliate)
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);

        if ($affiliate->status !== 'pending') {
            return Redirect::back()->with('error', 'Seules les demandes en attente peuvent être refusées.');
        }

        $affiliate->delete();

        return Redirect::route('marketing.affiliates.index')->with('success', 'Demande d’affiliation refusée et supprimée.');
    }

    public function generatePayout(Request $request, Affiliate $affiliate)
    {
        $validated = $request->validate([
            'payout_date' => ['nullable', 'date'],
            'method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $affiliate->loadMissing('user:id,name');

        if ((float) $affiliate->unpaid_earnings <= 0) {
            return Redirect::back()->with('error', 'Cet affilié n’a pas de commission à payer.');
        }

        $amount = (float) $affiliate->unpaid_earnings;

        try {
            DB::transaction(function () use ($affiliate, $validated, $amount) {
                AffiliatePayout::create([
                    'affiliate_id' => $affiliate->id,
                    'amount' => $amount,
                    'status' => 'pending',
                    'payout_date' => $validated['payout_date'] ?? now()->toDateString(),
                    'method' => $validated['method'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'processed_by' => auth()->id(),
                ]);

                $affiliate->update(['unpaid_earnings' => 0]);
            });
        } catch (\Throwable $e) {
            report($e);

            return Redirect::back()->with('error', 'Impossible de générer le paiement pour le moment.');
        }

        return Redirect::back()->with(
            'success',
            'Paiement de ' . number_format($amount, 2, ',', ' ') . ' $ généré pour ' . ($affiliate->user?->name ?? 'l’affilié') . ' (en attente de traitement).'
        );
    }

    public function export(Request $request)
    {
        $filters = $this->validatedFilters($request);

        $rows = $this->buildAffiliateQuery($filters)->get();
        $fileName = 'affiliates_' . now()->format('Y-m-d') . '.csv';

        $columns = [
            'Code',
            'Nom',
            'Email',
            'Statut',
            'Tier',
            'Clics',
            'Conversions',
            'Commission (%)',
            'Gains totaux ($)',
            'À payer ($)',
        ];

        $callback = function () use ($rows, $columns) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $columns, ';');

            foreach ($rows as $affiliate) {
                fputcsv($out, array_map([$this, 'csvSafe'], [
                    $affiliate->referral_code,
                    $affiliate->user?->name ?? '',
                    $affiliate->user?->email ?? '',
                    $affiliate->status,
                    $affiliate->tier,
                    $affiliate->clicks,
                    $affiliate->conversions,
                    $affiliate->commission_rate,
                    $affiliate->total_earnings,
                    $affiliate->unpaid_earnings,
                ]), ';');
            }

            fclose($out);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
            'tier' => ['nullable', Rule::in(self::TIERS)],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    private function indexProps(Request $request): array
    {
        $filters = $this->validatedFilters($request);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $perPage = $perPage >= 5 && $perPage <= 100 ? $perPage : 10;

        $affiliates = $this->buildAffiliateQuery($filters)
            ->paginate($perPage)
            ->withQueryString();

        $affiliates->getCollection()->transform(fn (Affiliate $affiliate) => $this->transformAffiliateRow($affiliate));

        $stats = [
            'total' => Affiliate::count(),
            'active' => Affiliate::where('status', 'active')->count(),
            'pending' => Affiliate::where('status', 'pending')->count(),
            'inactive' => Affiliate::where('status', 'inactive')->count(),
            'banned' => Affiliate::where('status', 'banned')->count(),
            'total_clicks' => (int) Affiliate::sum('clicks'),
            'total_conversions' => (int) Affiliate::sum('conversions'),
            'unpaid_earnings' => (float) Affiliate::sum('unpaid_earnings'),
            'total_earnings' => (float) Affiliate::sum('total_earnings'),
            'total_paid' => (float) AffiliatePayout::where('status', 'completed')->sum('amount'),
        ];

        $topAffiliates = Affiliate::with('user:id,name')
            ->orderByDesc('total_earnings')
            ->take(5)
            ->get(['id', 'user_id', 'referral_code', 'tier', 'status', 'total_earnings', 'unpaid_earnings'])
            ->map(fn (Affiliate $a) => [
                'id' => $a->id,
                'name' => $a->user?->name ?? '—',
                'code' => $a->referral_code,
                'tier' => $a->tier,
                'status' => $a->status,
                'total_earnings' => (float) $a->total_earnings,
                'unpaid_earnings' => (float) $a->unpaid_earnings,
            ])
            ->values();

        return [
            'affiliates' => $affiliates,
            'stats' => $stats,
            'chart' => $this->monthlyProgramStats(),
            'top_affiliates' => $topAffiliates,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? null,
                'tier' => $filters['tier'] ?? null,
                'per_page' => $perPage,
            ],
            'tiers' => $this->tiers(),
            'statuses' => self::STATUSES,
        ];
    }

    private function buildAffiliateQuery(array $filters)
    {
        return Affiliate::with(['user:id,name,email'])
            ->withCount('referrals')
            ->withSum(['referrals as referrals_amount_sum' => fn ($q) => $q->whereIn('status', self::REFERRAL_SUCCESS_STATUSES)], 'amount')
            ->withSum(['referrals as referrals_commission_sum' => fn ($q) => $q->whereIn('status', self::REFERRAL_SUCCESS_STATUSES)], 'commission')
            ->withSum(['payouts as payouts_completed_sum' => fn ($q) => $q->where('status', 'completed')], 'amount')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('referral_code', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['tier'] ?? null, fn ($query, $tier) => $query->where('tier', $tier))
            ->latest('created_at');
    }

    private function transformAffiliateRow(Affiliate $affiliate): array
    {
        $clicks = (int) $affiliate->clicks;
        $conversions = (int) $affiliate->conversions;

        return [
            'id' => $affiliate->id,
            'referral_code' => $affiliate->referral_code,
            'name' => $affiliate->user?->name ?? '—',
            'email' => $affiliate->user?->email ?? '—',
            'tier' => $affiliate->tier,
            'status' => $affiliate->status,
            'commission_rate' => (float) $affiliate->commission_rate,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'conversion_rate' => $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : 0,
            'total_earnings' => (float) $affiliate->total_earnings,
            'unpaid_earnings' => (float) $affiliate->unpaid_earnings,
            'referrals_count' => (int) ($affiliate->referrals_count ?? 0),
            'referrals_amount' => (float) ($affiliate->referrals_amount_sum ?? 0),
            'referrals_commission' => (float) ($affiliate->referrals_commission_sum ?? 0),
            'payouts_paid' => (float) ($affiliate->payouts_completed_sum ?? 0),
            'joined_at' => $affiliate->created_at?->format('Y-m-d'),
        ];
    }

    private function tiers(): array
    {
        return [
            ['label' => 'Bronze (10%)', 'value' => 'bronze', 'color' => '#cd7f32', 'rate' => 10],
            ['label' => 'Silver (15%)', 'value' => 'silver', 'color' => '#c0c0c0', 'rate' => 15],
            ['label' => 'Gold (25%)', 'value' => 'gold', 'color' => '#ffd700', 'rate' => 25],
            ['label' => 'Platinum (40%)', 'value' => 'platinum', 'color' => '#e5e4e2', 'rate' => 40],
        ];
    }

    private function monthlyProgramStats(): array
    {
        $monthLabels = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
        $labels = [];
        $signups = [];
        $conversions = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labels[] = ucfirst($monthLabels[$date->month - 1]) . ' ' . $date->format('y');
            $signups[] = Affiliate::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $conversions[] = Referral::whereIn('status', self::REFERRAL_SUCCESS_STATUSES)
                ->whereYear('converted_at', $date->year)
                ->whereMonth('converted_at', $date->month)
                ->count();
        }

        return [
            'labels' => $labels,
            'signups' => $signups,
            'conversions' => $conversions,
        ];
    }

    private function generateUniqueCode(string $name): string
    {
        $baseCode = Str::slug($name, '');
        $code = $baseCode !== '' ? $baseCode : 'affiliate';
        $counter = 1;

        while (Affiliate::where('referral_code', strtoupper($code))->exists()) {
            $code = $baseCode . $counter;
            $counter++;
        }

        return strtoupper($code);
    }

    private function csvSafe($value): string
    {
        $value = (string) $value;

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
