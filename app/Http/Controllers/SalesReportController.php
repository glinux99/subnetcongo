<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\Builder;

class SalesReportController extends Controller
{
    /**
     * Durée de vie du cache en secondes (1 heure).
     */
    private const CACHE_TTL = 3600;

    /**
     * Constantes pour les types de rapports disponibles.
     */
    private const REPORT_SALES_OVER_TIME = 'sales_over_time';
    private const REPORT_SALES_BY_COURSE = 'sales_by_course';
    private const REPORT_TOP_STUDENTS = 'top_students';
    private const REPORT_SALES_BY_REGION = 'sales_by_region';
    private const REPORT_SALES_BY_INSTRUCTOR = 'sales_by_instructor';
    private const REPORT_SALES_BY_CATEGORY = 'sales_by_category';
    private const REPORT_SALES_BY_PAYMENT_METHOD = 'sales_by_payment_method';
    private const REPORT_REFUNDS = 'refunds';
    private const REPORT_ABANDONED_CARTS = 'abandoned_carts';
    private const REPORT_TAXES = 'taxes';
    private const REPORT_DISCOUNTS = 'discounts';

    /**
     * Constructeur : Application des middlewares de sécurité.
     */
    public function __construct()
    {
        // Décommentez ces lignes en production pour sécuriser la route
        // $this->middleware('auth');
        // $this->middleware('can:view-sales-reports');
    }

    /**
     * Affiche le tableau de bord principal des rapports de ventes.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        try {
            $filters = $this->getFilters($request);

            // Génération des données initiales pour la vue avec mise en cache
            $cacheKeySummary = $this->generateCacheKey('dashboard_summary', $filters);
            $summary = Cache::remember($cacheKeySummary, self::CACHE_TTL, function () use ($filters) {
                return $this->getSalesSummary($filters);
            });

            $cacheKeyOverTime = $this->generateCacheKey('dashboard_over_time', $filters);
            $salesOverTime = Cache::remember($cacheKeyOverTime, self::CACHE_TTL, function () use ($filters) {
                return $this->getSalesOverTime($filters['start_date'], $filters['end_date'], $filters);
            });

            return Inertia::render('SalesReports',[
                'filters' => $filters,
                'summary' => $summary,
                'salesOverTime' => $salesOverTime,
                'filterOptions' => $this->getFilterOptions(),
                'availableReports' => $this->getAvailableReportsList(),
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du chargement du dashboard des rapports : ' . $e->getMessage(),[
                'trace' => $e->getTraceAsString(),
            ]);
            dd($e);
            // return Inertia::render('Errors/Generic',[
            //     'message' => 'Impossible de charger le tableau de bord des ventes.',
            // ]);
        }
    }

    /**
     * Génère un rapport spécifique via un appel API (AJAX).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),[
            'report_type' => 'required|string|in:' . implode(',', array_keys($this->getAvailableReportsList())),
        ]);

        if ($validator->fails()) {
            return $this->apiError('Type de rapport invalide.', 422, $validator->errors()->toArray());
        }

        try {
            $filters = $this->getFilters($request);
            $reportType = $request->input('report_type');

            $cacheKey = $this->generateCacheKey("api_report_{$reportType}", $filters);

            // Vérifier si l'utilisateur demande à vider le cache
            if ($request->boolean('force_refresh')) {
                Cache::forget($cacheKey);
            }

            $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($reportType, $filters) {
                return $this->fetchReportData($reportType, $filters['start_date'], $filters['end_date'], $filters);
            });

            return $this->apiSuccess('Rapport généré avec succès.',[
                'report_type' => $reportType,
                'filters' => $filters,
                'data' => $data,
                'generated_at' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la génération du rapport API ($reportType) : " . $e->getMessage(),[
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return $this->apiError('Une erreur inattendue est survenue lors de la génération du rapport.', 500);
        }
    }

    /**
     * Exporte un rapport au format CSV ou PDF.
     *
     * @param Request $request
     * @return StreamedResponse|\Illuminate\Http\Response|JsonResponse
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'report_type' => 'required|string|in:' . implode(',', array_keys($this->getAvailableReportsList())),
            'format' => 'required|string|in:csv,pdf',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Paramètres d\'exportation invalides.'], 422);
        }

        try {
            $filters = $this->getFilters($request);
            $reportType = $request->input('report_type');
            $format = $request->input('format');

            // Récupération des données (sans cache pour l'export ou avec cache très court)
            $data = $this->fetchReportData($reportType, $filters['start_date'], $filters['end_date'], $filters);

            $reportMeta = $this->getReportMetadata($reportType);
            $columns = $reportMeta['columns'];
            $title = $reportMeta['title'];

            if ($format === 'pdf') {
                return $this->exportPdf($data, $columns, $title, $filters);
            }

            return $this->exportCsv($data, $columns, $title);

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'exportation ($format) du rapport ($reportType) : " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Échec de l\'exportation du document.'], 500);
        }
    }

    /**
     * Vide le cache manuellement pour tous les rapports ou un spécifique.
     *
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        try {
            // Note : En production, il est préférable d'utiliser des tags de cache
            // Cache::tags(['sales_reports'])->flush();
            // À défaut, on vide la base complète si pertinent ou on retourne un message.
            Cache::flush(); // Attention : vide tout le cache de l'application.

            return $this->apiSuccess('Le cache des rapports a été vidé avec succès.');
        } catch (\Exception $e) {
            Log::error('Erreur lors du nettoyage du cache : ' . $e->getMessage());
            return $this->apiError('Impossible de vider le cache.', 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Méthodes privées d'agrégation et de calcul
    |--------------------------------------------------------------------------
    */

    /**
     * Dirige la requête vers la bonne méthode en fonction du type de rapport.
     *
     * @param string $reportType
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $filters
     * @return array
     */
    private function fetchReportData(string $reportType, Carbon $startDate, Carbon $endDate, array $filters): array
    {
        return match ($reportType) {
            self::REPORT_SALES_OVER_TIME => $this->getSalesOverTime($startDate, $endDate, $filters),
            self::REPORT_SALES_BY_COURSE => $this->getSalesByCourse($startDate, $endDate, $filters),
            self::REPORT_TOP_STUDENTS => $this->getTopStudents($startDate, $endDate, $filters),
            self::REPORT_SALES_BY_REGION => $this->getSalesByRegion($startDate, $endDate, $filters),
            self::REPORT_SALES_BY_INSTRUCTOR => $this->getSalesByInstructor($startDate, $endDate, $filters),
            self::REPORT_SALES_BY_CATEGORY => $this->getSalesByCategory($startDate, $endDate, $filters),
            self::REPORT_SALES_BY_PAYMENT_METHOD => $this->getSalesByPaymentMethod($startDate, $endDate, $filters),
            self::REPORT_REFUNDS => $this->getRefundsReport($startDate, $endDate, $filters),
            self::REPORT_ABANDONED_CARTS => $this->getAbandonedCartsReport($startDate, $endDate, $filters),
            self::REPORT_TAXES => $this->getTaxesReport($startDate, $endDate, $filters),
            self::REPORT_DISCOUNTS => $this->getDiscountsUsageReport($startDate, $endDate, $filters),
            default =>[],
        };
    }

    /**
     * Analyse et valide les filtres communs depuis la requête.
     *
     * @param Request $request
     * @return array
     */
    private function getFilters(Request $request): array
    {
        $end = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : now()->endOfDay();
        $start = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : now()->subDays(30)->startOfDay();

        // Sécurité : éviter que start_date soit après end_date
        if ($start->greaterThan($end)) {
            $start = clone $end;
            $start->subDays(30);
        }

        return[
            'start_date' => $start,
            'end_date' => $end,
            'course_id' => $request->input('course_id'),
            'category_id' => $request->input('category_id'),
            'instructor_id' => $request->input('instructor_id'),
            'region_id' => $request->input('region_id'),
            'payment_method' => $request->input('payment_method'),
            'status' => $request->input('status', 'paid'), // paid, refunded, failed, pending
            'group_by' => $request->input('group_by', 'day'), // hour, day, week, month, year
            'min_amount' => $request->input('min_amount'),
            'max_amount' => $request->input('max_amount'),
            'limit' => (int) $request->input('limit', 50),
        ];
    }

    /**
     * Requête de base pour les commandes respectant tous les filtres actifs.
     * Centralise la logique de filtrage pour éviter la duplication.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $filters
     * @return Builder
     */
    private function getBaseOrderQuery(Carbon $startDate, Carbon $endDate, array $filters =[]): Builder
    {
        $query = Order::query()
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Filtrage par statut de commande
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->whereIn('status',['paid', 'completed']);
        }

        // Filtrage par méthode de paiement
        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        // Filtrage par montant minimum
        if (isset($filters['min_amount']) && is_numeric($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }

        // Filtrage par montant maximum
        if (isset($filters['max_amount']) && is_numeric($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }

        // Filtrage via les relations (utilisation de whereHas pour les pivots / relations)
        if (!empty($filters['course_id'])) {
            // Suppose une relation `items()` dans le modèle Order pointant vers OrderItem
            $query->whereHas('items', function ($q) use ($filters) {
                $q->where('course_id', $filters['course_id']);
            });
        }

        if (!empty($filters['instructor_id'])) {
            $query->whereHas('items.course', function ($q) use ($filters) {
                $q->where('instructor_id', $filters['instructor_id']);
            });
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('items.course', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        if (!empty($filters['region_id'])) {
            // Suppose une relation `user()` dans Order
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('region_id', $filters['region_id']);
            });
        }

        return $query;
    }

    /**
     * Récupère le résumé global des ventes, avec comparaison par rapport à la période précédente.
     *
     * @param array $filters
     * @return array
     */
    private function getSalesSummary(array $filters): array
    {
        $currentStart = clone $filters['start_date'];
        $currentEnd = clone $filters['end_date'];

        // Calcul de la période précédente pour la comparaison (Growth)
        $diffInDays = $currentStart->diffInDays($currentEnd);
        $prevStart = (clone $currentStart)->subDays($diffInDays + 1)->startOfDay();
        $prevEnd = (clone $currentStart)->subDay()->endOfDay();

        $currentMetrics = $this->calculateMetrics($currentStart, $currentEnd, $filters);
        $prevMetrics = $this->calculateMetrics($prevStart, $prevEnd, $filters);

        return [
            'revenue' =>[
                'value' => $currentMetrics['revenue'],
                'formatted' => $this->formatMoney($currentMetrics['revenue']),
                'growth' => $this->calculateGrowth($currentMetrics['revenue'], $prevMetrics['revenue']),
            ],
            'orders_count' => [
                'value' => $currentMetrics['orders'],
                'formatted' => number_format($currentMetrics['orders'], 0, ',', ' '),
                'growth' => $this->calculateGrowth($currentMetrics['orders'], $prevMetrics['orders']),
            ],
            'avg_order_value' => [
                'value' => $currentMetrics['avg_order_value'],
                'formatted' => $this->formatMoney($currentMetrics['avg_order_value']),
                'growth' => $this->calculateGrowth($currentMetrics['avg_order_value'], $prevMetrics['avg_order_value']),
            ],
            'courses_sold' => [
                'value' => $currentMetrics['courses_sold'],
                'formatted' => number_format($currentMetrics['courses_sold'], 0, ',', ' '),
                'growth' => $this->calculateGrowth($currentMetrics['courses_sold'], $prevMetrics['courses_sold']),
            ],
            'refunds_amount' =>[
                'value' => $currentMetrics['refunds'],
                'formatted' => $this->formatMoney($currentMetrics['refunds']),
                'growth' => $this->calculateGrowth($currentMetrics['refunds'], $prevMetrics['refunds'], true), // inversé : plus de remboursements = négatif
            ],
        ];
    }

    /**
     * Calcule les métriques brutes pour une période donnée.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param array $filters
     * @return array
     */
    private function calculateMetrics(Carbon $start, Carbon $end, array $filters): array
    {
        $query = $this->getBaseOrderQuery($start, $end, $filters);

        // Aggrégation rapide via la BDD
        $metrics = $query->select(
            DB::raw('COUNT(id) as total_orders'),
            DB::raw('SUM(amount) as total_revenue')
        )->first();

        $totalOrders = (int) ($metrics->total_orders ?? 0);
        $totalRevenue = (float) ($metrics->total_revenue ?? 0);

        // Récupérer le total des cours vendus (optimisé avec une sous-requête)
        // Suppose une table `order_items` liée aux commandes.
        $totalCoursesSold = DB::table('order_items')
            ->whereIn('order_id', $this->getBaseOrderQuery($start, $end, $filters)->select('id'))
            ->sum('quantity');

        // Calcul des remboursements (en forçant le filtre de statut)
        $refundsFilters = array_merge($filters, ['status' => 'refunded']);
        $totalRefunds = $this->getBaseOrderQuery($start, $end, $refundsFilters)->sum('amount');

        return[
            'orders' => $totalOrders,
            'revenue' => $totalRevenue,
            'avg_order_value' => $totalOrders > 0 ? ($totalRevenue / $totalOrders) : 0,
            'courses_sold' => (int) $totalCoursesSold,
            'refunds' => (float) $totalRefunds,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Méthodes d'extraction de données spécifiques (Les Rapports)
    |--------------------------------------------------------------------------
    */

    /**
     * Ventes agrégées sur l'axe du temps (jour, semaine, mois).
     */
    private function getSalesOverTime(Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        $groupBy = $filters['group_by'] ?? 'day';

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $dateFormat = match($groupBy) {
                'year' => '%Y',
                'month' => '%Y-%m',
                'week' => '%Y-%W',
                'hour' => '%Y-%m-%d %H:00:00',
                default => '%Y-%m-%d',
            };
            $dateSelect = "strftime('{$dateFormat}', created_at)";
        } else {
            $dateFormat = match($groupBy) {
                'year' => '%Y',
                'month' => '%Y-%m',
                'week' => '%x-%v', // Semaine ISO
                'hour' => '%Y-%m-%d %H:00:00',
                default => '%Y-%m-%d',
            };
            $dateSelect = "DATE_FORMAT(created_at, '{$dateFormat}')";
        }

        return $this->getBaseOrderQuery($startDate, $endDate, $filters)
            ->select(
                DB::raw("{$dateSelect} as date"),
                DB::raw('COUNT(DISTINCT id) as sales_count'),
                DB::raw('SUM(amount) as total_revenue'),
                DB::raw('SUM(tax_amount) as total_tax'),
                DB::raw('SUM(discount_amount) as total_discount')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($item) {
                return[
                    'date' => $item->date,
                    'sales_count' => (int) $item->sales_count,
                    'total_revenue' => round((float) $item->total_revenue, 2),
                    'total_tax' => round((float) $item->total_tax, 2),
                    'total_discount' => round((float) $item->total_discount, 2),
                    'net_revenue' => round((float) ($item->total_revenue - $item->total_tax), 2),
                ];
            })->all();
    }

    /**
     * Répartition des ventes par cours.
     */
    private function getSalesByCourse(Carbon $startDate, Carbon $endDate, array $filters =[]): array
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('courses', 'order_items.course_id', '=', 'courses.id')
            ->where('orders.status', $filters['status'] ?? 'paid')
            ->whereBetween('orders.created_at',[$startDate, $endDate])
            ->select(
                'courses.title as course_name',
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
                DB::raw('SUM(order_items.price * order_items.quantity) as revenue')
            )
            ->groupBy('courses.id', 'courses.title')
            ->orderBy('revenue', 'desc')
            ->limit($filters['limit'] ?? 100)
            ->get()
            ->map(fn ($item) =>[
                'course' => $item->course_name,
                'sales' => (int) $item->quantity_sold,
                'revenue' => round((float) $item->revenue, 2)
            ])
            ->all();
    }

    /**
     * Répartition des ventes par Instructeur.
     */
    private function getSalesByInstructor(Carbon $startDate, Carbon $endDate, array $filters =[]): array
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('courses', 'order_items.course_id', '=', 'courses.id')
            ->join('users as instructors', 'courses.instructor_id', '=', 'instructors.id')
            ->where('orders.status', $filters['status'] ?? 'paid')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select(
                'instructors.name as instructor_name',
                DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                DB::raw('SUM(order_items.quantity) as courses_sold'),
                DB::raw('SUM(order_items.price * order_items.quantity) as revenue')
            )
            ->groupBy('instructors.id', 'instructors.name')
            ->orderBy('revenue', 'desc')
            ->get()
            ->map(fn ($item) =>[
                'instructor' => $item->instructor_name,
                'orders' => (int) $item->total_orders,
                'courses_sold' => (int) $item->courses_sold,
                'revenue' => round((float) $item->revenue, 2)
            ])
            ->all();
    }

    /**
     * Répartition des ventes par Catégorie de cours.
     */
    private function getSalesByCategory(Carbon $startDate, Carbon $endDate, array $filters =[]): array
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('courses', 'order_items.course_id', '=', 'courses.id')
            ->join('categories', 'courses.category_id', '=', 'categories.id')
            ->where('orders.status', $filters['status'] ?? 'paid')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select(
                'categories.name as category_name',
                DB::raw('SUM(order_items.quantity) as courses_sold'),
                DB::raw('SUM(order_items.price * order_items.quantity) as revenue')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('revenue', 'desc')
            ->get()
            ->map(fn ($item) =>[
                'category' => $item->category_name,
                'sales' => (int) $item->courses_sold,
                'revenue' => round((float) $item->revenue, 2)
            ])
            ->all();
    }

    /**
     * Top des étudiants (meilleurs acheteurs).
     */
    private function getTopStudents(Carbon $startDate, Carbon $endDate, array $filters =[]): array
    {
        return $this->getBaseOrderQuery($startDate, $endDate, $filters)
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select(
                'users.name as student_name',
                'users.email as student_email',
                DB::raw('COUNT(orders.id) as orders_count'),
                DB::raw('SUM(orders.amount) as total_spent')
            )
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy('total_spent', 'desc')
            ->limit($filters['limit'] ?? 50)
            ->get()
            ->map(fn ($item) =>[
                'student' => $item->student_name,
                'email' => $item->student_email,
                'orders' => (int) $item->orders_count,
                'spent' => round((float) $item->total_spent, 2)
            ])
            ->all();
    }

    /**
     * Ventes groupées par région géographique.
     */
    private function getSalesByRegion(Carbon $startDate, Carbon $endDate, array $filters =[]): array
    {
        return $this->getBaseOrderQuery($startDate, $endDate, $filters)
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->join('regions', 'users.region_id', '=', 'regions.id')
            ->select(
                'regions.designation as region_name',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('SUM(orders.amount) as revenue')
            )
            ->groupBy('regions.id', 'regions.designation')
            ->orderBy('revenue', 'desc')
            ->get()
            ->map(fn ($item) =>[
                'region' => $item->region_name,
                'orders' => (int) $item->total_orders,
                'revenue' => round((float) $item->revenue, 2)
            ])
            ->all();
    }

    /**
     * Ventes ventilées par méthode de paiement (Stripe, PayPal, Virement, etc.).
     */
    private function getSalesByPaymentMethod(Carbon $startDate, Carbon $endDate, array $filters =[]): array
    {
        return $this->getBaseOrderQuery($startDate, $endDate, $filters)
            ->select(
                'payment_method',
                DB::raw('COUNT(id) as usage_count'),
                DB::raw('SUM(amount) as total_volume')
            )
            ->whereNotNull('payment_method')
            ->groupBy('payment_method')
            ->orderBy('total_volume', 'desc')
            ->get()
            ->map(fn ($item) =>[
                'method' => Str::title(str_replace('_', ' ', $item->payment_method)),
                'transactions' => (int) $item->usage_count,
                'volume' => round((float) $item->total_volume, 2)
            ])
            ->all();
    }

    /**
     * Rapport détaillé sur les remboursements.
     */
    private function getRefundsReport(Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        $refundFilters = array_merge($filters,['status' => 'refunded']);

        return $this->getBaseOrderQuery($startDate, $endDate, $refundFilters)
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select(
                'orders.id as order_ref',
                'users.email as customer_email',
                'orders.amount as refunded_amount',
                'orders.updated_at as refunded_at'
            )
            ->orderBy('orders.updated_at', 'desc')
            ->limit($filters['limit'] ?? 100)
            ->get()
            ->map(fn ($item) =>[
                'reference' => '#' . $item->order_ref,
                'customer' => $item->customer_email,
                'amount' => round((float) $item->refunded_amount, 2),
                'date' => Carbon::parse($item->refunded_at)->format('d/m/Y H:i')
            ])
            ->all();
    }

    /**
     * Rapport sur les paniers abandonnés (Statut pending ou failed).
     */
    private function getAbandonedCartsReport(Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        $abandonedFilters = array_merge($filters,['status' => 'pending']); // ou 'failed'

        return $this->getBaseOrderQuery($startDate, $endDate, $abandonedFilters)
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select(
                'users.name as customer_name',
                'users.email as customer_email',
                'orders.amount as cart_value',
                'orders.created_at as abandoned_at'
            )
            ->orderBy('orders.created_at', 'desc')
            ->limit($filters['limit'] ?? 100)
            ->get()
            ->map(fn ($item) =>[
                'customer' => $item->customer_name,
                'email' => $item->customer_email,
                'value' => round((float) $item->cart_value, 2),
                'date' => Carbon::parse($item->abandoned_at)->format('d/m/Y H:i')
            ])
            ->all();
    }

    /**
     * Rapport des taxes collectées.
     */
    private function getTaxesReport(Carbon $startDate, Carbon $endDate, array $filters =[]): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $dateSelect = "strftime('%Y-%m', created_at)";
        } else {
            $dateSelect = "DATE_FORMAT(created_at, '%Y-%m')";
        }

        // Agrégation simple par mois pour les taxes
        return $this->getBaseOrderQuery($startDate, $endDate, $filters)
            ->select(
                DB::raw("{$dateSelect} as month"),
                DB::raw('SUM(amount) as gross_revenue'),
                DB::raw('SUM(tax_amount) as total_tax')
            )
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->map(fn ($item) =>[
                'month' => Carbon::createFromFormat('Y-m', $item->month)->translatedFormat('F Y'),
                'gross_revenue' => round((float) $item->gross_revenue, 2),
                'taxes_collected' => round((float) $item->total_tax, 2)
            ])
            ->all();
    }

    /**
     * Rapport sur l'utilisation des remises / codes promo.
     */
    private function getDiscountsUsageReport(Carbon $startDate, Carbon $endDate, array $filters =[]): array
    {
        return $this->getBaseOrderQuery($startDate, $endDate, $filters)
            ->where('discount_amount', '>', 0)
            ->select(
                DB::raw('COUNT(id) as times_used'),
                DB::raw('SUM(discount_amount) as total_discount_given'),
                DB::raw('SUM(amount) as revenue_generated')
            )
            // Si vous avez un coupon_code dans la table Order, ajoutez un groupBy('coupon_code')
            ->get()
            ->map(fn ($item) =>[
                'usage_count' => (int) $item->times_used,
                'discount_given' => round((float) $item->total_discount_given, 2),
                'revenue_generated' => round((float) $item->revenue_generated, 2)
            ])
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Méthodes d'Exportation (CSV & PDF)
    |--------------------------------------------------------------------------
    */

    /**
     * Génère un fichier CSV en flux continu (Stream) pour optimiser la mémoire.
     */
    private function exportCsv(array $data, array $columns, string $title): StreamedResponse
    {
        $fileName = sprintf('%s-%s.csv', Str::slug($title), now()->format('Y-m-d_H-i'));

        $headers =[
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"$fileName\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($data, $columns) {
            $file = fopen('php://output', 'w');

            // Ajout du BOM UTF-8 pour que MS Excel lise correctement les accents
            fputs($file, "\xEF\xBB\xBF");

            // En France/Europe, Excel préfère souvent le point-virgule
            fputcsv($file, $columns, ';');

            foreach ($data as $row) {
                // Conversion des objets éventuels en tableaux associatifs
                $rowArray = is_object($row) ? (array) $row : $row;
                fputcsv($file, array_values($rowArray), ';');
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Génère et télécharge un document PDF.
     */
    private function exportPdf(array $data, array $columns, string $title, array $filters)
    {
        // On suppose que la vue 'reports.sales_pdf' existe
        $pdf = Pdf::loadView('reports.sales_pdf',[
            'data' => $data,
            'columns' => array_combine(array_keys($data[0] ??[]), $columns), // Mapping clé => Nom Colonne
            'title' => $title,
            'filters' => $filters,
            'generated_at' => now()->format('d/m/Y à H:i:s'),
            'author' => auth()->check() ? auth()->user()->name : 'Système Automatisé'
        ]);

        // Optionnel : Format paysage pour les tableaux longs
        if (count($columns) > 4) {
            $pdf->setPaper('A4', 'landscape');
        } else {
            $pdf->setPaper('A4', 'portrait');
        }

        $fileName = sprintf('%s-%s.pdf', Str::slug($title), now()->format('Y-m-d_H-i'));
        return $pdf->download($fileName);
    }

    /**
     * Fournit les métadonnées (titre, colonnes) selon le type de rapport.
     */
    private function getReportMetadata(string $reportType): array
    {
        return match ($reportType) {
            self::REPORT_SALES_OVER_TIME =>[
                'title' => "Rapport des Ventes par Période",
                'columns' =>['Date', 'Nb Ventes', 'Revenu Brut (€)', 'Taxes (€)', 'Remises (€)', 'Revenu Net (€)']
            ],
            self::REPORT_SALES_BY_COURSE =>[
                'title' => "Rapport des Ventes par Cours",
                'columns' =>['Nom du Cours', 'Quantité Vendue', 'Revenu (€)']
            ],
            self::REPORT_TOP_STUDENTS =>[
                'title' => "Rapport des Meilleurs Étudiants",
                'columns' =>['Étudiant', 'Email', 'Nb Commandes', 'Total Dépensé (€)']
            ],
            self::REPORT_SALES_BY_REGION =>[
                'title' => "Rapport des Ventes par Région",
                'columns' => ['Région', 'Nb Commandes', 'Revenu (€)']
            ],
            self::REPORT_SALES_BY_INSTRUCTOR =>[
                'title' => "Rapport des Ventes par Instructeur",
                'columns' =>['Instructeur', 'Nb Commandes', 'Cours Vendus', 'Revenu (€)']
            ],
            self::REPORT_SALES_BY_CATEGORY =>[
                'title' => "Rapport des Ventes par Catégorie",
                'columns' => ['Catégorie', 'Cours Vendus', 'Revenu (€)']
            ],
            self::REPORT_SALES_BY_PAYMENT_METHOD =>[
                'title' => "Ventes par Méthode de Paiement",
                'columns' => ['Méthode', 'Nb Transactions', 'Volume (€)']
            ],
            self::REPORT_REFUNDS =>[
                'title' => "Historique des Remboursements",
                'columns' => ['Référence', 'Client', 'Montant (€)', 'Date']
            ],
            self::REPORT_ABANDONED_CARTS =>[
                'title' => "Paniers Abandonnés",
                'columns' => ['Client', 'Email', 'Valeur (€)', 'Date']
            ],
            self::REPORT_TAXES =>[
                'title' => "Rapport des Taxes Collectées",
                'columns' =>['Mois', 'Revenu Brut (€)', 'Taxes Collectées (€)']
            ],
            self::REPORT_DISCOUNTS =>[
                'title' => "Utilisation des Codes Promo",
                'columns' =>['Nb Utilisations', 'Total Remisé (€)', 'Revenu Généré (€)']
            ],
            default =>[
                'title' => "Rapport Générique",
                'columns' => ['Colonne 1', 'Colonne 2']
            ],
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers et Utilitaires
    |--------------------------------------------------------------------------
    */

    /**
     * Formate un nombre en monnaie (Euros).
     */
    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' €';
    }

    /**
     * Calcule le pourcentage de croissance entre deux valeurs.
     *
     * @param float|int $current
     * @param float|int $previous
     * @param bool $invert Évalue une baisse comme positive (utile pour les remboursements).
     * @return float
     */
    private function calculateGrowth($current, $previous, bool $invert = false): float
    {
        if ($previous == 0) {
            return $current > 0 ? ($invert ? -100.0 : 100.0) : 0.0;
        }

        $growth = (($current - $previous) / $previous) * 100;
        return round($invert ? -$growth : $growth, 1);
    }

    /**
     * Génère une clé de cache unique basée sur les filtres.
     */
    private function generateCacheKey(string $prefix, array $filters): string
    {
        // On sérialise et on hashe les filtres pour créer une clé courte et unique
        $hash = md5(json_encode([
            $filters['start_date']->timestamp ?? null,
            $filters['end_date']->timestamp ?? null,
            $filters['course_id'] ?? null,
            $filters['instructor_id'] ?? null,
            $filters['category_id'] ?? null,
            $filters['region_id'] ?? null,
            $filters['payment_method'] ?? null,
            $filters['status'] ?? null,
            $filters['group_by'] ?? null,
        ]));

        return "sales_reports_{$prefix}_{$hash}";
    }

    /**
     * Renvoie une réponse API structurée en cas de succès.
     */
    private function apiSuccess(string $message, array $data =[]): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'payload' => $data
        ], 200);
    }

    /**
     * Renvoie une réponse API structurée en cas d'erreur.
     */
    private function apiError(string $message, int $code = 400, array $errors =[]): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $code);
    }

    /**
     * Fournit les données nécessaires pour populer les listes déroulantes des filtres (Frontend).
     */
    private function getFilterOptions(): array
    {
        return[
            // Limité pour éviter de charger 100 000 entrées en mémoire
            'courses' => Course::select('id', 'title')->orderBy('title')->limit(200)->get(),
            'categories' => Category::select('id', 'name')->orderBy('name')->get(),
            // Supposons que les instructeurs ont un rôle spécifique
            'instructors' => User::whereHas('roles', fn($q) => $q->where('name', 'instructor'))
                                ->select('id', 'name')->get(),
            'statuses' => [['value' => 'paid', 'label' => 'Payée'],
                ['value' => 'completed', 'label' => 'Terminée'],['value' => 'refunded', 'label' => 'Remboursée'],['value' => 'pending', 'label' => 'En attente'],['value' => 'failed', 'label' => 'Échouée'],
            ],
            'payment_methods' => [['value' => 'stripe', 'label' => 'Carte Bancaire (Stripe)'],['value' => 'paypal', 'label' => 'PayPal'],
                ['value' => 'bank_transfer', 'label' => 'Virement Bancaire'],
            ],
            'group_by' => [['value' => 'day', 'label' => 'Par Jour'],
                ['value' => 'week', 'label' => 'Par Semaine'],
                ['value' => 'month', 'label' => 'Par Mois'],['value' => 'year', 'label' => 'Par Année'],
            ]
        ];
    }

    /**
     * Retourne la liste des rapports disponibles pour le front-end.
     */
    private function getAvailableReportsList(): array
    {
        return[
            self::REPORT_SALES_OVER_TIME => 'Ventes dans le temps',
            self::REPORT_SALES_BY_COURSE => 'Ventes par Cours',
            self::REPORT_SALES_BY_INSTRUCTOR => 'Ventes par Instructeur',
            self::REPORT_SALES_BY_CATEGORY => 'Ventes par Catégorie',
            self::REPORT_SALES_BY_REGION => 'Ventes par Région',
            self::REPORT_TOP_STUDENTS => 'Top Étudiants',
            self::REPORT_SALES_BY_PAYMENT_METHOD => 'Méthodes de paiement',
            self::REPORT_REFUNDS => 'Remboursements',
            self::REPORT_ABANDONED_CARTS => 'Paniers abandonnés',
            self::REPORT_TAXES => 'Taxes collectées',
            self::REPORT_DISCOUNTS => 'Codes promotionnels',
        ];
    }
}
