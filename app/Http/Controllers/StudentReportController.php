<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course; // Ajouté pour le filtre des cours
use App\Models\Submission;
// use App\Models\Certificate; // À décommenter si vous avez un modèle Certificate
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ======================================================================================
 * SUBNETCONGO LMS - ENTERPRISE ANALYTICS CONTROLLER
 * ======================================================================================
 * Contrôleur responsable de l'agrégation, du filtrage et de la distribution des données
 * d'intelligence analytique pour les apprenants.
 *
 * @version 13.0.0
 * @author SubnetCongo Framework Division
 * ======================================================================================
 */
class StudentReportController extends Controller
{
    /**
     * Affiche le tableau de bord principal (Student Intelligence Hub).
     * Intercepte les filtres, aggrège les KPIs et retourne les données formatées.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function index(Request $request): Response
    {
        // 1. Initialisation de la requête de base pour les étudiants
        $query = clone $this->getBaseStudentQuery();

        // 2. Application des filtres complexes
        $this->applyFilters($query, $request);

        // 3. Exécution et Pagination (On charge les relations nécessaires pour éviter le N+1)
        $studentsPaginator = $query->with(['courses', 'submissions' => function ($q) {
                // On précharge les 5 dernières soumissions pour l'historique récent
                $q->latest()->take(5);
            }])
            ->withCount('submissions')
            ->withAvg('submissions as avg_score', 'grade') // Crée l'attribut avg_score dynamique
            ->latest()
            ->paginate($request->input('per_page', 12))
            ->withQueryString();

        // 4. Transformation stricte de la donnée pour le Front-end (Data Transfer Object logique)
        $studentsPaginator->through(function ($student) {
            return $this->formatStudentForFrontend($student);
        });

        // 5. Retour de la vue Inertia avec injection stricte des Props
        return Inertia::render('StudentReports', [
            'students'          => $studentsPaginator,
            'filters'           => $request->only(['search', 'course', 'status', 'performance', 'per_page']),
            'courses'           => $this->getAvailableCourses(),
            'stats'             => $this->getGlobalCohortStats(),
            'availableStatuses' => $this->getAvailableStatuses(),
        ]);
    }

    /**
     * Affiche le rapport de performance détaillé d'un étudiant spécifique.
     *
     * @param  \App\Models\User  $student
     * @return \Inertia\Response
     */
    public function show(User $student)
{
    // Sécurité : S'assurer que l'utilisateur a bien le rôle étudiant
    abort_unless($student->hasRole('student'), 404, 'Profil étudiant introuvable.');

    // Optimisation : Requêtes de base distinctes pour éviter les effets de bord
    $submissionsQuery = Submission::where('user_id', $student->id);

    // On définit d'abord proprement la requête avec note sans altérer la première
    $gradedSubmissionsQuery = Submission::where('user_id', $student->id)->whereNotNull('grade');

    // --- Calcul des Statistiques Globales ---
    $averageScore = (clone $gradedSubmissionsQuery)->avg('grade') ?? 0;
    $totalAttempts = (clone $submissionsQuery)->count();
    $totalGradedAttempts = (clone $gradedSubmissionsQuery)->count();
    $successfulAttempts = (clone $gradedSubmissionsQuery)->where('grade', '>=', 50)->count();
    $successRate = $totalGradedAttempts > 0 ? ($successfulAttempts / $totalGradedAttempts) * 100 : 0;

    // --- Calcul des tendances pour les KPIs ---
    $lastFiveSubmissions = (clone $gradedSubmissionsQuery)->latest()->take(5)->get();
    $averageScoreLastFive = $lastFiveSubmissions->avg('grade') ?? 0;

    // Tendance du score : variation en % par rapport à la moyenne globale
    $scoreTrend = $averageScore > 0 ? (($averageScoreLastFive - $averageScore) / $averageScore) * 100 : ($averageScoreLastFive > 0 ? 100 : 0);

    $successfulLastFive = $lastFiveSubmissions->where('grade', '>=', 50)->count();
    $successRateLastFive = $lastFiveSubmissions->count() > 0 ? ($successfulLastFive / $lastFiveSubmissions->count()) * 100 : 0;

    // Tendance du succès : différence simple en points de pourcentage
    $successTrend = $successRateLastFive - $successRate;

    // --- Préparation des données temporelles (Graphiques) ---
    $performanceData = (clone $gradedSubmissionsQuery)
        ->orderBy('created_at', 'asc')
        ->get(['grade', 'created_at'])
        ->map(fn($sub) => [
            'score' => $sub->grade,
            'date'  => Carbon::parse($sub->created_at)->format('d M Y'),
        ]);

    // --- Données pour le Radar des Compétences ---
    $skillsData = $student->submissions()
        ->whereNotNull('grade')
        ->with('assignment.course.category')
        ->get()
        ->groupBy('assignment.course.category.name')
        ->map(function ($submissions, $categoryName) {
            if (empty($categoryName)) {
                return null;
            }
            return [
                'name' => $categoryName,
                'score' => round($submissions->avg('grade')),
            ];
        })
        ->filter()
        ->values();

    // --- Données pour le journal d'activité (Timeline) ---
    $activityLog = (clone $submissionsQuery)
        ->with('assignment')
        ->latest()
        ->get()
        ->map(function ($sub) {
            $isGraded = $sub->grade !== null;
            $isPassed = $isGraded && $sub->grade >= 50;
            $statusMap = [
                'pending_review' => ['status' => 'En attente de correction', 'icon' => 'pi pi-clock', 'color' => '#F59E0B'],
                'graded' => $isPassed
                    ? ['status' => 'Examen Réussi', 'icon' => 'pi pi-check-circle', 'color' => '#10B981']
                    : ['status' => 'Examen Échoué', 'icon' => 'pi pi-times-circle', 'color' => '#EF4444'],
                'default' => ['status' => 'Soumission Effectuée', 'icon' => 'pi pi-upload', 'color' => '#6366F1'],
            ];
            $activity = $statusMap[$sub->status] ?? $statusMap['default'];

            return array_merge($activity, [
                'date'        => Carbon::parse($sub->created_at)->translatedFormat('d F Y à H:i'),
                'description' => 'Soumission pour : ' . ($sub->assignment?->title ?? 'Évaluation') . ($isGraded ? '. Score : ' . $sub->grade . '%' : '.'),
            ]);
        });

    // --- Structure des cours ---
    $studentSubmissions = Submission::where('user_id', $student->id)
        ->get()
        ->groupBy('assignment_id');

    // CORRECTION : Fermeture correcte de la fonction map ici
    $coursesData = $student->courses()->with(['assignments'])->get()->map(function ($course) use ($student, $studentSubmissions) {
        $exams = $course->assignments->map(function ($exam) use ($studentSubmissions) {
            $submissionsForExam = $studentSubmissions->get($exam->id);

            if (!$submissionsForExam) {
                return null;
            }

            $bestSubmission = $submissionsForExam->whereNotNull('grade')->sortByDesc('grade')->first();
            $submission = $bestSubmission ?? $submissionsForExam->sortByDesc('created_at')->first();

            if (!$submission) return null;

            return [
                'id'           => $exam->id,
                'title'        => $exam->title,
                'status'       => $submission->grade !== null ? ($submission->grade >= 50 ? 'Réussi' : 'Échoué') : 'En attente',
                'score'        => $submission->grade,
                'attempted_at' => Carbon::parse($submission->created_at)->format('d/m/Y'),
            ];
        })->filter()->values();

        if ($exams->isEmpty()) {
            return null;
        }

        return [
            'id'    => $course->id,
            'title' => $course->title,
            'exams' => $exams,
        ];
    })->filter()->values(); // <-- Cette fermeture manquait dans ton code

    return Inertia::render('StudentReport', [
        'student' => [
            'id'     => $student->id,
            'name'   => $student->name,
            'email'  => $student->email,
            'avatar' => $this->getAvatarUrl($student), // Assure-toi que cette méthode existe dans ton contrôleur
        ],
        'globalStats' => [
            'average_score'   => round($averageScore, 1),
            'score_trend'     => round($scoreTrend),
            'success_rate'    => round($successRate, 1),
            'success_trend'   => round($successTrend),
            'completed_exams' => $successfulAttempts,
            'total_attempts'  => $totalAttempts,
        ],
        'coursesData' => $coursesData,
        'performanceOverTime' => [
            'labels' => $performanceData->pluck('date'),
            'scores' => $performanceData->pluck('score'),
        ],
        'skillsData' => [
            'labels' => $skillsData->pluck('name'),
            'scores' => $skillsData->pluck('score'),
        ],
        'activityLog' => $activityLog,
    ]);
}
    /**
     * ==================================================================================
     * MÉTHODES PRIVÉES (LOGIQUE MÉTIER & HELPERS)
     * ==================================================================================
     */

    /**
     * Retourne la requête de base ciblée sur les étudiants.
     */
    private function getBaseStudentQuery()
    {
        return User::query()->whereHas('roles', function ($q) {
            $q->where('name', 'student');
        });
    }

    /**
     * Applique les filtres dynamiques à la requête.
     */
    private function applyFilters($query, Request $request): void
    {
        // 1. Filtre textuel (Recherche)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        // 2. Filtre par cours
        if ($request->filled('course')) {
            $query->whereHas('courses', function ($q) use ($request) {
                $q->where('courses.id', $request->course);
            });
        }

        // 3. Filtre par statut d'activité (simulé via la date de dernière activité)
        if ($request->filled('status')) {
            $statuses = (array) $request->status;

            $query->where(function ($q) use ($statuses) {
                if (in_array('active', $statuses)) {
                    // Considéré actif si connecté dans les 7 derniers jours
                    $q->orWhere('last_activity', '>=', now()->subDays(7));
                }
                if (in_array('inactive', $statuses)) {
                    // Considéré inactif si pas de connexion depuis plus de 30 jours ou null
                    $q->orWhere('last_activity', '<', now()->subDays(30))
                      ->orWhereNull('last_activity');
                }
                if (in_array('paused', $statuses)) {
                    // Considéré en pause entre 7 et 30 jours
                    $q->orWhereBetween('last_activity', [now()->subDays(30), now()->subDays(7)]);
                }
            });
        }

        // 4. Filtre par performance (Nécessite la clause HAVING sur l'agrégation)
        if ($request->filled('performance')) {
            // Note: withAvg est déjà appelé dans l'index, nous utilisons l'alias
            $query->having('avg_score', '!=', null);

            if ($request->performance === 'high') {
                $query->having('avg_score', '>=', 80);
            } elseif ($request->performance === 'low') {
                $query->having('avg_score', '<', 50);
            } elseif ($request->performance === 'mid') {
                $query->havingBetween('avg_score', [50, 79.99]);
            }
        }
    }

    /**
     * Calcule et retourne les KPIs globaux de la cohorte.
     */
    private function getGlobalCohortStats(): array
    {
        // On base les calculs sur l'ensemble des étudiants (optimisé)
        $baseQuery = $this->getBaseStudentQuery();

        // Étudiants actifs dans les 24h
        $activeNow = (clone $baseQuery)
            ->where('last_activity', '>=', now()->subHours(24))
            ->count();

        // Score moyen global (Moyenne des moyennes)
        $avgScore = Submission::avg('grade');

        // Étudiants en danger (Moyenne < 50%)
        $atRisk = (clone $baseQuery)
            ->withAvg('submissions', 'grade')
            ->having('submissions_avg_grade', '<', 50)
            ->count();

        // Remplacer Certificate par votre vrai modèle si existant, sinon count des users ayant tout validé
        // $certifications = Certificate::count();
        $certifications = 124; // Valeur d'exemple en attendant votre modèle

        return [
            'avg_score'            => $avgScore ? round($avgScore, 1) : null,
            'active_now'           => $activeNow,
            'total_certifications' => $certifications,
            'at_risk_students'     => $atRisk,
        ];
    }

    /**
     * Formate strictement le modèle User pour correspondre au besoin du composant Vue.
     */
    private function formatStudentForFrontend(User $student): array
    {
        // Calcul du statut en ligne (actif dans les 15 dernières minutes)
        $isOnline = $student->last_activity
            ? Carbon::parse($student->last_activity)->diffInMinutes(now()) < 15
            : false;

        return [
            'id'                => $student->id,
            'name'              => $student->name,
            'email'             => $student->email,
            'avatar'            => $this->getAvatarUrl($student),
            'is_online'         => $isOnline,
            'avg_score'         => $student->avg_score !== null ? round($student->avg_score, 1) : null,
            'submissions_count' => $student->submissions_count ?? 0,

            'last_active_human' => $student->last_activity
                ? Carbon::parse($student->last_activity)->diffForHumans()
                : null,

            'ip_address'        => $student->last_login_ip ?? null,

            // Génération des métriques de compétences basées sur les cours/soumissions
            // Si vous avez une vraie table Skills, remplacez cette logique.
            'skills'            => $this->generateSkillsData($student),

            // Mappage de l'historique récent
            'recent_activities' => $student->submissions->map(function ($sub) {
                return [
                    'description' => 'A complété l\'examen : ' . ($sub->assignment?->title ?? 'Évaluation standard'),
                    'date_human'  => Carbon::parse($sub->created_at)->translatedFormat('d M Y à H:i'),
                    'score'       => $sub->grade ? $sub->grade . '/100' : 'En attente',
                    'icon'        => $sub->grade && $sub->grade >= 50 ? 'pi pi-check-circle text-emerald-500' : 'pi pi-exclamation-circle text-rose-500'
                ];
            })->toArray(),
        ];
    }

    /**
     * Génère un avatar de repli si l'utilisateur n'en a pas.
     */
    private function getAvatarUrl(User $student): string
    {
        if ($student->avatar) {
            // Assurez-vous que le chemin est absolu (Storage::url, asset, etc.)
            return asset('storage/' . $student->avatar);
        }

        $name = urlencode($student->name);
        return "https://ui-avatars.com/api/?name={$name}&background=6366f1&color=ffffff&bold=true";
    }

    /**
     * Génère des statistiques de compétences (Simulées ou réelles selon votre DB).
     */
    private function generateSkillsData(User $student): array
    {
        // Si la base n'a pas de module de compétences, on crée une donnée cohérente
        // basée sur le score global pour que l'interface soit fonctionnelle.
        $baseScore = $student->avg_score ?? 0;

        return [
            [
                'name'  => 'Compréhension Théorique',
                'score' => $baseScore > 0 ? min(100, round($baseScore + rand(-5, 10))) : 0
            ],
            [
                'name'  => 'Exercices Pratiques',
                'score' => $baseScore > 0 ? min(100, round($baseScore + rand(-15, 5))) : 0
            ],
            [
                'name'  => 'Assiduité & Régularité',
                // Logique: plus il y a de soumissions, plus le score est haut (max 100)
                'score' => min(100, ($student->submissions_count * 5) + 20)
            ]
        ];
    }

    /**
     * Récupère la liste des cours pour le menu déroulant du filtre.
     */
    private function getAvailableCourses(): array
    {
        if (class_exists(\App\Models\Course::class)) {
            return Course::select('id', 'title as name')->orderBy('title')->get()->toArray();
        }
        return [];
    }

    /**
     * Fournit les options de statut au composant Front-end.
     */
    private function getAvailableStatuses(): array
    {
        return [
            ['label' => 'Apprenant Actif (7j)', 'value' => 'active', 'severity' => 'success'],
            ['label' => 'Activité en baisse', 'value' => 'paused', 'severity' => 'warning'],
            ['label' => 'Totalement Inactif', 'value' => 'inactive', 'severity' => 'danger']
        ];
    }
}
