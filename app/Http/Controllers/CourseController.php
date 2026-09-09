<?php

namespace App\Http\Controllers;

/**
 * ======================================================================================
 * SUBNETCONGO LMS - ARCHITECTE PÉDAGOGIQUE & COURSE ENGINE v14.0
 * ======================================================================================
 * 
 * Ce contrôleur est le coeur du système de gestion des connaissances (LMS).
 * Il orchestre la création de cursus complexes, la gestion des médias sur disque dédié,
 * et la synchronisation polymorphique des ressources de support.
 *
 * FONCTIONNALITÉS :
 * - Gestion du Curriculum (Sections -> Leçons)
 * - Bibliothèque de Ressources (Polymorphique)
 * - Moteur d'Asset Management (Disque 'media')
 * - Dashboard Analytics Intégré
 * - Actions de masse (Bulk Operations)
 * - Clonage de parcours (Deep Copy)
 */

use App\Models\{Course, Category, User, Module, Lesson, Enrollment, Order, Review, Resource, AuditLog};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Storage, Cache, Log, Redirect};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Carbon\Carbon;
use Exception;

class CourseController extends Controller
{
    // --- CONSTANTES DE CONFIGURATION ---
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED  = 'archived';

    public const LEVEL_BEGINNER     = 'beginner';
    public const LEVEL_INTERMEDIATE = 'intermediate';
    public const LEVEL_ADVANCED     = 'advanced';
    public const LEVEL_ALL          = 'all';

    protected string $disk = 'media'; // Utilisation forcée du disque média

    /**
     * --------------------------------------------------------------------------
     * 1. INDEX : GESTION DU PATRIMOINE PÉDAGOGIQUE
     * --------------------------------------------------------------------------
     */
    public function index(Request $request)
    {
        try {
            $query = Course::with([
                'instructor:id,name,avatar',
                'category:id,name',
                'resources', // Chargement des documents joints
                'sections' => function($q) {
                    $q->orderBy('order')->with(['lessons' => fn($l) => $l->orderBy('order')]);
                }
            ])
            ->withCount(['students', 'lessons', 'reviews', 'resources']);

            // --- FILTRAGE AVANCÉ ---
            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('id', 'like', "%{$search}%")
                      ->orWhereHas('instructor', fn($i) => $i->where('name', 'like', "%{$search}%"));
                });
            }

            $query->when($request->status, fn($q, $s) => $q->where('status', $s));
            $query->when($request->category_id, fn($q, $id) => $q->where('category_id', $id));
            $query->when($request->level, fn($q, $l) => $q->where('level', $l));

            // --- TRI DYNAMIQUE ---
            $sortField = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortOrder);

            $courses = $query->paginate((int) $request->input('per_page', 10))->withQueryString();

            // Transformation des URLs pour le frontend
            $courses->getCollection()->transform(function ($course) {
                $course->thumbnail_path = $course->thumbnail_path 
                    ?? null;
                return $course;
            });
         
            return Inertia::render('Courses', [
                'courses'      => $courses,
                'stats'        => $this->getAdvancedStats(),
                'filters'      => $request->only(['search', 'status', 'category_id', 'level']),
                'categories'   => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
                'levels'       => $this->getLevelsForUI(),
                'instructors'  => User::role('instructor')->get(['id', 'name', 'avatar']),
                'exams'        => \App\Models\Exam::where('is_published', true)->get(['id', 'title', 'pass_percentage', 'type']),
                'meta'         => $this->getFinancialMeta(),
            ]);

        } catch (Exception $e) {
            Log::error("[LMS] Index Error: " . $e->getMessage());
           
            return back()->with('error', "Impossible de charger le catalogue.");
        }
    }

    /**
      * --------------------------------------------------------------------------
     * 2. STORE : FORGER UN NOUVEAU PARCOURS
     * --------------------------------------------------------------------------
     */
    public function store(Request $request)
    {
        $this->prepareAndSanitize($request);
        $validated = $this->validateCourseRequest($request);

        DB::beginTransaction();
        try {
            // Création de l'entité de base
            $courseData = collect($validated)->except(['sections', 'thumbnail', 'resources'])->toArray();
            $courseData['slug'] = $this->makeUniqueSlug($validated['title']);
            $courseData['created_by'] = Auth::id();

            $course = Course::create($courseData);

            // Gestion de l'image à la une (Thumbnail)
            if ($request->hasFile('thumbnail')) {
                $course->update([
                    'thumbnail_path' => $this->uploadAsset($request->file('thumbnail'), 'thumbnails')
                ]);
            }

            // Sync de l'arbre pédagogique
            if (!empty($validated['sections'])) {
                $this->syncCurriculum($course, $validated['sections']);
            }

            // Sync des documents et fichiers (Ressources)
            if (!empty($validated['resources'])) {
                $this->syncCourseResources($course, $validated['resources']);
            }

            $this->logAction('COURSE_CREATED', "Nouveau cours '{$course->title}' créé par ".Auth::user()->name);
            
            DB::commit();
            Cache::forget('snc_executive_stats');

            return Redirect::route('courses.index')->with('success', "Le parcours pédagogique a été forgé avec succès.");

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("[LMS] Store Failure: " . $e->getMessage());
            return back()->with('error', "Erreur critique : " . $e->getMessage())->withInput();
        }
    }

    /**
     * --------------------------------------------------------------------------
     * 3. UPDATE : MODERNISER UN CURSUS
     * --------------------------------------------------------------------------
     */
    public function update(Request $request, Course $course)
    {
        $this->prepareAndSanitize($request);
        $validated = $this->validateCourseRequest($request, $course->id);

        DB::beginTransaction();
        try {
            // Update du slug si titre modifié
            if ($course->title !== $validated['title'] && empty($validated['slug'])) {
                $validated['slug'] = $this->makeUniqueSlug($validated['title']);
            }

            // Remplacement de la vignette
            if ($request->hasFile('thumbnail')) {
                if ($course->thumbnail_path) {
                    Storage::disk($this->disk)->delete($course->thumbnail_path);
                }
                $validated['thumbnail_path'] = $this->uploadAsset($request->file('thumbnail'), 'thumbnails');
            }

            // Mise à jour des données atomiques
            $course->update(collect($validated)->except(['sections', 'thumbnail', 'resources'])->toArray());

            // Synchronisation récursive
            $this->syncCurriculum($course, $validated['sections'] ?? []);
            $this->syncCourseResources($course, $validated['resources'] ?? []);

            $this->logAction('COURSE_UPDATED', "Le cours ID #{$course->id} a été mis à jour.");

            DB::commit();
            return back()->with('success', "Les modifications ont été synchronisées avec succès.");

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("[LMS] Update Failure ID {$course->id}: " . $e->getMessage());
            return back()->with('error', "Échec de la mise à jour : " . $e->getMessage());
        }
    }

    /**
     * --------------------------------------------------------------------------
     * 4. RESOURCE ENGINE : SYNCHRO POLYMORPHIQUE
     * --------------------------------------------------------------------------
     */
    private function syncCourseResources($course, array $resourcesData)
    {
        $currentIds = $course->resources()->pluck('id')->toArray();
        $keptIds = [];

        foreach ($resourcesData as $res) {
            // Cas 1 : Nouveau fichier à uploader
            if (isset($res['is_new']) && $res['is_new'] && isset($res['file'])) {
                $file = $res['file'];
                $path = $file->store('courses/attachments', $this->disk);

                $newRes = $course->resources()->create([
                    'title'       => $res['title'] ?? $file->getClientOriginalName(),
                    'path'        => $path,
                    'file_type'   => strtoupper($file->getClientOriginalExtension()),
                    'mime_type'   => $file->getMimeType(),
                    'size'        => $file->getSize(),
                    'uploaded_by' => Auth::id(),
                ]);
                $keptIds[] = $newRes->id;
            } 
            // Cas 2 : Mise à jour du titre d'un fichier existant
            elseif (isset($res['id']) && is_numeric($res['id'])) {
                $existing = Resource::find($res['id']);
                if ($existing) {
                    $existing->update(['title' => $res['title']]);
                    $keptIds[] = $existing->id;
                }
            }
        }

        // Cas 3 : Suppression des fichiers retirés de la liste
        $toDelete = array_diff($currentIds, $keptIds);
        foreach ($toDelete as $id) {
            $resToDelete = Resource::find($id);
            if ($resToDelete) {
                Storage::disk($this->disk)->delete($resToDelete->path);
                $resToDelete->delete();
            }
        }
    }

    /**
     * --------------------------------------------------------------------------
     * 5. CURRICULUM ENGINE : SECTIONS & LESSONS
     * --------------------------------------------------------------------------
     */
    private function syncCurriculum(Course $course, array $sections)
    {
        $existingSectionIds = $course->sections()->pluck('id')->toArray();
        $keptSectionIds = [];

        foreach ($sections as $index => $sData) {
            $sectionData = [
                'title'       => $sData['title'],
                'order'       => $index + 1,
                'description' => $sData['description'] ?? null,
                'has_quiz'    => filter_var($sData['has_quiz'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'quiz_required' => filter_var($sData['quiz_required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'quiz_weight' => $sData['quiz_weight'] ?? 0,
                'quiz_passing_score' => $sData['quiz_passing_score'] ?? 50,
                'quiz_attempts_allowed' => $sData['quiz_attempts_allowed'] ?? 3,
                'intro_video_url' => $sData['intro_video_url'] ?? null,
                'intro_video_title' => $sData['intro_video_title'] ?? null,
                'text_content' => $sData['text_content'] ?? null,
                'objectives' => $sData['objectives'] ?? null,
                'has_document' => filter_var($sData['has_document'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'document_title' => $sData['document_title'] ?? null,
                'document_url' => $sData['document_url'] ?? null,
                'document_name' => $sData['document_name'] ?? null,
                'order_in_course' => $index + 1,
            ];

            $section = isset($sData['id']) && is_numeric($sData['id'])
                ? $course->sections()->find($sData['id'])
                : $course->sections()->create($sectionData);

            if ($section) {
                $section->update($sectionData);
                $keptSectionIds[] = $section->id;
                
                // Synchronisation des leçons à l'intérieur
                $this->syncLessons($section, $sData['lessons'] ?? []);
            }
        }

        // Suppression des sections disparues
        $course->sections()->whereIn('id', array_diff($existingSectionIds, $keptSectionIds))->delete();
    }

    private function syncLessons(Module $section, array $lessons)
    {
        $existingLessonIds = $section->lessons()->pluck('id')->toArray();
        $keptLessonIds = [];

        foreach ($lessons as $index => $lData) {
            $payload = [
                'title'            => $lData['title'],
                'type'             => $lData['type'] ?? 'video',
                'lesson_type'      => $lData['lesson_type'] ?? ($lData['type'] ?? 'video'),
                'is_free_preview'  => filter_var($lData['is_free'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_sequential'    => filter_var($lData['is_sequential'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'duration_minutes' => $lData['duration_minutes'] ?? 0,
                'video_duration'   => $lData['video_duration'] ?? null,
                'video_source'     => $lData['video_source'] ?? null,
                'order'            => $index + 1,
                'slug'             => Str::slug($lData['title']) . '-' . Str::random(4),
                'content_text'     => $lData['content_text'] ?? null,
                'content_html'     => $lData['content_html'] ?? null,
                'content'          => $lData['content'] ?? null,
                'video_url'        => $lData['video_url'] ?? null,
                'has_attachment'   => filter_var($lData['has_attachment'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'attachment_title' => $lData['attachment_title'] ?? null,
                'document_title'   => $lData['document_title'] ?? null,
                'document_url'     => $lData['document_url'] ?? null,
                'quiz_id'          => $lData['quiz_id'] ?? null,
                'seo_metadata'     => $lData['seo_metadata'] ?? [],
            ];

            $lesson = isset($lData['id']) && is_numeric($lData['id'])
                ? $section->lessons()->find($lData['id'])
                : $section->lessons()->create($payload);

            if ($lesson) {
                $lesson->update($payload);
                $keptLessonIds[] = $lesson->id;
            }
        }

        $section->lessons()->whereIn('id', array_diff($existingLessonIds, $keptLessonIds))->delete();
    }

    /**
     * --------------------------------------------------------------------------
     * 6. BULK & DESTRUCTION : OPÉRATIONS DE NETTOYAGE
     * --------------------------------------------------------------------------
     */
    public function bulkArchive(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        Course::whereIn('id', $request->ids)->update(['status' => self::STATUS_ARCHIVED]);
        return back()->with('success', "Archivage de masse réussi.");
    }

    public function purge(Course $course)
    {
        if ($course->status !== self::STATUS_ARCHIVED) {
            return back()->with('error', "Seul un cours archivé peut être purgé.");
        }

        DB::beginTransaction();
        try {
            // Nettoyage des fichiers physiques
            if ($course->thumbnail_path) Storage::disk($this->disk)->delete($course->thumbnail_path);
            
            foreach($course->resources as $res) {
                Storage::disk($this->disk)->delete($res->path);
            }

            $course->delete(); // Hard delete
            DB::commit();
            return back()->with('success', "Le parcours et ses médias ont été effacés du serveur.");
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', "Erreur lors de la purge.");
        }
    }

    /**
     * --------------------------------------------------------------------------
     * 7. DEEP CLONING : DUPLICATION D'EXPERTISE
     * --------------------------------------------------------------------------
     */
    public function duplicate(Course $course)
    {
        DB::beginTransaction();
        try {
            $clone = $course->replicate();
            $clone->title = $course->title . " (Copie)";
            $clone->slug = $this->makeUniqueSlug($clone->title);
            $clone->status = self::STATUS_DRAFT;
            $clone->save();

            // Clonage des sections et leçons
            foreach ($course->sections as $section) {
                $newSection = $section->replicate();
                $newSection->course_id = $clone->id;
                $newSection->save();

                foreach ($section->lessons as $lesson) {
                    $newLesson = $lesson->replicate();
                    $newLesson->module_id = $newSection->id;
                    $newLesson->save();
                }
            }

            DB::commit();
            return back()->with('success', "Parcours dupliqué avec succès.");
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', "Échec du clonage.");
        }
    }

    /**
     * --------------------------------------------------------------------------
     * 8. HELPERS : LOGIQUE INTERNE & DATA UI
     * --------------------------------------------------------------------------
     */
    private function prepareAndSanitize(Request $request): void
    {
        // Convertir les chaînes "true"/"false" de FormData en booléens réels
        $fields = ['is_featured', 'requires_enrollment', 'certificate_enabled', 'has_final_exam'];
        foreach ($fields as $f) {
            if ($request->has($f)) $request->merge([$f => filter_var($request->input($f), FILTER_VALIDATE_BOOLEAN)]);
        }

        // Décoder les JSON envoyés par l'UI (Inertia/Vue)
        $jsons = ['sections', 'seo_metadata', 'tags', 'resources'];
        foreach ($jsons as $f) {
            if ($request->has($f) && is_string($request->input($f))) {
                $decoded = json_decode($request->input($f), true);
                if (json_last_error() === JSON_ERROR_NONE) $request->merge([$f => $decoded]);
            }
        }
    }

    private function validateCourseRequest(Request $request, $id = null): array
    {
        return $request->validate([
            'title'               => 'required|string|max:150',
            'instructor_id'       => 'required|exists:users,id',
            'category_id'         => 'required|exists:categories,id',
            'level'               => ['required', Rule::in([self::LEVEL_BEGINNER, self::LEVEL_INTERMEDIATE, self::LEVEL_ADVANCED, self::LEVEL_ALL])],
            'price'               => 'required|numeric|min:0',
            'promo_price'         => 'nullable|numeric|min:0|lt:price',
            'description'         => 'nullable|string',
            'short_info'          => 'nullable|string|max:255',
            'status'              => 'nullable|string',
            'thumbnail'           => 'nullable|file|image|max:2048',
            'sections'            => 'nullable|array',
            'resources'           => 'nullable|array',
            'seo_metadata'        => 'nullable|array',
            'tags'                => 'nullable|array',
            'requires_enrollment' => 'boolean',
            'certificate_enabled' => 'boolean',
            'is_featured'         => 'boolean',
            'has_final_exam'      => 'boolean',
            'final_exam_weight'   => 'nullable|numeric|min:0|max:100',
            'passing_score_20'    => 'nullable|numeric|min:0|max:20',
            'quiz_weight_total'   => 'nullable|numeric|min:0|max:100',
        ]);
    }

    private function uploadAsset($file, $folder): string
    {
        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        return $file->storeAs("lms/{$folder}", $filename, $this->disk);
    }

    private function makeUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $base = $slug;
        $count = 1;
        while (Course::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $count++;
        }
        return $slug;
    }

    private function getAdvancedStats(): array
    {
        return [
            'total_courses'   => Course::where('status', '!=', self::STATUS_ARCHIVED)->count(),
            'total_students'  => Enrollment::distinct('user_id')->count('user_id'),
            'total_modules'   => Module::count(),
            'total_resources' => Resource::count(),
        ];
    }

    private function getFinancialMeta(): array
    {
        return [
            'total_revenue'   => Order::where('status', 'paid')->sum('amount'),
            'avg_rating'      => round(Review::where('is_approved', true)->avg('rating') ?? 0, 1),
            'completion_rate' => round(Enrollment::avg('progress_percent') ?? 0, 1),
        ];
    }

    private function getLevelsForUI(): array
    {
        return [
            ['label' => 'Débutant', 'value' => self::LEVEL_BEGINNER, 'icon' => 'pi pi-star'],
            ['label' => 'Intermédiaire', 'value' => self::LEVEL_INTERMEDIATE, 'icon' => 'pi pi-bolt'],
            ['label' => 'Expert', 'value' => self::LEVEL_ADVANCED, 'icon' => 'pi pi-verified'],
            ['label' => 'Général', 'value' => self::LEVEL_ALL, 'icon' => 'pi pi-users'],
        ];
    }

    private function logAction(string $event, string $desc): void
    {
        AuditLog::create([
            'user_id'     => Auth::id(),
            'event_name'  => $event,
            'description' => $desc,
            'ip_address'  => request()->ip(),
            'level'       => 'info'
        ]);
    }
}