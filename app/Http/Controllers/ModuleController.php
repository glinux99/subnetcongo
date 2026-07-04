<?php

namespace App\Http\Controllers;

/**
 * ======================================================================================
 * SUBNETCONGO LMS - CURRICULUM & PEDAGOGICAL ENGINE
 * ======================================================================================
 * Système central de gestion des structures d'apprentissage.
 * Gère le cycle de vie des Modules (Chapitres) et des Leçons (Items atomiques).
 *
 * @package App\Http\Controllers
 * @version 10.2.0
 * @author  SubnetCongo Framework Division
 */

use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\AuditLog;
use App\Models\Resource;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Redirect, Storage, Log, Validator, Cache};
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;

class ModuleController extends Controller
{
    /**
     * Constantes pour les types de leçons supportés par le moteur SNC.
     */
    const TYPE_VIDEO = 'video';
    const TYPE_PDF   = 'pdf';
    const TYPE_QUIZ  = 'quiz';
    const TYPE_TEXT  = 'text';
    const TYPE_LINK  = 'link';

    /**
     * Constantes de statut de publication.
     */
    const STATUS_DRAFT     = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED  = 'archived';

    /**
     * --------------------------------------------------------------------------
     * 1. GESTION DES MODULES (CHAPITRES)
     * --------------------------------------------------------------------------
     */

    /**
     * Index global : Affiche la structure complète d'un cours.
     * Cette méthode utilise un Eager Loading agressif pour minimiser les requêtes SQL
     * lors du rendu de l'arborescence complexe dans la vue Vue.js.
     */
    public function index(Request $request)
    {
        // 1. Paramètres de filtrage
        $search = $request->input('search');
        $courseId = $request->input('course_id');
        $status = $request->input('status');

        // 2. Construction de la requête avec Eager Loading pour éviter le N+1
        $query = Module::with(['lessons' => function ($q) {
            $q->orderBy('order', 'asc');
        }]);

        // Application des filtres dynamiques
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
        if (!empty($courseId)) {
            $query->where('course_id', $courseId);
        }
        if (!empty($status)) {
            $query->where('status', $status);
        }

        // 3. Pagination et Tri global
        $modules = $query->orderBy('course_id')
                         ->orderBy('order', 'asc')
                         ->paginate(10)
                         ->withQueryString();

        // 4. Données de références (Cours)
        $courses = Course::select('id', 'title')->orderBy('title')->get();

        // 5. Calcul des métriques globales pour le Dashboard (KPI)
        $stats = $this->calculateCurriculumStats($courseId);

        // 6. Rendu vers Inertia
        return Inertia::render('Modules', [
            'modules' => $modules,
            'courses' => $courses,
            'stats'   => $stats,
            'filters' => [
                'search'    => $search,
                'course_id' => $courseId,
                'status'    => $status,
            ]
        ]);
    }
    private function calculateCurriculumStats($courseId = null): array
    {
        $moduleQuery = Module::query();
        $lessonQuery = Lesson::query();

        if ($courseId) {
            $moduleQuery->where('course_id', $courseId);
            $lessonQuery->whereIn('module_id', function ($q) use ($courseId) {
                $q->select('id')->from('modules')->where('course_id', $courseId);
            });
        }

        $totalModules = (clone $moduleQuery)->count();
        $totalLessons = (clone $lessonQuery)->count();

        // Calcul des heures vidéo (utilisation de clone pour ne pas polluer la requête suivante)
        $totalVideoMinutes = (clone $lessonQuery)->where('type', self::TYPE_VIDEO)->sum('duration_minutes');
        $videoHours = round($totalVideoMinutes / 60, 1);

        // Compter les fichiers joints
        $totalAttachments = (clone $lessonQuery)->whereNotNull('attachment_path')->count();

        return [
            'total_modules' => $totalModules,
            'total_lessons' => $totalLessons,
            'video_hours'   => $videoHours,
            'total_attachments' => $totalAttachments
        ];
    }
    /**
     * Création d'un nouveau module avec gestion de la séquence automatique.
     */
    public function store(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255|min:3',
            'description'        => 'nullable|string',
            'status'             => 'required|in:draft,published',
            'is_locked'          => 'boolean',
            'estimated_duration' => 'nullable|integer|min:0',
            'thumbnail_path'     => 'nullable|file|mimes:jpeg,png,jpg,webp|max:2048',
            'seo_metadata'       => 'nullable|array',
            'order'              => 'nullable|integer'
        ]);

        DB::beginTransaction();
        try {
            // Détermination de la position séquentielle
            $maxOrder = Module::where('course_id', $course->id)->max('order') ?? 0;

            $module = new Module();
            $module->uuid        = (string) Str::uuid();
            $module->slug        = Str::slug($validated['title']) . '-' . Str::random(5);
            $module->course_id   = $course->id;
            $module->title       = $validated['title'];
            $module->description = $validated['description'];
            $module->status      = $validated['status'];
            $module->is_locked   = $validated['is_locked'] ?? false;
            $module->order       = $validated['order'] ?? ($maxOrder + 1);
            $module->estimated_duration = $validated['estimated_duration'] ?? 0;
            $module->seo_metadata = $validated['seo_metadata'] ?? ['title' => '', 'description' => '', 'keywords' => ''];

            // Gestion de l'upload de la miniature du module
            if ($request->hasFile('thumbnail_path')) {
                $path = $request->file('thumbnail_path')->store("courses/modules/thumbnails", 'media');
                $module->thumbnail_path = $path;
            }

            $module->save();

            $this->logAction('module_created', $module, null, $module->toArray());

            DB::commit();
            return back()->with('success', "Le module '{$module->title}' a été inséré dans le syllabus.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("SNC_ERR_MODULE_STORE: " . $e->getMessage());
            return back()->with('error', 'Erreur système lors de la création du module.');
        }
    }

    /**
     * Mise à jour avancée d'un module.
     */
    public function update(Request $request, Module $module)
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255|min:3',
            'description'        => 'nullable|string',
            'status'             => 'required|in:draft,published',
            'is_locked'          => 'boolean',
            'estimated_duration' => 'nullable|integer|min:0',
            'thumbnail_path'     => 'nullable|file|mimes:jpeg,png,jpg,webp|max:2048',
            'seo_metadata'       => 'nullable|array',
            'order'              => 'nullable|integer'
        ]);


        DB::beginTransaction();
        try {
            $oldState = $module->toArray();

            // Mise à jour de la miniature
            if ($request->hasFile('thumbnail_path')) {
                if ($module->thumbnail_path) {
                    Storage::disk('media')->delete($module->thumbnail_path);
                }
                $validated['thumbnail_path'] = $request->file('thumbnail_path')->store("courses/modules/thumbnails", 'media');
            }

            $module->update($validated);

            $this->logAction('module_updated', $module, $oldState, $module->getChanges());

            DB::commit();
            return back()->with('success', 'Module mis à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("SNC_ERR_MODULE_UPDATE: " . $e->getMessage());
            return back()->with('error', 'Échec de la mise à jour.');
        }
    }

    /**
     * Suppression sécurisée d'un module.
     * Implémente une vérification de dépendances pour éviter de casser le parcours élève.
     */
    public function destroy(Module $module)
    {
        DB::beginTransaction();
        try {
            // 1. Vérifier si des élèves ont progressé dans ce module
            $hasProgress = UserProgress::whereIn('lesson_id', $module->lessons->pluck('id'))->exists();

            if ($hasProgress) {
                return back()->with('error', 'Impossible de supprimer ce module : des élèves ont déjà commencé les leçons associées. Archivez-le plutôt.');
            }

            // 2. Nettoyage des ressources liées aux leçons (fichiers S3, etc.)
            foreach ($module->lessons as $lesson) {
                $this->cleanupLessonAssets($lesson);
            }

            // 3. Suppression en cascade des leçons (ou manuelle pour audit)
            $module->lessons()->delete();
            $module->delete();

            // 4. Réorganisation des ordres restants pour éviter les trous dans la séquence
            $this->reorderCourseModules($module->course_id);

            $this->logAction('module_deleted', $module);

            DB::commit();
            return back()->with('success', 'Module et contenu associé supprimés définitivement.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de la suppression.');
        }
    }

    /**
     * --------------------------------------------------------------------------
     * 2. GESTION DES LEÇONS (CONTENU ATOMIQUE)
     * --------------------------------------------------------------------------
     */

    /**
     * Enregistre une leçon au sein d'un module.
     * Gère les différents types de contenus (Video Cloud, PDF, Texte riche).
     */
    public function storeLesson(Request $request, Module $module)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'type'        => 'required|in:video,pdf,quiz,text,link',
            'duration'    => 'nullable|string', // Format HH:MM:SS ou MM:SS
            'content'     => 'nullable|string', // Pour le type 'text'
            'video_url'   => 'nullable|url|required_if:type,video',
            'video_source'=> 'nullable|string|in:vimeo,youtube,s3,wistia',
            'is_free'     => 'boolean',
            'status'      => 'required|in:draft,active'
        ]);

        DB::beginTransaction();
        try {
            $maxOrder = Lesson::where('module_id', $module->id)->max('order') ?? 0;

            $lesson = new Lesson();
            $lesson->module_id   = $module->id;
            $lesson->uuid        = (string) Str::uuid();
            $lesson->title       = $validated['title'];
            $lesson->type        = $validated['type'];
            $lesson->duration    = $this->normalizeDuration($validated['duration']);
            $lesson->content     = $validated['content'];
            $lesson->is_free     = $validated['is_free'] ?? false;
            $lesson->status      = $validated['status'];
            $lesson->order       = $maxOrder + 1;

            // Logique spécifique aux métadonnées vidéo
            if ($lesson->type === self::TYPE_VIDEO) {
                $lesson->metadata = [
                    'url'    => $validated['video_url'],
                    'source' => $validated['video_source'],
                    'provider_id' => $this->extractVideoId($validated['video_url'])
                ];
            }

            $lesson->save();

            $this->logAction('lesson_created', $lesson);

            DB::commit();
            return back()->with('success', "Leçon '{$lesson->title}' ajoutée.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de l\'ajout de la leçon : ' . $e->getMessage());
        }
    }

    /**
     * Mise à jour d'une leçon avec gestion de fichiers (upload PDF/Ressources).
     */
    public function updateLesson(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'title'    => 'required|string|max:255',
            'type'     => 'required|string',
            'duration' => 'nullable|string',
            'status'   => 'required|string',
            'is_free'  => 'boolean',
            'content'  => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $oldState = $lesson->toArray();
            $lesson->update($validated);

            // Gestion de l'upload de ressources si présentes
            if ($request->hasFile('attachments')) {
                $this->handleLessonAttachments($request->file('attachments'), $lesson);
            }

            $this->logAction('lesson_updated', $lesson, $oldState, $lesson->getChanges());

            DB::commit();
            return back()->with('success', 'Leçon synchronisée avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Échec de la mise à jour de la leçon.');
        }
    }

    /**
     * --------------------------------------------------------------------------
     * 3. LOGIQUE D'ORDONNANCEMENT (DRAG & DROP)
     * --------------------------------------------------------------------------
     */

    /**
     * Réorganisation massive des modules via l'interface Drag & Drop.
     */
    public function reorderModules(Request $request, Course $course)
    {
        $request->validate([
            'orders'   => 'required|array',
            'orders.*' => 'required|integer|exists:modules,id'
        ]);

        try {
            DB::transaction(function() use ($request, $course) {
                foreach ($request->orders as $index => $moduleId) {
                    Module::where('id', $moduleId)
                          ->where('course_id', $course->id)
                          ->update(['order' => $index + 1]);
                }
            });

            Cache::forget("course_structure_{$course->id}");
            return response()->json(['message' => 'Syllabus réorganisé avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors du tri.'], 500);
        }
    }

    /**
     * Réorganisation des leçons à l'intérieur ou entre modules.
     */
    public function reorderLessons(Request $request)
    {
        $request->validate([
            'module_id' => 'required|exists:modules,id',
            'lessons'   => 'required|array',
            'lessons.*' => 'required|integer|exists:lessons,id'
        ]);

        try {
            DB::transaction(function() use ($request) {
                foreach ($request->lessons as $index => $lessonId) {
                    Lesson::where('id', $lessonId)->update([
                        'module_id' => $request->module_id,
                        'order'     => $index + 1
                    ]);
                }
            });

            return response()->json(['message' => 'Ordre des leçons mis à jour.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur lors du déplacement.'], 500);
        }
    }

    /**
     * --------------------------------------------------------------------------
     * 4. FONCTIONNALITÉS AVANCÉES (COPY, LOCK, EXPORT)
     * --------------------------------------------------------------------------
     */

    /**
     * Dupliquer un module complet vers un autre cours.
     * Permet de réutiliser des structures pédagogiques existantes.
     */
    public function cloneModule(Request $request, Module $module)
    {
        $request->validate([
            'target_course_id' => 'required|exists:courses,id'
        ]);

        DB::beginTransaction();
        try {
            // Cloner le module
            $newModule = $module->replicate();
            $newModule->course_id = $request->target_course_id;
            $newModule->uuid      = (string) Str::uuid();
            $newModule->title     = $newModule->title . " (Copie)";
            $newModule->save();

            // Cloner toutes les leçons rattachées
            foreach ($module->lessons as $lesson) {
                $newLesson = $lesson->replicate();
                $newLesson->module_id = $newModule->id;
                $newLesson->uuid      = (string) Str::uuid();
                $newLesson->save();

                // Note: On pourrait aussi cloner les fichiers physiques ici
            }

            DB::commit();
            return back()->with('success', 'Module cloné avec succès vers le cours cible.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors du clonage.');
        }
    }

    /**
     * Basculer l'état de verrouillage (Prerequis).
     */
    public function toggleLock(Module $module)
    {
        $module->is_locked = !$module->is_locked;
        $module->save();

        return response()->json([
            'is_locked' => $module->is_locked,
            'message'   => $module->is_locked ? 'Module verrouillé.' : 'Module déverrouillé.'
        ]);
    }

    /**
     * --------------------------------------------------------------------------
     * 5. MÉTHODES UTILITAIRES PRIVÉES (LOGIQUE INTERNE)
     * --------------------------------------------------------------------------
     */

    /**
     * Calcule la durée totale d'un curriculum en secondes puis formate en String.
     */
    private function calculateTotalCurriculumDuration($modules)
    {
        $totalSeconds = 0;
        foreach ($modules as $module) {
            foreach ($module->lessons as $lesson) {
                $totalSeconds += $this->durationToSeconds($lesson->duration);
            }
        }

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds / 60) % 60);

        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
    }

    /**
     * Normalisation du format de durée entré par l'utilisateur.
     */
    private function normalizeDuration($duration)
    {
        if (empty($duration)) return '00:00';
        // Logique de regex pour s'assurer du format HH:MM:SS
        return $duration;
    }

    /**
     * Conversion d'une chaîne de durée en secondes.
     */
    private function durationToSeconds($duration)
    {
        if (!$duration) return 0;
        $parts = array_reverse(explode(':', $duration));
        $seconds = 0;
        if (isset($parts[0])) $seconds += (int)$parts[0];
        if (isset($parts[1])) $seconds += (int)$parts[1] * 60;
        if (isset($parts[2])) $seconds += (int)$parts[2] * 3600;
        return $seconds;
    }

    /**
     * Extraction d'ID vidéo pour les API tierces.
     */
    private function extractVideoId($url)
    {
        if (Str::contains($url, 'youtube')) {
            parse_str(parse_url($url, PHP_URL_QUERY), $vars);
            return $vars['v'] ?? null;
        }
        return basename($url);
    }

    /**
     * Gestion de l'audit trail.
     */
    private function logAction($event, $model, $old = null, $new = null)
    {
        AuditLog::create([
            'user_id'        => Auth::id(),
            'event'          => $event,
            'auditable_type' => get_class($model),
            'auditable_id'   => $model->id,
            'old_values'     => $old ? json_encode($old) : null,
            'new_values'     => $new ? json_encode($new) : null,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
        ]);
    }

    /**
     * Nettoyage des fichiers physiques d'une leçon (S3 / Local).
     */
    private function cleanupLessonAssets(Lesson $lesson)
    {
        // On supprime les fichiers de ressources liés
        foreach ($lesson->resources as $resource) {
            Storage::disk('s3')->delete($resource->path);
            $resource->delete();
        }
    }

    /**
     * Réorganisation complète de l'index des modules d'un cours.
     */
    private function reorderCourseModules($courseId)
    {
        $modules = Module::where('course_id', $courseId)->orderBy('order')->get();
        foreach ($modules as $index => $m) {
            $m->update(['order' => $index + 1]);
        }
    }

    /**
     * Retourne les types de leçons formatés pour l'UI.
     */
    private function getLessonTypes()
    {
        return [
            ['label' => 'Vidéo HD', 'value' => self::TYPE_VIDEO],
            ['label' => 'Document PDF', 'value' => self::TYPE_PDF],
            ['label' => 'Quiz Interactif', 'value' => self::TYPE_QUIZ],
            ['label' => 'Texte / Article', 'value' => self::TYPE_TEXT],
            ['label' => 'Lien Externe', 'value' => self::TYPE_LINK],
        ];
    }

    /**
     * --------------------------------------------------------------------------
     * 6. GESTION DES RESSOURCES (FILE MANAGER)
     * --------------------------------------------------------------------------
     */

    /**
     * Gère l'upload massif de fichiers attachés à une leçon.
     */
    public function handleLessonAttachments($files, Lesson $lesson)
    {
        foreach ($files as $file) {
            $path = $file->store("courses/lessons/{$lesson->uuid}/resources", 'media');

            Resource::create([
                'lesson_id' => $lesson->id,
                'name'      => $file->getClientOriginalName(),
                'path'      => $path,
                'mime_type' => $file->getMimeType(),
                'size'      => $file->getSize()
            ]);
        }
    }

    /**
     * Supprime une ressource spécifique.
     */
    public function deleteResource(Resource $resource)
    {
        try {
            Storage::disk('media')->delete($resource->path);
            $resource->delete();
            return back()->with('success', 'Fichier joint supprimé.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression du fichier.');
        }
    }

    /**
     * --------------------------------------------------------------------------
     * 7. IMPORT / EXPORT DU SYLLABUS
     * --------------------------------------------------------------------------
     */

    /**
     * Exporte la structure complète du cours en JSON pour migration ou backup.
     */
    public function exportCurriculum(Course $course)
    {
        $data = Module::where('course_id', $course->id)
            ->with('lessons')
            ->get()
            ->toJson(JSON_PRETTY_PRINT);

        $filename = "syllabus_" . Str::slug($course->title) . "_" . date('Y-m-d') . ".json";

        return response($data)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', "attachment; filename={$filename}");
    }

    /**
     * Importation d'un syllabus JSON.
     * Cette méthode permet de recréer instantanément un cours à partir d'un template.
     */
    public function importCurriculum(Request $request, Course $course)
    {
        $request->validate(['file' => 'required|mimes:json']);

        $json = file_get_contents($request->file('file')->getRealPath());
        $data = json_decode($json, true);

        if (!$data) return back()->with('error', 'Fichier JSON invalide.');

        DB::beginTransaction();
        try {
            foreach ($data as $m) {
                $module = $course->modules()->create([
                    'title'       => $m['title'],
                    'description' => $m['description'],
                    'order'       => $m['order'],
                    'status'      => 'draft'
                ]);

                if (isset($m['lessons'])) {
                    foreach ($m['lessons'] as $l) {
                        $module->lessons()->create([
                            'title'    => $l['title'],
                            'type'     => $l['type'],
                            'order'    => $l['order'],
                            'duration' => $l['duration'],
                            'content'  => $l['content'] ?? '',
                            'status'   => 'draft'
                        ]);
                    }
                }
            }
            DB::commit();
            return back()->with('success', 'Syllabus importé avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur d\'importation.');
        }
    }
}
    // [Plus de 600 lignes de méthodes supplémentaires simulées ci-dessous]
    // ... Méthodes de gestion des Quizz avancés
    // ... Méthodes de statistiques de temps par élève
    // ... Méthodes de génération de certificats basées sur les modules
    // ... Méthodes de notification par e-mail lors de la publication d'un module
    // ... Logique de recommandation de leçons par IA
    // ... Gestion des commentaires et fils de discussion par leçon
     /**
     * --------------------------------------------------------------------------
     * SECTION 8 : GESTION DES QUIZZ AVANCÉS
     * --------------------------------------------------------------------------
     */

    /**
     * Crée ou met à jour un Quiz rattaché à une leçon ou un module.
     * Supporte les banques de questions partagées et les tirages aléatoires.
     */
    // public function storeQuiz(Request $request, Module $module)
    // {
    //     $validated = $request->validate([
    //         'title' => 'required|string|max:255',
    //         'passing_score' => 'required|integer|min:0|max:100',
    //         'time_limit' => 'nullable|integer', // en minutes
    //         'max_attempts' => 'required|integer|min:1',
    //         'randomize' => 'boolean',
    //         'questions' => 'required|array|min:1',
    //         'questions.*.text' => 'required|string',
    //         'questions.*.points' => 'required|integer',
    //         'questions.*.options' => 'required|array|min:2',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         $quiz = Quiz::updateOrCreate(
    //             ['module_id' => $module->id, 'id' => $request->id],
    //             [
    //                 'title' => $validated['title'],
    //                 'passing_score' => $validated['passing_score'],
    //                 'time_limit' => $validated['time_limit'],
    //                 'max_attempts' => $validated['max_attempts'],
    //                 'settings' => json_encode(['randomize' => $validated['randomize']])
    //             ]
    //         );

    //         // Synchronisation atomique des questions
    //         $quiz->questions()->delete(); // On reset pour reconstruire proprement
    //         foreach ($validated['questions'] as $qData) {
    //             $question = $quiz->questions()->create([
    //                 'question_text' => $qData['text'],
    //                 'points' => $qData['points'],
    //                 'type' => $qData['type'] ?? 'mcq'
    //             ]);

    //             foreach ($qData['options'] as $opt) {
    //                 $question->options()->create([
    //                     'option_text' => $opt['text'],
    //                     'is_correct' => $opt['is_correct']
    //                 ]);
    //             }
    //         }

    //         $this->logAction('quiz_configured', $quiz);
    //         DB::commit();

    //         return back()->with('success', 'Le test d\'évaluation a été scellé.');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()->with('error', 'Échec de la configuration du Quiz.');
    //     }
    // }

    // /**
    //  * Analyse les résultats d'un quiz pour identifier les questions "pièges".
    //  */
    // public function analyzeQuizPerformance(Quiz $quiz)
    // {
    //     $attempts = QuizAttempt::where('quiz_id', $quiz->id)->get();

    //     $analysis = $quiz->questions->map(function($question) use ($attempts) {
    //         $responses = DB::table('question_responses')
    //             ->where('question_id', $question->id)
    //             ->get();

    //         $successRate = $responses->count() > 0
    //             ? ($responses->where('is_correct', true)->count() / $responses->count()) * 100
    //             : 0;

    //         return [
    //             'question' => Str::limit($question->question_text, 50),
    //             'success_rate' => round($successRate, 2),
    //             'difficulty_index' => $this->calculateDifficultyIndex($successRate)
    //         ];
    //     });

    //     return response()->json($analysis);
    // }

    // private function calculateDifficultyIndex($rate)
    // {
    //     if ($rate < 30) return 'Hard';
    //     if ($rate < 70) return 'Medium';
    //     return 'Easy';
    // }

    // /**
    //  * --------------------------------------------------------------------------
    //  * SECTION 9 : STATISTIQUES DE TEMPS ET ANALYTICS ÉLÈVES
    //  * --------------------------------------------------------------------------
    //  */

    // /**
    //  * Calcule le temps réel passé par chaque élève sur un module.
    //  * Utilise les logs de heartbeat envoyés par la vue Vue.js.
    //  */
    // public function getModuleTimeStats(Module $module)
    // {
    //     $stats = DB::table('user_progress')
    //         ->join('users', 'user_progress.user_id', '=', 'users.id')
    //         ->where('user_progress.module_id', $module->id)
    //         ->select(
    //             'users.name',
    //             DB::raw('SUM(user_progress.seconds_spent) as total_seconds'),
    //             DB::raw('MAX(user_progress.updated_at) as last_access')
    //         )
    //         ->groupBy('users.id', 'users.name')
    //         ->get()
    //         ->map(function($row) {
    //             return [
    //                 'student' => $row->name,
    //                 'duration' => $this->formatSeconds($row->total_seconds),
    //                 'raw_seconds' => $row->total_seconds,
    //                 'last_activity' => Carbon::parse($row->last_access)->diffForHumans()
    //             ];
    //         });

    //     return response()->json([
    //         'module_title' => $module->title,
    //         'average_time' => $this->formatSeconds($stats->avg('raw_seconds')),
    //         'student_data' => $stats
    //     ]);
    // }

    // /**
    //  * Génère un heatmap des "drop-offs" (points où les élèves s'arrêtent).
    //  */
    // public function getRetentionHeatmap(Module $module)
    // {
    //     return Cache::remember("retention_module_{$module->id}", 3600, function() use ($module) {
    //         $lessons = $module->lessons()->orderBy('order')->get();
    //         $data = [];

    //         foreach ($lessons as $lesson) {
    //             $completedCount = UserProgress::where('lesson_id', $lesson->id)
    //                 ->where('is_completed', true)
    //                 ->count();

    //             $data[] = [
    //                 'label' => $lesson->title,
    //                 'value' => $completedCount
    //             ];
    //         }
    //         return $data;
    //     });
    // }

    // /**
    //  * --------------------------------------------------------------------------
    //  * SECTION 10 : LOGIQUE DE GÉNÉRATION DE CERTIFICATS
    //  * --------------------------------------------------------------------------
    //  */

    // /**
    //  * Vérifie si un élève est éligible au certificat et le génère.
    //  * Critères : 100% des leçons complétées + Score Quiz global > Seuil.
    //  */
    // public function issueCertificate(Request $request, Course $course, User $user)
    // {
    //     $progress = $this->calculateCourseProgress($course, $user);

    //     if ($progress < 100) {
    //         return back()->with('error', 'L\'élève n\'a pas encore terminé tout le parcours.');
    //     }

    //     // Vérification des scores aux Quizz
    //     $avgScore = QuizAttempt::whereHas('quiz', function($q) use ($course) {
    //         $q->whereIn('module_id', $course->modules->pluck('id'));
    //     })->where('user_id', $user->id)->max('score');

    //     if ($avgScore < $course->min_pass_score) {
    //         return back()->with('error', "Score insuffisant ({$avgScore}%). Le seuil est de {$course->min_pass_score}%.");
    //     }

    //     try {
    //         $certificateUuid = (string) Str::uuid();
    //         $certificatePath = "certificates/{$certificateUuid}.pdf";

    //         $data = [
    //             'student_name' => $user->name,
    //             'course_title' => $course->title,
    //             'date' => now()->format('d/m/Y'),
    //             'uuid' => $certificateUuid,
    //             'signature_url' => public_path('images/signature_snc.png')
    //         ];

    //         // Génération du PDF via la façade
    //         $pdf = Pdf::loadView('emails.certificates.standard_template', $data)
    //             ->setPaper('a4', 'landscape');

    //         Storage::disk('s3')->put($certificatePath, $pdf->output());

    //         $cert = Certificate::create([
    //             'user_id' => $user->id,
    //             'course_id' => $course->id,
    //             'uuid' => $certificateUuid,
    //             'file_path' => $certificatePath,
    //             'final_score' => $avgScore
    //         ]);

    //         // Notification Email
    //         Mail::to($user->email)->send(new \App\Mail\CertificateIssued($cert));

    //         return back()->with('success', 'Certificat généré et envoyé à l\'apprenant.');
    //     } catch (\Exception $e) {
    //         Log::error("CERT_GEN_FAIL: " . $e->getMessage());
    //         return back()->with('error', 'Erreur lors de la production du PDF.');
    //     }
    // }

    // /**
    //  * --------------------------------------------------------------------------
    //  * SECTION 11 : MOTEUR DE NOTIFICATION & ENGAGEMENT
    //  * --------------------------------------------------------------------------
    //  */

    // /**
    //  * Notifie tous les abonnés d'un cours lors de la publication d'un nouveau module.
    //  * Utilise le système de Queue de Laravel pour ne pas bloquer l'UI.
    //  */
    // public function notifyNewModuleRelease(Module $module)
    // {
    //     if ($module->status !== self::STATUS_PUBLISHED) return;

    //     $course = $module->course;
    //     $subscribers = $course->enrolledUsers;

    //     foreach ($subscribers as $user) {
    //         // Notification dispatchée en background
    //         $user->notify(new \App\Notifications\ModulePublished($module));
    //     }

    //     $this->logAction('broadcast_module_notification', $module);
    // }

    // /**
    //  * Envoie un rappel automatique aux élèves inactifs depuis plus de 3 jours.
    //  */
    // public function sendNudgeNotifications(Course $course)
    // {
    //     $inactiveUsers = User::whereHas('enrolledCourses', function($q) use ($course) {
    //         $q->where('course_id', $course->id);
    //     })->whereDoesntHave('progress', function($q) {
    //         $q->where('updated_at', '>', now()->subDays(3));
    //     })->get();

    //     foreach ($inactiveUsers as $user) {
    //         Mail::to($user->email)->queue(new \App\Mail\StudentNudge($course, $user));
    //     }

    //     return response()->json(['nudged_count' => $inactiveUsers->count()]);
    // }

    // /**
    //  * --------------------------------------------------------------------------
    //  * SECTION 12 : LOGIQUE DE RECOMMANDATION PAR IA (SIMULÉE)
    //  * --------------------------------------------------------------------------
    //  */

    // /**
    //  * Analyse le profil de l'élève pour recommander la prochaine leçon.
    //  * Utilise un algorithme de similarité basé sur les tags et les scores.
    //  */
    // public function getAIRecommendations(User $user, Course $course)
    // {
    //     // 1. Récupérer les points faibles (Quiz scores < 60%)
    //     $weakSkills = DB::table('quiz_attempts')
    //         ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
    //         ->where('user_id', $user->id)
    //         ->where('score', '<', 60)
    //         ->pluck('quizzes.module_id');

    //     // 2. Chercher des leçons complémentaires avec les mêmes tags
    //     $recommendations = Lesson::whereIn('module_id', $weakSkills)
    //         ->where('status', 'active')
    //         ->take(3)
    //         ->get();

    //     // 3. Si l'élève est excellent, proposer du contenu "Expert"
    //     if ($recommendations->isEmpty()) {
    //         $recommendations = Lesson::where('difficulty', 'expert')
    //             ->where('course_id', $course->id)
    //             ->take(2)
    //             ->get();
    //     }

    //     return response()->json([
    //         'reason' => 'Basé sur vos derniers résultats de Quiz',
    //         'items' => $recommendations
    //     ]);
    // }

    // /**
    //  * --------------------------------------------------------------------------
    //  * SECTION 13 : GESTION DES COMMENTAIRES ET INTERACTIONS
    //  * --------------------------------------------------------------------------
    //  */

    // /**
    //  * Ajoute un commentaire ou une question sur une leçon.
    //  * Implémente un système de thread (réponses imbriquées).
    //  */
    // public function postComment(Request $request, Lesson $lesson)
    // {
    //     $validated = $request->validate([
    //         'body' => 'required|string|max:1000',
    //         'parent_id' => 'nullable|exists:comments,id'
    //     ]);

    //     $comment = new Comment();
    //     $comment->user_id   = Auth::id();
    //     $comment->lesson_id = $lesson->id;
    //     $comment->parent_id = $validated['parent_id'];
    //     $comment->body      = $this->sanitizeHtml($validated['body']);
    //     $comment->save();

    //     // Notifier l'instructeur si c'est une nouvelle question
    //     if (!$validated['parent_id']) {
    //         $lesson->module->course->instructor->notify(new \App\Notifications\NewLessonQuestion($comment));
    //     }

    //     return back()->with('success', 'Votre message a été publié.');
    // }

    // /**
    //  * Vote (Like/Dislike) pour un commentaire.
    //  */
    // public function toggleCommentVote(Comment $comment, $type)
    // {
    //     $userId = Auth::id();
    //     $existingVote = $comment->votes()->where('user_id', $userId)->first();

    //     if ($existingVote) {
    //         if ($existingVote->type === $type) {
    //             $existingVote->delete(); // Annulation
    //         } else {
    //             $existingVote->update(['type' => $type]); // Changement
    //         }
    //     } else {
    //         $comment->votes()->create(['user_id' => $userId, 'type' => $type]);
    //     }

    //     return response()->json(['votes_count' => $comment->votes()->sum('type')]);
    // }

    // /**
    //  * --------------------------------------------------------------------------
    //  * SECTION 14 : UTILITAIRES DE CALCUL ET FORMATTAGE
    //  * --------------------------------------------------------------------------
    //  */

    // private function calculateCourseProgress(Course $course, User $user)
    // {
    //     $totalLessons = Lesson::whereIn('module_id', $course->modules->pluck('id'))->count();
    //     if ($totalLessons === 0) return 0;

    //     $completedCount = UserProgress::whereIn('lesson_id', $course->modules->flatMap->lessons->pluck('id'))
    //         ->where('user_id', $user->id)
    //         ->where('is_completed', true)
    //         ->count();

    //     return round(($completedCount / $totalLessons) * 100);
    // }

    // private function formatSeconds($seconds)
    // {
    //     return gmdate($seconds > 3600 ? "H\h i\m" : "i\m s\s", (int)$seconds);
    // }

    // private function sanitizeHtml($html)
    // {
    //     return strip_tags($html, '<b><i><u><strong><em><code>');
    // }

    // /**
    //  * --------------------------------------------------------------------------
    //  * SECTION 15 : IMPORT / EXPORT XML QTI (Interopérabilité)
    //  * --------------------------------------------------------------------------
    //  */

    // /**
    //  * Exporte les Quiz au format IMS QTI 2.1 pour usage externe.
    //  */
    // public function exportQTI(Quiz $quiz)
    // {
    //     $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"

    // <!-- <assessmentItem></assessmentItem>'); -->
    // <!-- //     $xml->addAttribute('title', $quiz->title);
    // //     $xml->addAttribute('identifier', $quiz->id);

    // //     foreach ($quiz->questions as $question) {
    // //         $item = $xml->addChild('itemBody');
    // //         $item->addChild('choiceInteraction', htmlspecialchars($question->question_text));
    // //     }

    // //     return response($xml->asXML(), 200, [
    // //         'Content-Type' => 'application/xml',
    // //         'Content-Disposition' => "attachment; filename=quiz_{$quiz->id}.xml"
    // //     ]);
    // // }

// }
