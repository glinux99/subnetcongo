<?php

namespace App\Http\Controllers;

/**
 * ======================================================================================
 * SUBNETCONGO LMS - EXECUTIVE ANALYTICS & INFRASTRUCTURE ENGINE v13.6
 * ======================================================================================
 *
 * DESCRIPTION :
 * Ce contrôleur est le cerveau analytique de la plateforme SubnetCongo. Il gère
 * l'agrégation massive de données, les calculs de tendances par IA statistique,
 * et le monitoring temps réel de l'infrastructure hybride (Windows/Linux).
 *
 * CHANGELOG v13.6 (corrections) :
 * - studentDashboard() : requête vidéos réécrite (l'ancienne générait du SQL invalide),
 *   dédoublonnage des icônes de timeline, colonne de temps passé unifiée,
 *   rang et streak réellement calculés (avant : valeurs bidon), garde-fous null
 *   sur les enrollments orphelins (cours supprimé).
 * - index() : gestion d'erreur corrigée (code mort supprimé, plus de fuite de message
 *   brut en production).
 * - Nettoyage : imports et méthodes mortes supprimés.
 *
 * @version     13.6.0 (Enterprise Gold Edition)
 * @author      SubnetCongo R&D Division
 * @license     Proprietary - SubnetCongo
 */

use App\Models\User;
use App\Models\Course;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\ExamAttempt;
use App\Models\Certificate;
use App\Http\Controllers\CertificateController as CertificateEngine;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\Payment;
use App\Models\UserProgress;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    /**
     * Configuration des constantes de performance
     */
    const CACHE_KEY_KPI = 'subnet_kpis_global';
    const CACHE_KEY_CHARTS = 'subnet_charts_data';
    const CACHE_KEY_INFRA = 'subnet_infra_health';
    const CACHE_TTL = 3600; // 1 Heure

    /**
     * Couleurs SNC Enterprise pour les exports et graphiques
     */
    protected array $palette = [
        'indigo' => '#6366F1',
        'emerald' => '#10B981',
        'rose' => '#F43F5E',
        'amber' => '#F59E0B',
        'slate' => '#1E293B',
        'blue' => '#3B82F6',
        'violet' => '#8B5CF6',
    ];

    /**
     * --------------------------------------------------------------------------
     * 1. MÉTHODE PRINCIPALE : INDEX
     * --------------------------------------------------------------------------
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            // 1. Logique pour les ÉTUDIANTS
            if ($user->hasRole('student')) {
                return $this->studentDashboard($user);
            }else{
                 return $this->adminDashboard($request);
            }

            // 2. Logique pour les ADMINS / MANAGERS
            // if ($user->hasAnyRole(['admin', 'manager'])) {
            //     return 123;
            //     return $this->adminDashboard($request);
            // }

            // Fallback si aucun rôle
            return redirect()->route('login')->with('error', 'Accès restreint.');
        } catch (\Throwable $e) {
            // \Throwable (pas seulement \Exception) : capture aussi les TypeError,
            // par ex. un accès à une relation nulle non protégée.
            Log::error('Dashboard Engine Failure: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return Inertia::render('Errors/DashboardError', [
                // Le détail technique ne part jamais vers le client en production.
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : "Une erreur est survenue lors du chargement du tableau de bord.",
            ]);
        }
    }

    /**
     * --------------------------------------------------------------------------
     * 2. DASHBOARD ÉTUDIANT (LOGIQUE APPRENANT)
     * --------------------------------------------------------------------------
     */
    private function studentDashboard(User $user)
    {
        // Récupération des cours inscrits avec progression réelle
        $enrollments = Enrollment::where('user_id', $user->id)
            ->with(['course' => function ($q) {
                $q->select('id', 'title', 'thumbnail_path', 'instructor_id', 'level')
                  ->with('instructor:id,name');
            }])
            ->get();

        // On ignore les inscriptions orphelines (cours supprimé depuis) pour éviter
        // un crash sur $en->course->id quand $en->course est null.
        $myCourses = $enrollments
            ->filter(fn ($en) => $en->course !== null)
            ->map(function ($en) use ($user) {
                $next = $this->getNextLesson($en->course_id, $user->id);
                $lessonsTotal = Lesson::whereHas('module', fn ($q) => $q->where('course_id', $en->course_id))->count();
                $lessonsDone = UserProgress::where('user_id', $user->id)
                    ->where('course_id', $en->course_id)
                    ->where('progress_percent', '>=', 100)
                    ->count();

                return [
                    'id' => $en->course->id,
                    'title' => $en->course->title,
                    'instructor' => $en->course->instructor->name ?? 'SubnetCongo',
                    'progress' => (float) $en->progress_percent,
                    'last_accessed' => $en->updated_at?->diffForHumans(),
                    'image' => $en->course->thumbnail_path,
                    'level' => $en->course->level ?? 'all',
                    'lessons_done' => $lessonsDone,
                    'lessons_total' => $lessonsTotal,
                    // Objet complet (id + title + type) pour le player de leçon
                    'next_lesson' => $next,
                ];
            })
            ->values();

        // Calcul des XP et Streaks
        $xp = UserProgress::where('user_id', $user->id)
            ->where('progress_percent', '>=', 100)
            ->count() * 50;

        $certificatesCount = Certificate::where('user_id', $user->id)
            ->where('status', CertificateEngine::STATUS_ACTIVE)
            ->count();

        // ── Certificats (liste enrichie pour le panneau Certificats) ──
        $certificates = Certificate::with('course:id,title,thumbnail_path')
            ->where('user_id', $user->id)
            ->where('status', CertificateEngine::STATUS_ACTIVE)
            ->orderByDesc('issued_at')
            ->get()
            ->map(fn (Certificate $cert) => [
                'id' => $cert->id,
                'course_id' => $cert->course_id,
                'course_title' => $cert->course?->title ?? 'Cours retiré',
                'course_image' => $cert->course?->thumbnail_path,
                'score' => (float) $cert->score,
                'issued_at' => $cert->issued_at?->format('d/m/Y'),
                'certificate_code' => $cert->certificate_code,
                'download_url' => route('certificates.download', $cert->id),
            ])
            ->values();

        // ── Examens à venir (publiés, actifs, dans la fenêtre de disponibilité) ──
        $enrolledCourseIds = $enrollments->pluck('course_id')->filter()->values()->all();

        $upcomingExams = Exam::with('course:id,title')
            ->where('is_published', true)
            ->where('status', Exam::STATUS_ACTIVE)
            ->where(function ($q) use ($enrolledCourseIds) {
                $q->whereIn('course_id', $enrolledCourseIds)
                  ->orWhereNull('course_id');
            })
            ->whereDoesntHave('attempts', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->whereIn('status', [ExamAttempt::STATUS_IN_PROGRESS, ExamAttempt::STATUS_COMPLETED]);
            })
            ->orderBy('available_from')
            ->limit(8)
            ->get()
            ->map(fn (Exam $exam) => [
                'id' => $exam->id,
                'title' => $exam->title,
                'course_id' => $exam->course_id,
                'course_title' => $exam->course?->title ?? 'Examen global',
                'duration_min' => (int) ($exam->time_limit_minutes ?? $exam->duration_minutes ?? 0),
                'questions_count' => $exam->questions()->count(),
                'status' => $exam->isAvailable() ? 'ready' : 'locked',
                'scheduled_at' => $exam->available_from?->toIso8601String(),
            ])
            ->values();

        // ── Examens en cours (à reprendre) ──
        $inProgressExams = ExamAttempt::with(['exam.course:id,title'])
            ->where('user_id', $user->id)
            ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
            ->orderByDesc('started_at')
            ->limit(4)
            ->get()
            ->map(fn (ExamAttempt $attempt) => [
                'id' => $attempt->exam_id,
                'title' => $attempt->exam?->title ?? 'Examen',
                'course_title' => $attempt->exam?->course?->title ?? '—',
                'attempt_uuid' => $attempt->uuid,
                'remaining_seconds' => max(0, ($attempt->exam?->time_limit_minutes ?? 0) * 60 - $attempt->time_spent_seconds),
            ])
            ->values();

        // ── Ressources (supports + vidéos) pour chaque cours inscrit ──
        $courseResources = $this->getCourseResourcesFor($enrolledCourseIds);

       return Inertia::render('DashboardS', [
    'auth' => [
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_photo_url' => $user->profile_photo_url ?? null,
            // AJOUT DES RÔLES ET PERMISSIONS ICI
            'roles' => $user->getRoleNames(), // Retourne un tableau simple : ['student'] ou ['admin']
            'permissions' => $user->getAllPermissions()->pluck('name'), // Retourne la liste des permissions
        ],
    ],
    'meta' => [
        'greeting' => 'Bonjour',
        'message' => $myCourses->count() > 0
            ? 'Continuez vos efforts, vous progressez bien !'
            : 'Bienvenue ! Trouvez votre premier cours pour commencer.',
    ],
    'stats' => [
        'xp' => $xp,
        'streak' => $this->calculateLoginStreak($user->id),
        'certificates' => $certificatesCount,
        'hours_studied' => round(UserProgress::where('user_id', $user->id)->sum('time_spent_seconds') / 3600, 1),
        'rank' => $this->getStudentRank($user->id, $xp),
        'completion_rate' => round($enrollments->avg('progress_percent') ?? 0, 1),
    ],
    'myCourses' => $myCourses,
    'activityChart' => $this->getStudentActivityHistory($user->id),
    'recentActivities' => $this->getStudentRecentTimeline($user->id),
    'leaderboard' => $this->computeGamificationRankings(),
    'certificates' => $certificates,
    'upcomingExams' => $upcomingExams,
    'inProgressExams' => $inProgressExams,
    'courseResources' => $courseResources,
]);
    }

    /**
     * Construit { [course_id]: { materials: [], videos: [] } } pour un set de cours.
     * Toujours initialisé pour éviter les clés manquantes côté front (getResources()).
     */
    private function getCourseResourcesFor(array $courseIds): array
    {
        $courseResources = [];

        if (empty($courseIds)) {
            return $courseResources;
        }

        foreach ($courseIds as $cid) {
            $courseResources[$cid] = ['materials' => [], 'videos' => []];
        }

        $materials = Resource::where('resourceable_type', Course::class)
            ->whereIn('resourceable_id', $courseIds)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'resourceable_id', 'title', 'file_type', 'mime_type', 'size']);

        foreach ($materials as $m) {
            $courseResources[$m->resourceable_id]['materials'][] = [
                'id' => $m->id,
                'title' => $m->title,
                'type' => $m->file_type,
                'size' => (int) $m->size,
                'mime_type' => $m->mime_type,
                'download_url' => route('student.resources.download', $m->id),
            ];
        }

        // NOTE : l'ancienne requête utilisait whereIn(DB::raw('(SELECT ...)'), ...) —
        // Eloquent attend un nom de colonne en 1er argument, pas une sous-requête brute
        // combinée de cette façon (ça générait du SQL invalide). Remplacé par un join.
        $videos = Lesson::query()
            ->select('lessons.id', 'lessons.title', 'lessons.video_url', 'lessons.duration_minutes', 'modules.course_id')
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->whereIn('modules.course_id', $courseIds)
            ->where('lessons.type', Lesson::TYPE_VIDEO)
            ->orderBy('lessons.order')
            ->get();

        foreach ($videos as $v) {
            $courseResources[$v->course_id]['videos'][] = [
                'id' => $v->id,
                'lesson_id' => $v->id,
                'title' => $v->title,
                'url' => $v->video_url,
                'duration' => ($v->duration_minutes ?? 0) * 60,
                'thumbnail' => null,
            ];
        }

        return $courseResources;
    }

    /**
     * Retourne la prochaine leçon non terminée (objet complet) ou null.
     */
    private function getNextLesson(int $courseId, int $userId): ?array
    {
        $completedIds = UserProgress::where('user_id', $userId)
            ->where('progress_percent', '>=', 100)
            ->pluck('lesson_id');

        $next = DB::table('lessons')
            ->join('modules', 'lessons.module_id', '=', 'modules.id')
            ->where('modules.course_id', $courseId)
            ->whereNotIn('lessons.id', $completedIds)
            ->orderBy('modules.order')
            ->orderBy('lessons.order')
            ->first(['lessons.id', 'lessons.title', 'lessons.type']);

        return $next ? [
            'id' => $next->id,
            'title' => $next->title,
            'type' => $next->type,
        ] : null;
    }

    /**
     * Calcule le nombre de jours consécutifs (streak) de connexion.
     * Avant : comptait juste le nombre total de logins sur 30 jours (aucune notion
     * de "consécutif"). Ici on remonte jour par jour depuis aujourd'hui (ou hier,
     * si l'utilisateur ne s'est pas encore connecté aujourd'hui) tant que la chaîne
     * n'est pas rompue.
     */
    private function calculateLoginStreak(int $userId): int
    {
        $loginDates = AuditLog::where('user_id', $userId)
            ->where('event_name', 'LOGIN')
            ->where('created_at', '>=', now()->subDays(90))
            ->pluck('created_at')
            ->map(fn ($dt) => Carbon::parse($dt)->toDateString())
            ->unique()
            ->values();

        if ($loginDates->isEmpty()) {
            return 0;
        }

        $cursor = Carbon::today();
        if (! $loginDates->contains($cursor->toDateString())) {
            $cursor->subDay(); // le streak reste "actif" si pas encore connecté aujourd'hui
        }

        $streak = 0;
        while ($loginDates->contains($cursor->toDateString())) {
            $streak++;
            $cursor->subDay();
        }

        return $streak;
    }

    /**
     * Calcule le rang réel de l'étudiant par rapport aux autres, basé sur l'XP.
     * Avant : retournait simplement le nombre total d'étudiants, sans lien avec $xp.
     */
    private function getStudentRank(int $userId, int $xp): int
    {
        $studentsWithMoreXp = UserProgress::query()
            ->select('user_id', DB::raw('COUNT(*) * 50 as xp_total'))
            ->where('progress_percent', '>=', 100)
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) * 50 > ?', [$xp])
            ->get()
            ->count();

        return $studentsWithMoreXp + 1;
    }

    /**
     * Historique d'assiduité (heures d'étude par jour, 7 derniers jours).
     * Avant : sommait la colonne `seconds_spent` (incohérente avec `time_spent_seconds`
     * utilisée ailleurs) et renvoyait des secondes brutes alors que le graphique
     * affiche "Heures d'étude". Corrigé + labels de jours en français.
     */
    private function getStudentActivityHistory(int $userId): array
    {
        $labels = collect(range(6, 0))->map(
            fn ($i) => Carbon::now()->subDays($i)->locale('fr')->isoFormat('ddd')
        )->values();

        $data = collect(range(6, 0))->map(function ($i) use ($userId) {
            $seconds = UserProgress::where('user_id', $userId)
                ->whereDate('updated_at', Carbon::now()->subDays($i))
                ->sum('time_spent_seconds');

            return round($seconds / 3600, 1);
        })->values();

        return ['labels' => $labels->all(), 'data' => $data->all()];
    }

    /**
     * Flux d'activité récente pour la timeline.
     * Avant : 'icon' => 'pi pi-check' — le composant Vue préfixe déjà par "pi ",
     * ce qui produisait la classe invalide "pi pi pi-check". Corrigé.
     */
    private function getStudentRecentTimeline(int $userId): array
    {
        return AuditLog::where('user_id', $userId)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($log) => [
                'event' => $log->event_name,
                'detail' => $log->description,
                'time' => $log->created_at->diffForHumans(),
                'type' => $log->level ?? 'info', // success | info | warning
                'icon' => $log->event_name === 'LESSON_COMPLETED' ? 'pi-check' : 'pi-info-circle',
            ])
            ->values()
            ->toArray();
    }

    /**
     * --------------------------------------------------------------------------
     * 3. MÉTHODES D'INFRASTRUCTURE (ADMIN SIDE - MULTI-OS)
     * --------------------------------------------------------------------------
     */
    private function adminDashboard(Request $request)
    {
        $filters = [
            'year' => $request->input('year', now()->year),
            'month' => $request->input('month', now()->month),
            'category' => $request->input('category', 'all'),
            'search' => $request->input('search', ''),
        ];

        $this->logActivity('ADMIN_DASHBOARD_VIEW', 'Consultation des rapports de direction');

        return Inertia::render('Dashboard', [
            'meta' => $this->resolveSystemMeta(),
            'availableFilters' => $this->fetchAvailableFilters(),
            'kpis' => $this->calculateExecutiveKPIs($filters),
            'chartData' => $this->compileAdvancedChartData($filters),
            'cohorts' => $this->getDetailedCohorts($filters),
            'leaderboard' => $this->computeGamificationRankings(),
            'activities' => $this->fetchRecentAuditTrail(),
            'supportStatus' => $this->getSupportMetrics(),
            'infra' => $this->getRealTimeInfrastructureStatus(),
        ]);
    }

    /**
     * Récupère le statut réel du serveur en s'adaptant à l'OS.
     */
    private function getRealTimeInfrastructureStatus(): array
    {
        return Cache::remember(self::CACHE_KEY_INFRA, 30, function () {
            return PHP_OS_FAMILY === 'Windows'
                ? $this->getWindowsMetrics()
                : $this->getLinuxMetrics();
        });
    }

    /**
     * Métriques pour environnement de développement (Windows)
     */
    private function getWindowsMetrics(): array
    {
        try {
            $cpuOutput = shell_exec('wmic cpu get loadpercentage /value');
            preg_match('/LoadPercentage=(\d+)/', (string) $cpuOutput, $matches);
            $cpu = isset($matches[1]) ? (int) $matches[1] : rand(10, 30);

            $freeMem = shell_exec('wmic OS get FreePhysicalMemory /Value');
            $totalMem = shell_exec('wmic OS get TotalVisibleMemorySize /Value');
            preg_match('/FreePhysicalMemory=(\d+)/', (string) $freeMem, $mFree);
            preg_match('/TotalVisibleMemorySize=(\d+)/', (string) $totalMem, $mTotal);

            $ram = 0;
            if (isset($mFree[1], $mTotal[1]) && (int) $mTotal[1] > 0) {
                $ram = round(100 - (($mFree[1] / $mTotal[1]) * 100));
            }

            $diskFree = disk_free_space('C:');
            $diskTotal = disk_total_space('C:');
            $diskUsage = $diskTotal > 0 ? round(100 - (($diskFree / $diskTotal) * 100)) : 0;

            return [
                'platform' => 'Windows (Development)',
                'cpu' => $cpu,
                'ram' => $ram,
                'disk' => $diskUsage,
                'db_latency' => $this->measureDbLatency(),
                'status' => $cpu < 85 ? 'Optimal' : 'Surchargé',
                'uptime' => 'N/A sur Windows dev',
            ];
        } catch (\Throwable $e) {
            return $this->getMockMetrics('Erreur Win Metrics: ' . $e->getMessage());
        }
    }

    /**
     * Métriques pour environnement de production (Linux)
     */
    private function getLinuxMetrics(): array
    {
        try {
            $load = sys_getloadavg();
            $cpu = round($load[0] * 100 / 4); // basé sur 4 coeurs, à adapter si besoin

            $ram = $this->getLinuxRamUsage();

            $diskFree = disk_free_space('/');
            $diskTotal = disk_total_space('/');
            $diskUsage = $diskTotal > 0 ? round(100 - (($diskFree / $diskTotal) * 100)) : 0;

            return [
                'platform' => 'Linux (Production)',
                'cpu' => $cpu,
                'ram' => $ram,
                'disk' => $diskUsage,
                'db_latency' => $this->measureDbLatency(),
                'status' => $cpu < 80 ? 'Optimal' : 'Attention',
                'uptime' => trim((string) shell_exec('uptime -p')),
            ];
        } catch (\Throwable $e) {
            return $this->getMockMetrics('Erreur Linux Metrics: ' . $e->getMessage());
        }
    }

    /**
     * Utilisation RAM sous Linux via `free`. Extrait dans sa propre méthode pour
     * être réutilisable (avant : dupliqué en ligne dans getLinuxMetrics() ET dans
     * une méthode getLinuxRamUsage() jamais appelée).
     */
    private function getLinuxRamUsage(): int
    {
        $free = trim((string) shell_exec('free'));
        if (! $free) {
            return 0;
        }

        $lines = explode("\n", $free);
        $mem = array_values(array_filter(explode(' ', $lines[1] ?? '')));

        if (count($mem) < 3 || (float) $mem[1] === 0.0) {
            return 0;
        }

        return (int) round(($mem[2] / $mem[1]) * 100);
    }

    /**
     * Mesure la latence réelle de la DB (round trip).
     */
    private function measureDbLatency(): float
    {
        $start = microtime(true);
        DB::select('SELECT 1');
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * --------------------------------------------------------------------------
     * 4. MOTEUR ANALYTIQUE (MATHÉMATIQUES & STATS)
     * --------------------------------------------------------------------------
     */
    private function calculateChurnRate(): float
    {
        $totalStart = User::students()->where('created_at', '<', now()->subDays(30))->count();
        if ($totalStart === 0) {
            return 0.0;
        }

        $inactives = User::students()
            ->where('last_login_at', '<', now()->subDays(30))
            ->count();

        return round(($inactives / $totalStart) * 100, 2);
    }

    /**
     * Prédiction de revenus (régression linéaire simple) sur les 6 derniers mois.
     */
    private function predictNextMonthRevenue(): float
    {
        $data = collect(range(5, 0))->map(function ($i) {
            $date = now()->subMonths($i);
            return Payment::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('amount');
        });

        $n = $data->count();
        $sumX = collect(range(0, $n - 1))->sum();
        $sumY = $data->sum();
        $sumXY = $data->map(fn ($y, $x) => $x * $y)->sum();
        $sumX2 = collect(range(0, $n - 1))->map(fn ($x) => $x * $x)->sum();

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0) {
            return (float) $data->last();
        }

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX)) / $n;
        $prediction = $intercept + ($slope * $n);

        return round(max(0, $prediction), 2);
    }

    /**
     * --------------------------------------------------------------------------
     * 5. GESTION DES KPIS ET DONNÉES FINANCIÈRES
     * --------------------------------------------------------------------------
     */
    private function calculateExecutiveKPIs(array $filters): array
    {
        return Cache::remember('kpi_v13_' . serialize($filters), 600, function () use ($filters) {
            $targetYear = $filters['year'];

            $studentsCount = User::students()->count();
            $newStudentsThisMonth = User::students()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $revenue = Payment::whereYear('created_at', $targetYear)->sum('amount');
            $prevYearRevenue = Payment::whereYear('created_at', $targetYear - 1)->sum('amount');
            $growth = $prevYearRevenue > 0 ? (($revenue - $prevYearRevenue) / $prevYearRevenue) * 100 : 100;

            $completionRate = $this->getGlobalCompletionRate();

            return [
                'students' => [
                    'total' => $studentsCount,
                    'new_month' => $newStudentsThisMonth,
                    'churn' => $this->calculateChurnRate(),
                ],
                'revenue' => [
                    'total' => round($revenue, 2),
                    'predicted' => $this->predictNextMonthRevenue(),
                    'growth' => round($growth, 1),
                    'target' => 2500000,
                    'progress' => round(($revenue / 2500000) * 100, 1),
                ],
                'academic' => [
                    'completion' => $completionRate,
                    'avg_score' => $this->getGlobalAverageExamScore(),
                    'certificates_issued' => Certificate::count(),
                ],
                'infra_summary' => [
                    'server_load' => $this->safeGetSystemLoad(),
                    'errors_24h' => AuditLog::where('level', 'error')
                        ->where('created_at', '>', now()->subDay())
                        ->count(),
                ],
            ];
        });
    }

    /**
     * Récupère la charge CPU de manière sécurisée (Linux natif ou WMIC sous Windows,
     * avec repli si shell_exec est désactivé).
     * NOTE : l'ancien fichier avait deux méthodes quasi identiques pour ça
     * (getSystemLoad et safeGetSystemLoad) — seule celle-ci est appelée, l'autre
     * était du code mort et a été supprimée.
     */
    private function safeGetSystemLoad(): float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return isset($load[0]) ? (float) $load[0] : 0.0;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            try {
                $output = shell_exec('wmic cpu get loadpercentage /value');
                if ($output) {
                    preg_match('/LoadPercentage=(\d+)/', $output, $matches);
                    return isset($matches[1]) ? (float) $matches[1] : 0.0;
                }
            } catch (\Throwable $e) {
                return (float) rand(5, 15); // shell_exec bloqué : petite charge simulée pour l'affichage
            }
        }

        return 0.0;
    }

    /**
     * --------------------------------------------------------------------------
     * 6. COMPILATION DES GRAPHIQUES (DATAVIZ)
     * --------------------------------------------------------------------------
     */
    private function compileAdvancedChartData(array $filters): array
    {
        return [
            'revenue_evolution' => $this->getRevenueChartData($filters['year']),
            'enrollment_distribution' => $this->getEnrollmentDistributionData(),
            'skills_radar' => $this->getSkillsRadarData(),
            'hourly_activity' => $this->getHourlyActivityHeatmap(),
            'retention_cohort' => $this->getRetentionCohortData(),
        ];
    }

    private function getEnrollmentDistributionData(): array
    {
        return Cache::remember('snc_enrollment_dist', 3600, function () {
            $categories = Category::withCount(['courses as enrollments_count' => function ($query) {
                $query->join('enrollments', 'courses.id', '=', 'enrollments.course_id')
                    ->select(DB::raw('count(enrollments.id)'));
            }])
                ->orderByDesc('enrollments_count')
                ->limit(5)
                ->get();

            return [
                'labels' => $categories->pluck('name')->toArray(),
                'data' => $categories->pluck('enrollments_count')->toArray(),
            ];
        });
    }

    private function getRevenueChartData(int $year): array
    {
        $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];

        $currentYear = collect(range(1, 12))->map(
            fn ($m) => Payment::whereYear('created_at', $year)->whereMonth('created_at', $m)->sum('amount')
        );

        $lastYear = collect(range(1, 12))->map(
            fn ($m) => Payment::whereYear('created_at', $year - 1)->whereMonth('created_at', $m)->sum('amount')
        );

        return [
            'labels' => $months,
            'datasets' => [
                [
                    'label' => "Année $year",
                    'borderColor' => $this->palette['indigo'],
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'data' => $currentYear,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Année ' . ($year - 1),
                    'borderColor' => $this->palette['slate'],
                    'borderDash' => [5, 5],
                    'data' => $lastYear,
                    'fill' => false,
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    /**
     * Radar des compétences basé sur les catégories de cours.
     */
    private function getSkillsRadarData(): array
    {
        $categories = Category::withCount('courses')->get();

        return [
            'labels' => $categories->pluck('name'),
            'datasets' => [
                [
                    'label' => 'Offre de Formation',
                    'borderColor' => $this->palette['rose'],
                    'data' => $categories->pluck('courses_count'),
                ],
                [
                    'label' => 'Inscriptions Apprenants',
                    'borderColor' => $this->palette['emerald'],
                    'data' => $categories->map(
                        fn ($c) => Enrollment::whereHas('course', fn ($q) => $q->where('category_id', $c->id))->count() / 10
                    ),
                ],
            ],
        ];
    }

    /**
     * --------------------------------------------------------------------------
     * 7. GESTION DES COHORTES ET DATATABLE (BIG DATA)
     * --------------------------------------------------------------------------
     */
    private function getDetailedCohorts(array $filters): array
    {
        $query = Course::with(['category', 'creator'])
            ->withCount(['enrollments', 'lessons'])
            ->withSum('payments as total_income', 'amount');

        if ($filters['category'] !== 'all') {
            $query->where('category_id', $filters['category']);
        }

        if (! empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        return $query->orderByDesc('enrollments_count')
            ->paginate(15)
            ->through(fn ($course) => [
                'id' => $course->id,
                'title' => $course->title,
                'category' => $course->category->name ?? 'N/A',
                'instructor' => $course->creator->name ?? 'Subnet',
                'students' => $course->enrollments_count,
                'lessons' => $course->lessons_count,
                'revenue' => round($course->total_income ?? 0, 2),
                'avg_progress' => $this->getCourseAverageProgress($course->id),
                'status' => $course->status,
                'tags' => $course->tags,
            ])
            ->toArray();
    }

    /**
     * --------------------------------------------------------------------------
     * 8. MÉTHODES D'EXPORTATION MASSIVE (CHUNKED)
     * --------------------------------------------------------------------------
     */
    public function exportMassiveData(Request $request): StreamedResponse
    {
        $type = $request->input('type', 'enrollments');
        $filename = "SNC_Export_{$type}_" . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($type) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM pour Excel

            if ($type === 'enrollments') {
                fputcsv($handle, ['ID', 'Étudiant', 'Cours', 'Date Inscription', 'Progression (%)', 'Statut']);

                Enrollment::with(['user', 'course'])->chunk(1000, function ($enrollments) use ($handle) {
                    foreach ($enrollments as $row) {
                        fputcsv($handle, [
                            $row->id,
                            $row->user->name ?? 'N/A',
                            $row->course->title ?? 'N/A',
                            $row->created_at->format('d/m/Y'),
                            $row->progress_percent,
                            $row->status,
                        ]);
                    }
                });
            } else {
                fputcsv($handle, ['ID', 'Date', 'Utilisateur', 'Montant', 'Référence', 'Statut']);

                Payment::with('user')->chunk(1000, function ($payments) use ($handle) {
                    foreach ($payments as $row) {
                        fputcsv($handle, [
                            $row->id,
                            $row->created_at->format('d/m/Y H:i'),
                            $row->user->name ?? 'Système',
                            $row->amount,
                            $row->transaction_id,
                            $row->status,
                        ]);
                    }
                });
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * --------------------------------------------------------------------------
     * 9. UTILITAIRES PRIVÉS ET CALCULS SECONDAIRES
     * --------------------------------------------------------------------------
     */
    private function getGlobalCompletionRate(): float
    {
        $total = Enrollment::count();
        if ($total === 0) {
            return 0.0;
        }

        $completed = Enrollment::where('status', 'completed')->count();
        return round(($completed / $total) * 100, 2);
    }

    private function getGlobalAverageExamScore(): float
    {
        return round(ExamAttempt::avg('score') ?? 0, 1);
    }

    private function getCourseAverageProgress(int $courseId): int
    {
        return (int) (Enrollment::where('course_id', $courseId)->avg('progress_percent') ?? 0);
    }

    private function fetchAvailableFilters(): array
    {
        return [
            'years' => collect(range(now()->year, 2024))->toArray(),
            'months' => [
                1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
            ],
            'categories' => Category::select('id', 'name')->get()->toArray(),
        ];
    }

    private function resolveSystemMeta(): array
    {
        return [
            'app_name' => 'SubnetCongo Enterprise LMS',
            'version' => '13.6.0-Gold',
            'environment' => app()->environment(),
            'php_version' => PHP_VERSION,
            'server_time' => now()->toDateTimeString(),
            'greeting' => $this->getGreeting(),
        ];
    }

    private function getGreeting(): string
    {
        $hour = now()->hour;
        if ($hour < 12) {
            return 'Bonjour, Excellence.';
        }
        if ($hour < 18) {
            return 'Bon après-midi, Administrateur.';
        }
        return 'Bonsoir, Prêt pour le rapport nocturne ?';
    }

    private function computeGamificationRankings(): array
    {
        return User::students()
            ->withCount('certificates')
            ->orderByDesc('certificates_count')
            ->take(10)
            ->get()
            ->map(fn ($user, $index) => [
                'rank' => $index + 1,
                'name' => $user->name,
                'avatar' => $user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name),
                'score' => $user->certificates_count * 100,
                // TODO : la tendance est aléatoire faute d'historique de rang persistant ;
                // à remplacer par une vraie comparaison avec le rang de la semaine passée.
                'trend' => rand(0, 1) ? 'up' : 'down',
            ])
            ->toArray();
    }

    private function fetchRecentAuditTrail(): array
    {
        return AuditLog::with('user')
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'user' => $log->user->name ?? 'Système',
                'action' => $log->event_name,
                'description' => $log->description,
                'time' => $log->created_at->diffForHumans(),
                'type' => $log->level, // success, error, info, warning
            ])
            ->toArray();
    }

    private function getSupportMetrics(): array
    {
        return [
            'open_tickets' => Ticket::where('status', 'open')->count(),
            'avg_response_time' => '1.2h',
            'satisfaction_rate' => '94%',
            'recent_tickets' => Ticket::latest()->take(5)->get(['id', 'subject', 'priority', 'status']),
        ];
    }

    /**
     * Système de secours pour les métriques en cas d'erreur fatale.
     */
    private function getMockMetrics(string $error): array
    {
        Log::critical('Infrastructure Metrics Error: ' . $error);

        return [
            'platform' => 'Unknown (Error Fallback)',
            'cpu' => 0,
            'ram' => 0,
            'disk' => 0,
            'db_latency' => 0,
            'status' => 'Indisponible',
            'error' => $error,
        ];
    }

    private function logActivity(string $event, string $description): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'event_name' => $event,
            'description' => $description,
            'level' => 'info',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Maintenance : purge du cache analytique.
     */
    public function purgeCache()
    {
        Cache::forget(self::CACHE_KEY_KPI);
        Cache::forget(self::CACHE_KEY_CHARTS);
        Cache::forget(self::CACHE_KEY_INFRA);

        return back()->with('success', 'Intelligence cache purgée. Les données seront recalculées au prochain accès.');
    }

    /**
     * Analyse de l'activité horaire (heatmap).
     * TODO : données actuellement simulées (rand) — à remplacer par une vraie requête
     * sur la table des sessions/connexions quand le schéma sera confirmé.
     */
    private function getHourlyActivityHeatmap(): array
    {
        $data = [];
        for ($day = 0; $day < 7; $day++) {
            for ($hour = 0; $hour < 24; $hour++) {
                $data[] = ['day' => $day, 'hour' => $hour, 'value' => rand(10, 100)];
            }
        }
        return $data;
    }

    /**
     * Analyse de rétention par cohorte.
     * TODO : valeurs actuellement statiques — à remplacer par un vrai calcul de
     * rétention mensuelle par cohorte d'inscription.
     */
    private function getRetentionCohortData(): array
    {
        return [
            'months' => ['M1', 'M2', 'M3', 'M4', 'M5', 'M6'],
            'rates' => [100, 85, 72, 68, 60, 55],
        ];
    }
}
