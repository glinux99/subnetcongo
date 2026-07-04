<?php

namespace App\Http\Controllers;

use App\Models\{Course, Category, User, Module, Lesson, Enrollment, Order, Review};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Storage, Cache, Log, Redirect};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Exception;

class CourseController extends Controller
{
    // Constantes d'état de publication
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED  = 'archived';

    // Constantes de niveaux
    public const LEVEL_BEGINNER     = 'beginner';
    public const LEVEL_INTERMEDIATE = 'intermediate';
    public const LEVEL_ADVANCED     = 'advanced';
    public const LEVEL_ALL          = 'all';

    /**
     * Affiche le catalogue principal et prépare les données pour la Datatable Vue.
     */
    public function index(Request $request)
    {
        $query = Course::with([
                'instructor:id,name,avatar',
                'category:id,name',
                'sections' => function($q) {
                    $q->orderBy('order')->with(['lessons' => function($l) {
                        $l->orderBy('order');
                    }]);
                }
            ])
            ->withCount(['students', 'lessons', 'reviews']);

        // Recherche textuelle avancée
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('short_info', 'like', "%{$search}%")
                  ->orWhereHas('instructor', fn($i) => $i->where('name', 'like', "%{$search}%"));
            });
        }

        // Filtres stricts
        $query->when($request->status, fn($q, $s) => $q->where('status', $s));
        $query->when($request->category_id, fn($q, $id) => $q->where('category_id', $id));
        $query->when($request->level, fn($q, $l) => $q->where('level', $l));

        // Tri dynamique
        $sortField = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_direction', 'desc');
        if (in_array($sortField, ['title', 'price', 'students_count', 'is_featured', 'created_at', 'status'])) {
            $query->orderBy($sortField, $sortOrder);
        }

        $courses = $query->paginate((int) $request->input('per_page', 10))->withQueryString();

        // Formatage de l'URL de la vignette pour le Front-end
        $courses->getCollection()->transform(function ($course) {
            $course->thumbnail_path = $course->thumbnail_path ? Storage::disk(config('app.disk'))->url($course->thumbnail_path) : null;
            return $course;
        });
        return Inertia::render('Courses', [
            'courses'      => $courses,
            'stats'        => $this->buildExecutiveStats(),
            'filters'      => $request->only(['search', 'status', 'category_id', 'level', 'sort_by', 'sort_direction']),
            'categories'   => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'levels'       => $this->getLevelsForSelect(),
            'instructors'  => User::role('instructor')->get(['id', 'name', 'avatar'])->toArray(),
            'meta'         => [
                'total_revenue'   => Order::where('status', 'paid')->sum('amount') ?? 0,
                'avg_rating'      => round(Review::where('is_approved', true)->avg('rating') ?? 0, 1),
                'completion_rate' => round(Enrollment::avg('progress_percent') ?? 0, 1),
            ],
        ]);
    }

    /**
     * Store: Création d'un nouveau cours depuis l'éditeur (Course Architect).
     */
    public function store(Request $request)
    {

        $this->sanitizeFormData($request);
        $validated = $this->performValidation($request);

        DB::beginTransaction();
        try {
            $data = collect($validated)->except(['sections', 'thumbnail'])->toArray();
            $data['slug'] = $this->generateUniqueSlug($validated['title'] ?? 'nouveau-cours');
            $data['created_by'] = Auth::id();
            $data['status'] = $validated['status'] ?? self::STATUS_DRAFT;

            $course = new Course();
            $course->fill($data);

            if ($request->hasFile('thumbnail')) {
                $course->thumbnail_path = $this->handleAssetUpload($request->file('thumbnail'), 'thumbnails');
            }

            $course->save();

            // Synchro du constructeur de Curriculum
            if (!empty($validated['sections'])) {
                $this->syncSections($course, $validated['sections']);
            }

            DB::commit();
            Cache::forget('snc_executive_stats');

            return Redirect::route('courses.index')->with('success', "Le cours '{$course->title}' a été forger avec succès.");

        } catch (Exception $e) {
            DB::rollBack();
            return $e;
            Log::error("[LMS] Échec création cours: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Erreur critique lors de la sauvegarde du cours.')->withInput();
        }
    }

    /**
     * Update: Mise à jour intégrale d'un cours existant et de son curriculum.
     */
    public function update(Request $request, Course $course)
    {
        $this->sanitizeFormData($request);
        $validated = $this->performValidation($request, $course->id);
        // return $validated;
        DB::beginTransaction();
        try {
            // Regénération du slug si le titre a changé et qu'aucun slug n'est forcé
            if ($course->title !== $validated['title'] && empty($validated['slug'])) {
                $validated['slug'] = $this->generateUniqueSlug($validated['title']);
            }

            // Gestion propre de l'Asset (Thumbnail)
            if ($request->hasFile('thumbnail')) {
                if ($course->thumbnail_path && Storage::disk(config('app.disk'))->exists($course->thumbnail_path)) {
                    Storage::disk(config('app.disk'))->delete($course->thumbnail_path);
                }
                $validated['thumbnail_path'] = $this->handleAssetUpload($request->file('thumbnail'), 'thumbnails');
            }

            // Exclure les relations et le fichier avant l'update direct
            $data = collect($validated)->except(['sections', 'thumbnail'])->toArray();

            $course->update($data);

            // Synchronisation intelligente de l'arbre (Modules -> Leçons)
            if (isset($validated['sections'])) {
                $this->syncSections($course, $validated['sections']);
            }

            DB::commit();
            return back()->with('success', 'Curriculum mis à jour et synchronisé avec succès.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("[LMS] Échec mise à jour cours ID {$course->id}: " . $e->getMessage());
            return back()->with('error', 'Échec de la mise à jour des données pédagogiques.')->withInput();
        }
    }

    /**
     * Archive un cours (Soft delete métier).
     */
    public function destroy(Course $course)
    {
        $course->update(['status' => self::STATUS_ARCHIVED]);
        return back()->with('success', 'Le cours a été archivé. Les apprenants actuels y conservent l\'accès.');
    }

    /**
     * Suppression en masse (Bulk Action depuis la DataTable).
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:courses,id'
        ]);

        DB::beginTransaction();
        try {
            // Au lieu de supprimer (qui casserait les commandes/certificats), on archive.
            Course::whereIn('id', $request->ids)->update(['status' => self::STATUS_ARCHIVED]);
            DB::commit();

            Cache::forget('snc_executive_stats');
            return back()->with('success', count($request->ids) . ' cours ont été archivés avec succès.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("[LMS] Échec Bulk Archive: " . $e->getMessage());
            return back()->with('error', 'Erreur lors de l\'archivage de masse.');
        }
    }

    /**
     * Duplique un cours et l'intégralité de sa structure pédagogique.
     */
    public function duplicate(Course $course)
    {
        DB::beginTransaction();
        try {
            $newCourse = $course->replicate();
            $newCourse->title = $course->title . ' (Copie)';
            $newCourse->slug  = $this->generateUniqueSlug($newCourse->title);
            $newCourse->status = self::STATUS_DRAFT;
            $newCourse->created_at = now();
            $newCourse->save();

            foreach ($course->sections as $section) {
                $newSection = $section->replicate();
                $newSection->course_id = $newCourse->id;
                $newSection->save();

                foreach ($section->lessons as $lesson) {
                    $newLesson = $lesson->replicate();
                    $newLesson->module_id = $newSection->id; // ou section_id selon votre BDD
                    $newLesson->slug = $lesson->slug . '-' . Str::lower(Str::random(6));
                    $newLesson->save();
                }
            }

            DB::commit();
            return back()->with('success', 'Le cours a été cloné avec succès dans vos brouillons.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("[LMS] Échec clonage cours ID {$course->id}: " . $e->getMessage());
            return back()->with('error', 'Impossible de dupliquer ce cours.');
        }
    }

    // =========================================================================
    // MÉTHODES PRIVÉES DE LOGIQUE MÉTIER
    // =========================================================================

    /**
     * Nettoie les données du formulaire provenant d'Inertia/Vue (FormData).
     */
    private function sanitizeFormData(Request $request): void
    {
        // Conversion des strings Javascript 'true'/'false' en booléens natifs PHP
        $booleans = ['is_featured', 'requires_enrollment', 'certificate_enabled'];
        foreach ($booleans as $field) {
            if ($request->has($field)) {
                $request->merge([$field => filter_var($request->input($field), FILTER_VALIDATE_BOOLEAN)]);
            }
        }

        // Décodage des objets JSON envoyés via FormData (multipart/form-data stringifie le JSON)
        $jsons = ['sections', 'seo_metadata', 'tags'];
        foreach ($jsons as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $decoded = json_decode($request->input($field), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $request->merge([$field => $decoded]);
                }
            }
        }
    }

    /**
     * Validation exhaustive du Course Architect (Informations, SEO, Sections, Leçons).
     */
    private function performValidation(Request $request, $courseId = null): array
    {
        $rules = [
            'title'               => 'required|string|min:3|max:150',
            'slug'                => ['nullable', 'string', Rule::unique('courses')->ignore($courseId)],
            'description'         => 'nullable|string',
            'short_info'          => 'nullable|string|max:255',
            'instructor_id'       => 'required|exists:users,id',
            'category_id'         => 'required|exists:categories,id',
            'level'               => ['required', Rule::in([self::LEVEL_BEGINNER, self::LEVEL_INTERMEDIATE, self::LEVEL_ADVANCED, self::LEVEL_ALL])],
            'price'               => 'required|numeric|min:0',
            'promo_price'         => 'nullable|numeric|min:0|lt:price',
            'status'              => ['nullable', Rule::in([self::STATUS_DRAFT, self::STATUS_PENDING, self::STATUS_PUBLISHED, self::STATUS_ARCHIVED])],
            'thumbnail'           => 'nullable|file|mimes:jpeg,png,jpg,webp|max:2048',
            'preview_video_url'   => 'nullable|url|max:255',
            'estimated_duration'  => 'nullable|numeric|min:0',
            'prerequisites'       => 'nullable|string',
            'is_featured'         => 'boolean',
            'requires_enrollment' => 'boolean',
            'certificate_enabled' => 'boolean',
            'tags'                => 'nullable|array',

            // Meta SEO
            'seo_metadata'             => 'nullable|array',
            'seo_metadata.title'       => 'nullable|string|max:150',
            'seo_metadata.description' => 'nullable|string|max:255',
            'seo_metadata.keywords'    => 'nullable|string|max:255',

            // Arbre du Curriculum (Modules)
            'sections'                       => 'nullable|array',
            'sections.*.id'                  => 'nullable',
            'sections.*.title'               => 'required|string|max:150',
            'sections.*.description'         => 'nullable|string',
            'sections.*.status'              => 'nullable|string',
            'sections.*.order'               => 'required|integer',

            // Arbre du Curriculum (Leçons contenues dans les modules)
            'sections.*.lessons'             => 'nullable|array',
            'sections.*.lessons.*.id'        => 'nullable',
            'sections.*.lessons.*.title'     => 'required|string|max:150',
            'sections.*.lessons.*.type'      => 'nullable|string|in:video,document,quiz,assignment', // Ajout assignment
            'sections.*.lessons.*.is_free'   => 'boolean',
            'sections.*.lessons.*.order'     => 'required|integer',
            'sections.*.lessons.*.duration_minutes' => 'nullable|integer|min:0', // Depuis l'UI Vue
            'sections.*.lessons.*.video_url' => 'nullable|url',
        ];

        return $request->validate($rules);
    }

    /**
     * Synchronise les modules (Sections). Gère la création, mise à jour et suppression en cascade.
     */
    private function syncSections(Course $course, array $sectionsData)
    {
        $existingSectionIds = $course->sections()->pluck('id')->toArray();
        $keptSectionIds = [];

        foreach ($sectionsData as $index => $sData) {
            $section = null;
            // Différencier un ID réel (en BDD) d'un ID temporaire généré par VueJS (ex: 'sec_170...')
            $isExisting = isset($sData['id']) && is_numeric($sData['id']);

            if ($isExisting) {
                $section = $course->sections()->find($sData['id']);
            }

            if ($section) {
                $section->update([
                    'title'       => $sData['title'],
                    'description' => $sData['description'] ?? null,
                    'order'       => $index + 1,
                ]);
                $keptSectionIds[] = $section->id;
            } else {
                $section = $course->sections()->create([
                    'title'       => $sData['title'],
                    'description' => $sData['description'] ?? null,
                    'order'       => $index + 1,
                ]);
                $keptSectionIds[] = $section->id;
            }

            // Sync des leçons dans ce module
            if (isset($sData['lessons']) && is_array($sData['lessons'])) {
                $this->syncLessons($section, $sData['lessons']);
            } else {
                // Si aucune leçon envoyée, on purge les anciennes leçons du module
                $section->lessons()->delete();
            }
        }

        // Purge des modules qui ont été retirés via l'UI
        $sectionsToDelete = array_diff($existingSectionIds, $keptSectionIds);
        if (!empty($sectionsToDelete)) {
            $course->sections()->whereIn('id', $sectionsToDelete)->delete();
        }
    }

    /**
     * Synchronise les leçons d'un module spécifique.
     */
    private function syncLessons(Module $section, array $lessonsData)
    {
        $existingLessonIds = $section->lessons()->pluck('id')->toArray();
        $keptLessonIds = [];

        foreach ($lessonsData as $index => $lData) {
            $lesson = null;
            // ID temporaire Vue: 'les_170...', ID réel: 42
            $isExisting = isset($lData['id']) && is_numeric($lData['id']);

            if ($isExisting) {
                $lesson = $section->lessons()->find($lData['id']);
            }

            $payload = [
                'title'            => $lData['title'],
                'type'             => $lData['type'] ?? 'video',
                'slug'             => Str::slug($lData['title']),
                'video_url'        => $lData['video_url'] ?? null,
                'is_free_preview'  => filter_var($lData['is_free'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'duration_minutes' => $lData['duration_minutes'] ?? 0,
                'order'            => $index + 1,
            ];

            if ($lesson) {
                $lesson->update($payload);
                $keptLessonIds[] = $lesson->id;
            } else {
                $newLesson = $section->lessons()->create($payload);
                $keptLessonIds[] = $newLesson->id;
            }
        }

        // Purge des leçons retirées via l'UI
        $lessonsToDelete = array_diff($existingLessonIds, $keptLessonIds);
        if (!empty($lessonsToDelete)) {
            $section->lessons()->whereIn('id', $lessonsToDelete)->delete();
        }
    }

    /**
     * Génère un slug unique pour le cours.
     */
    private function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $count = 1;
        while (Course::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $count++;
        }
        return $slug;
    }

    /**
     * Logique d'upload pour les images/vidéos.
     */
    private function handleAssetUpload($file, $folder): string
    {
        $name = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        return $file->storeAs("lms/{$folder}", $name, config('app.disk'));
    }

    /**
     * KPI mis en cache pour les performances du Dashboard.
     */
    private function buildExecutiveStats(): array
    {
        return Cache::remember('snc_executive_stats', 3600, function () {
            return [
                'total_courses'   => Course::where('status', '!=', self::STATUS_ARCHIVED)->count(),
                'total_students'  => Enrollment::distinct('user_id')->count('user_id'),
                'active_instructors' => Course::distinct('instructor_id')->count('instructor_id'),
            ];
        });
    }

    /**
     * Fournisseur des données statiques pour les dropdowns.
     */
    private function getLevelsForSelect(): array
    {
        return [
            ['label' => 'Débutant (Bases)',        'value' => self::LEVEL_BEGINNER],
            ['label' => 'Intermédiaire (Avancé)',  'value' => self::LEVEL_INTERMEDIATE],
            ['label' => 'Expert (Maîtrise)',       'value' => self::LEVEL_ADVANCED],
            ['label' => 'Tous niveaux (Général)',  'value' => self::LEVEL_ALL],
        ];
    }
}
