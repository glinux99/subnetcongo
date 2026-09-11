<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Affiliate;
use App\Models\User;
use App\Models\Referral;
use App\Models\Course;
use App\Models\AffiliatePayout;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Exception;
class AffiliateController extends Controller
{
    public function __construct()
    {
        // $this->middleware('can:view-affiliates')->only(['index', 'show', 'export']);
        // $this->middleware('can:manage-affiliates')->except(['index', 'show', 'export']);
    }

    public function index(Request $request)
    {

      try {
          $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $perPage = $request->input('per_page', 15);

        $query = $this->buildAffiliateQuery($request);

        $validSorts = ['created_at', 'status', 'commission_rate', 'clicks', 'conversions', 'total_earnings', 'tier'];
        if (in_array($sortField, $validSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $affiliates = $query->paginate($perPage)->withQueryString();

        // Transform affiliates for frontend
        $affiliates->getCollection()->transform(function ($affiliate) {
            return $this->transformAffiliate($affiliate);
        });

        // Stats for header
        $stats = [
            'total' => Affiliate::count(),
            'active' => Affiliate::where('status', 'active')->count(),
            'pending' => Affiliate::where('status', 'pending')->count(),
            'total_earnings' => Affiliate::sum('total_earnings'),
        ];

        // Tiers config for frontend
        $tiers = [
            ['label' => 'Bronze (10%)', 'value' => 'bronze', 'color' => '#cd7f32', 'rate' => 10, 'minSales' => 0],
            ['label' => 'Silver (15%)', 'value' => 'silver', 'color' => '#c0c0c0', 'rate' => 15, 'minSales' => 10],
            ['label' => 'Gold (25%)', 'value' => 'gold', 'color' => '#ffd700', 'rate' => 25, 'minSales' => 50],
            ['label' => 'Platinum (40%)', 'value' => 'platinum', 'color' => '#e5e4e2', 'rate' => 40, 'minSales' => 100],
        ];

        $statuses = ['active', 'pending', 'inactive', 'banned'];

        return Inertia::render('Marketing/Affiliates', [
            'affiliates' => $affiliates,
            'filters' => $request->all(['search', 'status', 'tier', 'sort_field', 'sort_direction', 'per_page']),
            'stats' => $stats,
            'tiers' => $tiers,
            'statuses' => $statuses,
        ]);
      }
      catch(Exception $e){
        return $e->getMessage();
      }
    }

    public function create()
    {
        $users = User::whereDoesntHave('affiliate')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $tiers = [
            ['label' => 'Bronze (10%)', 'value' => 'bronze', 'color' => '#cd7f32', 'rate' => 10, 'minSales' => 0],
            ['label' => 'Silver (15%)', 'value' => 'silver', 'color' => '#c0c0c0', 'rate' => 15, 'minSales' => 10],
            ['label' => 'Gold (25%)', 'value' => 'gold', 'color' => '#ffd700', 'rate' => 25, 'minSales' => 50],
            ['label' => 'Platinum (40%)', 'value' => 'platinum', 'color' => '#e5e4e2', 'rate' => 40, 'minSales' => 100],
        ];

        return Inertia::render('Marketing/Affiliates', [
            'users' => $users,
            'tiers' => $tiers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|unique:affiliates,user_id',
            'referral_code' => 'nullable|string|max:50|unique:affiliates,referral_code',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:active,pending,inactive',
            'payment_email' => 'required|email',
            'tier' => 'required|in:bronze,silver,gold,platinum',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $user = User::findOrFail($validated['user_id']);

            $affiliateCode = $validated['referral_code'] ?: $this->generateUniqueCode($user->name);

            $affiliate = Affiliate::create([
                'user_id' => $user->id,
                'referral_code' => $affiliateCode,
                'commission_rate' => $validated['commission_rate'],
                'status' => $validated['status'],
                'payment_email' => $validated['payment_email'],
                'tier' => $validated['tier'],
                'notes' => $validated['notes'] ?? null,
                'clicks' => 0,
                'conversions' => 0,
                'total_earnings' => 0,
                'unpaid_earnings' => 0,
            ]);

            DB::commit();

            return Redirect::route('marketing.affiliates.index')
                ->with('success', 'Affilié créé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()
                ->with('error', 'Erreur lors de la création de l\'affilié: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Affiliate $affiliate)
    {
        $affiliate->load(['user', 'referrals.course', 'referrals.user', 'payouts']);

        $transformed = $this->transformAffiliate($affiliate);

        // Performance chart data from real referrals
        $performanceChart = $this->getPerformanceChartData($affiliate);

        return Inertia::render('Marketing/Affiliates', [
            'affiliate' => $transformed,
            'performance_chart' => $performanceChart,
        ]);
    }

    public function edit(Affiliate $affiliate)
    {
        $affiliate->load('user');

        $tiers = [
            ['label' => 'Bronze (10%)', 'value' => 'bronze', 'color' => '#cd7f32', 'rate' => 10, 'minSales' => 0],
            ['label' => 'Silver (15%)', 'value' => 'silver', 'color' => '#c0c0c0', 'rate' => 15, 'minSales' => 10],
            ['label' => 'Gold (25%)', 'value' => 'gold', 'color' => '#ffd700', 'rate' => 25, 'minSales' => 50],
            ['label' => 'Platinum (40%)', 'value' => 'platinum', 'color' => '#e5e4e2', 'rate' => 40, 'minSales' => 100],
        ];

        return Inertia::render('Marketing/Affiliates', [
            'affiliate' => $this->transformAffiliate($affiliate),
            'tiers' => $tiers,
        ]);
    }

    public function update(Request $request, Affiliate $affiliate)
    {
        $validated = $request->validate([
            'referral_code' => ['nullable', 'string', 'max:50', Rule::unique('affiliates')->ignore($affiliate->id)],
            'commission_rate' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:active,pending,inactive,banned',
            'payment_email' => 'required|email',
            'tier' => 'required|in:bronze,silver,gold,platinum',
            'notes' => 'nullable|string',
        ]);

        try {
            $affiliate->update($validated);

            return Redirect::route('marketing.affiliates.index')
                ->with('success', 'Affilié mis à jour.');

        } catch (\Exception $e) {
            return Redirect::back()
                ->with('error', 'Erreur de mise à jour: ' . $e->getMessage());
        }
    }

    public function destroy(Affiliate $affiliate)
    {
        try {
            $affiliate->update(['status' => 'inactive']);

            return Redirect::route('marketing.affiliates.index')
                ->with('success', 'Affilié désactivé.');
        } catch (\Exception $e) {
            return Redirect::back()
                ->with('error', 'Impossible de désactiver l\'affilié.');
        }
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:affiliates,id']);

        $count = Affiliate::whereIn('id', $request->ids)->count();
        Affiliate::whereIn('id', $request->ids)->update(['status' => 'inactive']);

        return Redirect::back()->with('success', "$count affiliés ont été désactivés.");
    }

    public function approve(Affiliate $affiliate)
    {
        if ($affiliate->status !== 'pending') {
            return Redirect::back()->with('warning', 'Cet affilié n\'est pas en attente d\'approbation.');
        }

        $affiliate->update(['status' => 'active']);

        return Redirect::back()->with('success', 'Affilié approuvé et activé.');
    }

    public function reject(Request $request, Affiliate $affiliate)
    {
        $request->validate(['reason' => 'nullable|string|max:1000']);

        if ($affiliate->status !== 'pending') {
            return Redirect::back()->with('warning', 'Cet affilié n\'est pas en attente.');
        }

        $reason = $request->input('reason', 'Votre demande ne correspond pas à nos critères actuels.');

        $affiliate->delete();

        return Redirect::route('marketing.affiliates.index')
            ->with('success', 'Demande d\'affiliation rejetée et supprimée.');
    }

    public function export(Request $request)
    {
        $fileName = 'affiliates_export_' . date('Y-m-d') . '.csv';

        $affiliates = $this->buildAffiliateQuery($request)->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Nom', 'Email', 'Code', 'Taux Commission (%)', 'Statut', 'Clics', 'Conversions', 'Gains Totaux', 'Solde Actuel', 'Inscrit le'];

        $callback = function() use($affiliates, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($affiliates as $aff) {
                $row['ID'] = $aff->id;
                $row['Nom'] = $aff->user->name;
                $row['Email'] = $aff->user->email;
                $row['Code'] = $aff->referral_code;
                $row['Taux Commission (%)'] = $aff->commission_rate;
                $row['Statut'] = $aff->status;
                $row['Clics'] = $aff->clicks;
                $row['Conversions'] = $aff->conversions;
                $row['Gains Totaux'] = $aff->total_earnings;
                $row['Solde Actuel'] = $aff->balance;
                $row['Inscrit le'] = $aff->created_at->format('Y-m-d');

                fputcsv($file, array_values($row));
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function stats()
    {
        $topAffiliates = Affiliate::with('user:id,name')
            ->orderBy('total_earnings', 'desc')
            ->take(5)
            ->get(['id', 'user_id', 'total_earnings']);

        $recentSignups = Affiliate::with('user:id,name')
            ->latest()
            ->take(5)
            ->get(['id', 'user_id', 'created_at']);

        $data = [
            'total_affiliates' => Affiliate::count(),
            'pending_applications' => Affiliate::where('status', 'pending')->count(),
            'total_revenue_generated' => Referral::where('status', 'approved')->sum('amount'),
            'total_commission_paid' => AffiliatePayout::where('status', 'completed')->sum('amount'),
            'top_affiliates' => $topAffiliates,
            'recent_signups' => $recentSignups,
        ];

        return response()->json($data);
    }

    public function payouts(Affiliate $affiliate)
    {
        $affiliate->load('user');

        $payouts = AffiliatePayout::where('affiliate_id', $affiliate->id)
            ->latest()
            ->paginate(10)
            ->through(function ($payout) {
                return [
                    'id' => $payout->id,
                    'amount' => $payout->amount,
                    'status' => $payout->status,
                    'payout_date' => $payout->payout_date?->format('Y-m-d'),
                    'method' => $payout->method,
                    'transaction_id' => $payout->transaction_id,
                    'notes' => $payout->notes,
                    'created_at' => $payout->created_at->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('Marketing/Affiliates', [
            'affiliate' => $this->transformAffiliate($affiliate),
            'payouts' => $payouts,
            'payout_methods' => ['PayPal', 'Virement Bancaire', 'Orange Money', 'M-Pesa', 'Airtel Money'],
        ]);
    }

    public function generatePayout(Request $request)
    {
        $request->validate([
            'affiliate_ids' => 'required|array',
            'affiliate_ids.*' => 'exists:affiliates,id',
            'payout_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $affiliates = Affiliate::whereIn('id', $request->affiliate_ids)
            ->where('unpaid_earnings', '>', 0)
            ->get();

        if ($affiliates->isEmpty()) {
            return Redirect::back()->with('warning', 'Aucun affilié sélectionné n\'a de solde à payer.');
        }

        DB::beginTransaction();
        try {
            foreach ($affiliates as $affiliate) {
                $amount = $affiliate->unpaid_earnings;

                AffiliatePayout::create([
                    'affiliate_id' => $affiliate->id,
                    'amount' => $amount,
                    'status' => 'processing',
                    'payout_date' => $request->payout_date,
                    'notes' => $request->notes,
                ]);

                $affiliate->update([
                    'unpaid_earnings' => 0,
                ]);
            }
            DB::commit();

            return Redirect::back()->with('success', 'Paiements générés pour ' . $affiliates->count() . ' affilié(s).');

        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur lors de la génération des paiements: ' . $e->getMessage());
        }
    }

    private function generateUniqueCode(string $name): string
    {
        $baseCode = Str::slug($name, '');
        $code = $baseCode;
        $counter = 1;

        while (Affiliate::where('referral_code', $code)->exists()) {
            $code = $baseCode . $counter;
            $counter++;
        }

        return strtoupper($code);
    }

    private function buildAffiliateQuery(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $tier = $request->input('tier');

        return Affiliate::with(['user:id,name,email,profile_photo'])
            ->when($search, function ($q, $search) {
                $q->where('referral_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQ) use ($search) {
                      $userQ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                  });
            })
            ->when($status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($tier, function ($q, $tier) {
                $q->where('tier', $tier);
            });
    }

    private function transformAffiliate($affiliate)
    {
        $referrals = $affiliate->referrals()->with('course:id,title')->get();

        $approvedReferrals = $referrals->where('status', 'approved');
        $totalRevenue = $approvedReferrals->sum('amount');
        $totalCommission = $approvedReferrals->sum('commission');
        $pendingReferrals = $referrals->where('status', 'pending');
        $pendingCommission = $pendingReferrals->sum('commission');

        $lastPayout = $affiliate->payouts()->where('status', 'completed')->latest()->first();

        // Recent sales (last 3 approved referrals)
        $recentSales = $approvedReferrals->take(3)->map(function ($ref) {
            return [
                'id' => $ref->id,
                'course' => $ref->course->title ?? 'Cours supprimé',
                'date' => $ref->converted_at?->format('Y-m-d') ?? $ref->created_at->format('Y-m-d'),
                'amount' => $ref->amount,
                'commission' => $ref->commission,
            ];
        })->values()->toArray();

        return [
            'id' => $affiliate->id,
            'user' => [
                'name' => $affiliate->user->name,
                'email' => $affiliate->user->email,
                'avatar' => $affiliate->user->profile_photo_url ?? "https://i.pravatar.cc/150?u=" . $affiliate->user->id,
                'joined_at' => $affiliate->created_at->format('Y-m-d'),
            ],
            'tier' => $affiliate->tier,
            'stats' => [
                'clicks' => $affiliate->clicks,
                'conversions' => $affiliate->conversions,
                'conversion_rate' => $affiliate->clicks > 0 ? round(($affiliate->conversions / $affiliate->clicks) * 100, 2) : 0,
                'total_revenue' => $totalRevenue,
                'commission_earned' => $totalCommission,
            ],
            'balance' => [
                'pending' => $pendingCommission,
                'withdrawable' => $affiliate->unpaid_earnings,
                'last_payout' => $lastPayout ? $lastPayout->payout_date?->format('d/m/Y') : 'Aucun',
            ],
            'status' => ucfirst($affiliate->status),
            'recent_sales' => $recentSales,
        ];
    }

    private function getPerformanceChartData($affiliate)
    {
        $months = 6;
        $labels = [];
        $commissions = [];
        $newAffiliates = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labels[] = $date->format('M');

            $monthCommissions = $affiliate->referrals()
                ->where('status', 'approved')
                ->whereMonth('converted_at', $date->month)
                ->whereYear('converted_at', $date->year)
                ->sum('commission');
            $commissions[] = $monthCommissions;

            // For demo, we'll use 0 for new affiliates since we don't track referral tree
            $newAffiliates[] = 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Commissions Générées ($)',
                    'data' => $commissions,
                    'fill' => false,
                    'borderColor' => '#F97316',
                    'tension' => 0.4,
                    'pointBackgroundColor' => '#F97316'
                ],
                [
                    'label' => 'Nouveaux Affiliés',
                    'data' => $newAffiliates,
                    'fill' => true,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'borderColor' => '#6366F1',
                    'tension' => 0.4
                ]
            ]
        ];
    }
}
