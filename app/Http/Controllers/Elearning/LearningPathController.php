<?php

namespace App\Http\Controllers\Elearning;

/**
 * ======================================================================================
 * SUBNETCONGO LMS - LEARNING PATH ARCHITECT ENGINE & COHORT MANAGER v17.5.0
 * ======================================================================================
 * Pilote la création de cursus complexes (Learning Paths) et de bundles de cours.
 * Gère la hiérarchie, la monétisation, la progression et les cohortes d'étudiants.
 *
 * @package App\Http\Controllers
 * @version 17.5.0
 * @author  SubnetCongo Framework Division
 */

use App\Models\Elearning\{LearningPath, Course, Category, Cohort};
use App\Models\{AuditLog, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Storage, Log, Cache, Redirect};
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class LearningPathController extends Controller
{
    // Constantes d'états
    const STATUS_DRAFT     = 'Draft';
    const STATUS_PUBLISHED = 'Published';
    const STATUS_ARCHIVED  = 'Archived';

    /**
     * --------------------------------------------------------------------------
     * 1. EXPLORATEUR DE PARCOURS (INDEX)
     * --------------------------------------------------------------------------
     */
    public function index(Request $request)
    {
        $query = LearningPath::with([
            'category',
            'courses' => function ($q) {
                $q->select('courses.id', 'title', 'thumbnail_path', 'level', 'category_id', 'estimated_duration')->withCount('students');
            },
            // On charge les cohortes et leurs étudiants
            'cohorts.students' => function ($studentQuery) {
                $studentQuery->select('users.id', 'name', 'email', 'avatar')->withPivot('created_at as enrolled_at');
            }
        ]);

        // Moteur de recherche et filtres
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paginator = $query->latest()->paginate($request->input('per_page', 10))->withQueryString();

        $paginator->getCollection()->transform(function ($path) {
            // On consolide les étudiants de toutes les cohortes associées en une seule liste "enrollments"
            $enrollments = $path->cohorts->flatMap(fn($cohort) => $cohort->students)->unique('id');

            $path->enrollments = $enrollments;
            $path->users_count = $enrollments->count();

            unset($path->cohorts); // Nettoyage pour ne pas surcharger le front-end
            $path->thumbnail = $path->thumbnail ? Storage::disk('public')->url($path->thumbnail) : null;
            $path->badge_url = $path->badge_url ? Storage::disk('public')->url($path->badge_url) : null;
            return $path;
        });

        return Inertia::render('LearningPaths', [
            'learningPaths' => $paginator,
            'categories' => Category::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'availableCourses' => Course::where('status', 'published')
                ->withCount('students')
                ->orderBy('title')

                ->get(['id', 'title', 'thumbnail_path', 'level', 'category_id', 'estimated_duration', 'students_count']),
            'users' => User::role('student')->get(['id', 'name', 'email', 'avatar']),
            'stats' => $this->getGlobalStats(),
            'filters' => $request->only(['search', 'category_id', 'level', 'status']),
            'analyticsData' => $this->getAnalyticsData()
        ]);
    }


    /**
     * --------------------------------------------------------------------------
     * 2. CRÉATION & ÉDITION (CRUD ARCHITECTE)
     * --------------------------------------------------------------------------
     */
    public function store(Request $request)
    {
        // Laravel gère automatiquement les exceptions de validation et redirige avec les erreurs Inertia.
        $validated = $this->validateRequest($request);

        DB::beginTransaction();
        try {
            $path = new LearningPath();
            $path->uuid = (string) Str::uuid();
            $path->name = $validated['name'];
            $path->slug = Str::slug($validated['name']);
            $path->description = $validated['description'];
            $path->category_id = $validated['category_id'];
            $path->price = $validated['price'] ?? 0;
            $path->status = $validated['status'] ?? self::STATUS_DRAFT;
            $path->level = $validated['level'] ?? 'Beginner';
            $path->created_by = Auth::id();

            // Gestion JSON settings
            $path->settings = $this->sanitizeJsonInput($request->input('settings', [
                'is_linear' => true,
                'allow_skip' => false,
                'show_certificate' => true
            ]));

            $path->seo_metadata = $this->sanitizeJsonInput($request->input('seo_metadata', []));

            // Upload Médias (Vignettes et Badges)
            if ($request->hasFile('thumbnail')) {
                $path->thumbnail = $request->file('thumbnail')->store('learning-paths/covers', 'public');
            }
            if ($request->hasFile('badge_url')) {
                $path->badge_url = $request->file('badge_url')->store('learning-paths/badges', 'public');
            }

            $path->save();

            // Séquençage des cours (Drag & Drop order)
            $this->syncSyllabus($path, $request->input('courses', []));

            $this->logActivity('CREATE', $path);
            DB::commit();

            return back()->with('success', 'Nouveau parcours créé avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("LP_STORE_ERR: " . $e->getMessage());
            return back()->with('error', 'Erreur système lors de la création du parcours.');
        }
    }

    public function update(Request $request, LearningPath $learningPath)
    {
        $validated = $this->validateRequest($request, $learningPath->id);

        DB::beginTransaction();
        try {
            $learningPath->name = $validated['name'];
            if ($learningPath->isDirty('name')) {
                $learningPath->slug = Str::slug($validated['name']);
            }

            $learningPath->description = $validated['description'];
            $learningPath->category_id = $validated['category_id'];
            $learningPath->price = $validated['price'];
            $learningPath->status = $validated['status'];
            $learningPath->level = $validated['level'];

            $learningPath->settings = $this->sanitizeJsonInput($request->input('settings'));
            $learningPath->seo_metadata = $this->sanitizeJsonInput($request->input('seo_metadata'));

            // Mise à jour Médias avec suppression des anciens
            if ($request->hasFile('thumbnail')) {
                if ($learningPath->thumbnail) Storage::disk('public')->delete($learningPath->thumbnail);
                $learningPath->thumbnail = $request->file('thumbnail')->store('learning-paths/covers', 'public');
            }
            if ($request->hasFile('badge_url')) {
                if ($learningPath->badge_url) Storage::disk('public')->delete($learningPath->badge_url);
                $learningPath->badge_url = $request->file('badge_url')->store('learning-paths/badges', 'public');
            }

            $learningPath->save();

            // Mise à jour de la séquence
            $this->syncSyllabus($learningPath, $request->input('courses', []));

            // NOUVEAU: Synchroniser les cours des cohortes associées pour maintenir la cohérence
            $courseIds = $learningPath->courses()->pluck('id')->toArray();
            if ($learningPath->cohorts()->exists()) {
                foreach ($learningPath->cohorts as $cohort) {
                    $cohort->courses()->sync($courseIds);
                }
            }

            $this->logActivity('UPDATE', $learningPath);
            DB::commit();

            return back()->with('success', 'Le parcours a été mis à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("LP_UPDATE_ERR: " . $e->getMessage());
            return back()->with('error', 'Erreur lors de la modification du parcours.');
        }
    }

    public function archive(LearningPath $learningPath)
    {
        $learningPath->update(['status' => self::STATUS_ARCHIVED]);
        $this->logActivity('ARCHIVE', $learningPath);
        return back()->with('warning', 'Le parcours a été archivé et retiré du catalogue.');
    }

    /**
     * Inscrire un étudiant individuellement à un parcours.
     *
     * @param Request $request
     * @param LearningPath $learningPath
     * @return \Illuminate\Http\RedirectResponse
     */
    public function enrollStudent(Request $request, LearningPath $learningPath)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            // 1. Trouver ou créer la cohorte principale pour ce parcours
            $cohort = $learningPath->cohorts()->first();
            if (!$cohort) {
                $cohort = Cohort::create([
                    'name' => 'Cohorte pour ' . $learningPath->name,
                    'description' => 'Cohorte principale pour le parcours : ' . $learningPath->name,
                    'status' => 'Actif',
                    'instructor_id' => $learningPath->created_by ?? Auth::id(),
                    'start_date' => $learningPath->created_at->toDateString(),
                    'max_students' => 1000, // Valeur par défaut élevée
                    'learning_path_id' => $learningPath->id,
                ]);
            }

            // 2. Synchroniser les cours de la cohorte avec ceux du parcours
            $courseIds = $learningPath->courses()->pluck('courses.id')->toArray();
            $cohort->courses()->sync($courseIds);

            $user = User::findOrFail($request->user_id);

            // 3. Inscription à la cohorte
            $cohort->students()->syncWithoutDetaching([$user->id]);

            // 4. Inscription en cascade aux cours
            if (!empty($courseIds)) {
                $user->enrolledCourses()->syncWithoutDetaching($courseIds);
            }

            $this->logActivity('STUDENT_ENROLLED', $learningPath);
            DB::commit();

            return back()->with('success', "L'étudiant {$user->name} a été inscrit au parcours.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("LP_ENROLL_STUDENT_ERR: " . $e->getMessage());
            return back()->with('error', "Erreur lors de l'inscription individuelle.");
        }
    }

    /**
     * --------------------------------------------------------------------------
     * 3. GESTION DES COHORTES (NOUVEAU)
     * --------------------------------------------------------------------------
     * Remplace "enrollMass" pour gérer l'ajout et la révocation des étudiants.
     */
    public function syncCohort(Request $request, LearningPath $learningPath)
    {
        $validated = $request->validate([
            'user_ids'     => 'nullable|array',
            'user_ids.*'   => 'exists:users,id',
            'remove_ids'   => 'nullable|array',
            'remove_ids.*' => 'exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            // 1. Trouver ou créer la cohorte principale pour ce parcours
            $cohort = $learningPath->cohorts()->first();
            if (!$cohort) {
                $cohort = Cohort::create([
                    'name' => 'Cohorte pour ' . $learningPath->name,
                    'description' => 'Cohorte principale pour le parcours : ' . $learningPath->name,
                    'status' => 'Actif',
                    'instructor_id' => $learningPath->created_by ?? Auth::id(),
                    'start_date' => $learningPath->created_at->toDateString(),
                    'max_students' => 1000, // Valeur par défaut élevée
                    'learning_path_id' => $learningPath->id,
                ]);
            }

            // 2. Synchroniser les cours de la cohorte avec ceux du parcours
            $courseIds = $learningPath->courses()->pluck('courses.id')->toArray();
            $cohort->courses()->sync($courseIds);

            // 3. AJOUT DES NOUVEAUX ÉTUDIANTS
            if (!empty($validated['user_ids'])) {
                // Inscrire à la cohorte
                $cohort->students()->syncWithoutDetaching($validated['user_ids']);

                // Inscrire aux cours du parcours
                if (!empty($courseIds)) {
                    foreach ($validated['user_ids'] as $userId) {
                        $user = User::find($userId);
                        if ($user) $user->enrolledCourses()->syncWithoutDetaching($courseIds);
                    }
                }
            }

            // 4. RETRAIT DES ANCIENS ÉTUDIANTS
            if (!empty($validated['remove_ids'])) {
                // Retirer de la cohorte
                $cohort->students()->detach($validated['remove_ids']);

                // Désinscrire des cours
                if (!empty($courseIds)) {
                    foreach ($validated['remove_ids'] as $userId) {
                        $user = User::find($userId);
                        if ($user) $user->enrolledCourses()->detach($courseIds);
                    }
                }
            }

            $this->logActivity('COHORT_SYNC', $learningPath);
            DB::commit();

            return back()->with('success', 'Les accès de la cohorte ont été mis à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("LP_COHORT_SYNC_ERR: " . $e->getMessage());
            return back()->with('error', 'Erreur critique lors de la mise à jour de la cohorte.');
        }
    }


    /**
     * --------------------------------------------------------------------------
     * 4. LOGIQUE MÉTIER : SYLLABUS ET COMPTEURS
     * --------------------------------------------------------------------------
     */
    private function syncSyllabus(LearningPath $path, array $courses)
    {
        $syncData = [];
        $totalDuration = 0;

        foreach ($courses as $courseData) {
            $courseId = $courseData['course_id'];
            $order = $courseData['order'];

            $syncData[$courseId] = ['order' => $order];

            $course = Course::find($courseId);
            if ($course) {
                $totalDuration += $course->estimated_duration ?? 0;
            }
        }

        $path->courses()->sync($syncData);

        // Mise à jour du cache de durée et volume
        $path->update([
            'total_duration_minutes' => $totalDuration,
            'courses_count_cache' => count($syncData)
        ]);
    }


    /**
     * --------------------------------------------------------------------------
     * 5. ANALYTICS & STATS
     * --------------------------------------------------------------------------
     */
    private function getGlobalStats()
    {
        return Cache::remember('lp_global_stats', 600, function() {
            // Note: Table 'learning_path_user' suppose le nom de votre table pivot. Ajustez si besoin.
            return [
                'total_paths' => LearningPath::where('status', self::STATUS_PUBLISHED)->count(),
                'active_students' => DB::table('learning_path_user')->count(),
                'avg_completion' => 68,
                'mrr' => LearningPath::where('status', self::STATUS_PUBLISHED)->sum('price'),
            ];
        });
    }

    private function getAnalyticsData()
    {
        return [
            'revenue' => [
                'labels' => ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Juin'],
                'data' => [1200, 2100, 1800, 3900, 4200, 5100]
            ],
            'retention' => [
                'labels' => ['Path A', 'Path B', 'Path C', 'Path D'],
                'data' => [85, 72, 91, 64]
            ]
        ];
    }


    /**
     * --------------------------------------------------------------------------
     * 6. VALIDATION & UTILS
     * --------------------------------------------------------------------------
     */
    private function sanitizeJsonInput($input)
    {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return $input;
    }

    private function validateRequest(Request $request, $id = null)
    {
        return $request->validate([
            'name' => 'required|string|min:4|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'level' => 'required|in:Beginner,Intermediate,Advanced,Expert',
            'status' => 'required|in:Draft,Published,Archived',
            'thumbnail' => 'nullable|image|max:2048',
            'badge_url' => 'nullable|image|max:1024',
            'settings' => 'nullable',
            'courses' => 'nullable|array',
            'courses.*.course_id' => 'exists:courses,id',
            'courses.*.order' => 'integer'
        ]);
    }

    private function logActivity($action, $model)
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => "LEARNING_PATH_{$action}",
            'auditable_type' => LearningPath::class,
            'auditable_id' => $model->id,
            'new_values' => json_encode($model->only(['name', 'status', 'price', 'level'])),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
}
