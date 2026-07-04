<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order; // Supposé
use App\Models\User;
use App\Models\Course;
use App\Models\LessonCompletion; // Supposé
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Affiche le tableau de bord analytique principal.
     */
    public function index(Request $request)
    {
        $range = $request->input('range', '30d');
        $dates = $this->getDateRange($range);

        // Revenus sur la période
        $revenueData = Order::where('status', 'paid')
            ->whereBetween('created_at', [$dates['start'], $dates['end']])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Nouveaux utilisateurs
        $usersData = User::whereBetween('created_at', [$dates['start'], $dates['end']])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Cours les plus populaires (par inscriptions)
        $topCourses = Course::withCount(['students' => function ($q) use ($dates) {
                // $q->whereBetween('enrollments.created_at', [$dates['start'], $dates['end']]);
            }])
            ->orderByDesc('students_count')
            ->take(5)
            ->get();

        return Inertia::render('Analytics', [
            'filters' => ['range' => $range],
            'charts' => [
                'revenue' => $revenueData,
                'users' => $usersData,
            ],
            'top_courses' => $topCourses,
            'summary' => [
                'total_revenue' => Order::where('status', 'paid')->sum('amount'),
                'total_students' => User::role('student')->count(),
                'active_now' => User::where('last_activity', '>=', now()->subMinutes(5))->count(),
            ]
        ]);
    }

    /**
     * Rapport détaillé des ventes.
     */
    public function sales(Request $request)
    {
        $query = Order::with('user')
            ->when($request->search, function ($q, $search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('email', 'like', "%{$search}%"));
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            });

        $sales = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Analytics/Sales', [
            'sales' => $sales,
            'filters' => $request->only(['search', 'status']),
            'total_sales' => Order::where('status', 'paid')->sum('amount'),
        ]);
    }

    /**
     * Rapport d'engagement des étudiants.
     */
    public function engagement(Request $request)
    {
        // Exemple : Leçons terminées par jour
        $completions = LessonCompletion::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->get();

        return Inertia::render('Analytics/Engagement', [
            'completions' => $completions,
        ]);
    }

    private function getDateRange($range)
    {
        $end = now();
        $start = match ($range) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '1y' => now()->subYear(),
            default => now()->subDays(30),
        };

        return ['start' => $start, 'end' => $end];
    }
}
