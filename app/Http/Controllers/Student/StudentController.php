<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CertificateController;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Resource;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * ======================================================================================
 * SUBNETCONGO LMS — STUDENT EXPERIENCE CONTROLLER v1.0
 * ======================================================================================
 * Contrôleur central pour les espaces dédiés à l'apprenant :
 *   - Mes cours (myCourses)
 *   - Mes certificats (certificates)
 *   - Ma progression (progress)
 *   - Player de leçon (lessonPlayer)
 *   - Téléchargement de ressources (resourceDownload)
 *
 * Toutes les méthodes sont scopées sur l'utilisateur authentifié pour éviter
 * toute fuite de données entre apprenants.
 */
class StudentController extends Controller
{
    /**
     * Page "Mes cours" — liste enrichie de toutes les formations de l'apprenant.
     */
    public function myCourses(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        $enrollments = Enrollment::with([
            'course' => function ($q) {
                $q->with(['instructor:id,name', 'category:id,name'])
                    ->withCount(['lessons', 'resources']);
            },
            'order:id,status,amount,paid_at',
        ])
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get();

        $courses = $enrollments->map(function (Enrollment $en) use ($user) {
            $course = $en->course;
            $lessonsTotal = $course?->lessons_count ?? 0;
            $lessonsDone = $lessonsTotal > 0
                ? UserProgress::where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->where('progress_percent', '>=', 100)
                    ->count()
                : 0;

            // "Soldé" = inscription ACTIVE ou COMPLETED ET (pas de order_id OU order.status = paid)
            $orderPaid = $en->order ? $en->order->status === 'paid' : true;
            $isSoldee = in_array($en->status, ['active', 'completed'], true) && $orderPaid;

            return [
                'id' => $en->course_id,
                'title' => $course?->title ?? 'Cours retiré',
                'instructor' => $course?->instructor?->name ?? 'SubnetCongo',
                'level' => $course?->level ?? 'all',
                'category' => $course?->category?->name ?? null,
                'image' => $course?->thumbnail_path ?? null,
                'progress' => (float) $en->progress_percent,
                'lessons_done' => $lessonsDone,
                'lessons_total' => $lessonsTotal,
                'resources_count' => $course?->resources_count ?? 0,
                'status' => $en->status,
                'is_soldee' => $isSoldee,
                'order_id' => $en->order_id,
                'order_paid_at' => $en->order?->paid_at?->toIso8601String(),
                'enrolled_at' => $en->enrolled_at?->toIso8601String(),
                'last_accessed' => $en->updated_at?->diffForHumans(),
                'next_lesson' => $this->getNextLessonSummary($en->course_id, $user->id),
            ];
        })->values();

        return Inertia::render('Student/MyCourses', [
            'courses' => $courses,
            'meta' => [
                'total' => $courses->count(),
                'completed' => $courses->where('progress', 100)->count(),
                'in_progress' => $courses->where('progress', '<', 100)->count(),
                'soldee' => $courses->where('is_soldee', true)->count(),
                'pending' => $courses->where('status', 'pending')->count(),
            ],
        ]);
    }

    /**
     * Page "Mes certificats" — n'inclut que les certificats actifs de l'apprenant.
     */
    public function certificates(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        $certificates = Certificate::with(['course:id,title,thumbnail_path'])
            ->where('user_id', $user->id)
            ->where('status', CertificateController::STATUS_ACTIVE)
            ->orderByDesc('issued_at')
            ->get()
            ->map(function (Certificate $cert) {
                return [
                    'id' => $cert->id,
                    'uuid' => $cert->uuid,
                    'course_id' => $cert->course_id,
                    'course_title' => $cert->course?->title ?? 'Cours retiré',
                    'course_image' => $cert->course?->thumbnail_path ?? null,
                    'score' => (float) $cert->score,
                    'issued_at' => $cert->issued_at?->format('d/m/Y'),
                    'certificate_code' => $cert->certificate_code,
                    'verification_hash' => $cert->verification_hash,
                    'download_url' => route('certificates.download', $cert->id),
                    'verify_url' => route('certificates.verify.public', $cert->verification_hash),
                ];
            })->values();

        return Inertia::render('Student/Certificates', [
            'certificates' => $certificates,
            'meta' => [
                'total' => $certificates->count(),
                'avg_score' => round((float) $certificates->avg('score'), 1),
            ],
        ]);
    }

    /**
     * Page "Ma progression" — heatmap par cours + activité quotidienne.
     */
    public function progress(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        // KPI globaux
        $enrollments = Enrollment::where('user_id', $user->id)->get();
        $totalSeconds = (int) UserProgress::where('user_id', $user->id)->sum('time_spent_seconds');

        // Progression par cours
        $perCourse = $enrollments->map(function (Enrollment $en) use ($user) {
            $lessonsTotal = Lesson::whereHas('module', fn ($q) => $q->where('course_id', $en->course_id))->count();
            $lessonsDone = UserProgress::where('user_id', $user->id)
                ->where('course_id', $en->course_id)
                ->where('progress_percent', '>=', 100)
                ->count();

            return [
                'course_id' => $en->course_id,
                'course_title' => $en->course?->title ?? 'Cours retiré',
                'progress' => (float) $en->progress_percent,
                'lessons_done' => $lessonsDone,
                'lessons_total' => $lessonsTotal,
                'avg_score' => (float) $en->avg_score,
                'status' => $en->status,
                'enrolled_at' => $en->enrolled_at?->toIso8601String(),
                'completed_at' => $en->completed_at?->toIso8601String(),
            ];
        })->values();

        // Historique d'activité (30 derniers jours)
        $activity = collect(range(29, 0))->map(function ($i) use ($user) {
            $date = now()->subDays($i)->startOfDay();
            $seconds = (int) UserProgress::where('user_id', $user->id)
                ->whereDate('last_accessed_at', $date)
                ->sum('time_spent_seconds');

            return [
                'date' => $date->format('Y-m-d'),
                'label' => $date->translatedFormat('d M'),
                'minutes' => round($seconds / 60),
            ];
        })->values();

        return Inertia::render('Student/Progress', [
            'kpis' => [
                'courses_total' => $enrollments->count(),
                'courses_completed' => $enrollments->where('progress', 100)->count(),
                'completion_rate' => round((float) $enrollments->avg('progress_percent'), 1),
                'hours_studied' => round($totalSeconds / 3600, 1),
                'certificates' => Certificate::where('user_id', $user->id)->where('status', 'Active')->count(),
            ],
            'perCourse' => $perCourse,
            'activity' => $activity,
        ]);
    }

    /**
     * Page player de leçon — charge le contexte cours + leçon + ressources/vidéos du cours.
     */
    public function lessonPlayer(Request $request, Course $course, Lesson $lesson)
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        // Vérification d'inscription (sécurité)
        $enrolled = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();
        if (! $enrolled && ! $lesson->is_free_preview) {
            abort(403, "Vous devez être inscrit à ce cours pour accéder à cette leçon.");
        }

        // Toutes les leçons du cours pour la navigation
        $lessons = Lesson::whereHas('module', fn ($q) => $q->where('course_id', $course->id))
            ->with('module:id,title,order')
            ->orderBy('order')
            ->get()
            ->map(fn (Lesson $l) => [
                'id' => $l->id,
                'title' => $l->title,
                'type' => $l->type,
                'lesson_type' => $l->lesson_type,
                'order' => $l->order,
                'module_title' => $l->module?->title,
                'duration' => $l->duration_minutes,
                'video_url' => $l->video_url,
                'document_url' => $l->document_url ? $this->getMediaUrl($l->document_url) : null,
                'document_title' => $l->document_title,
                'is_completed' => UserProgress::where('user_id', $user->id)
                    ->where('lesson_id', $l->id)
                    ->where('progress_percent', '>=', 100)
                    ->exists(),
            ])->values();

        // Progression existante sur cette leçon
        $progress = UserProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        // Module info pour affichage dans le player
        $module = $lesson->module;
        $moduleData = $module ? [
            'id' => $module->id,
            'title' => $module->title,
            'description' => $module->description,
            'text_content' => $module->text_content,
            'intro_video_url' => $module->intro_video_url ? $this->getMediaUrl($module->intro_video_url) : null,
            'intro_video_title' => $module->intro_video_title,
            'document_url' => $module->document_url ? $this->getMediaUrl($module->document_url) : null,
            'document_name' => $module->document_name,
            'has_quiz' => $module->has_quiz,
            'quiz_id' => $module->quiz_id,
            'quiz_weight' => $module->quiz_weight,
            'quiz_passing_score' => $module->quiz_passing_score,
            'quiz_attempts_allowed' => $module->quiz_attempts_allowed,
        ] : null;

        // Quiz attempts pour ce module (si le module a un quiz)
        $moduleQuizAttempts = null;
        if ($module && $module->has_quiz && $module->quiz_id) {
            $moduleQuizAttempts = \App\Models\ExamAttempt::where('user_id', $user->id)
                ->where('exam_id', $module->quiz_id)
                ->where('status', 'Completed')
                ->orderByDesc('created_at')
                ->get(['id', 'score_percentage', 'created_at', 'time_spent_seconds'])
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'score' => $a->score_percentage,
                    'score_out_of_20' => round(($a->score_percentage / 100) * 20, 2),
                    'date' => $a->created_at?->toIso8601String(),
                    'time_spent' => $a->time_spent_seconds,
                ])->values();
        }

        // Vidéos du cours (leçons de type vidéo)
        $videos = Lesson::whereHas('module', fn ($q) => $q->where('course_id', $course->id))
            ->where('type', Lesson::TYPE_VIDEO)
            ->orderBy('order')
            ->get(['id', 'title', 'video_url', 'duration_minutes'])
            ->map(fn (Lesson $l) => [
                'id' => $l->id,
                'lesson_id' => $l->id,
                'title' => $l->title,
                'url' => $l->video_url,
                'duration' => ($l->duration_minutes ?? 0) * 60,
            ])->values();

        // Matériaux / ressources téléchargeables du cours
        $materials = $course->resources()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Resource $r) => [
                'id' => $r->id,
                'title' => $r->title,
                'type' => $r->file_type ?? $r->mime_type,
                'size' => (int) $r->size,
                'mime_type' => $r->mime_type,
                'download_url' => route('student.resources.download', $r->id),
                'full_url' => $r->full_url,
            ])->values();

        $courseResources = [
            $course->id => [
                'materials' => $materials,
                'videos' => $videos,
            ],
        ];

        return Inertia::render('Student/LessonPlayer', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'instructor' => $course->instructor?->name ?? 'SubnetCongo',
                'image' => $course->thumbnail_path,
            ],
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'type' => $lesson->type,
                'lesson_type' => $lesson->lesson_type,
                'content' => $lesson->content,
                'video_url' => $lesson->video_url,
                'duration' => $lesson->duration_minutes,
                'attachments' => $lesson->attachment_paths ?? [],
                'document_url' => $lesson->document_url ? $this->getMediaUrl($lesson->document_url) : null,
                'document_title' => $lesson->document_title,
            ],
            'module' => $moduleData,
            'moduleQuizAttempts' => $moduleQuizAttempts,
            'lessons' => $lessons,
            'progress' => $progress ? [
                'percent' => (float) $progress->progress_percent,
                'time_spent' => $progress->time_spent_seconds,
                'last_accessed_at' => $progress->last_accessed_at?->toIso8601String(),
            ] : null,
            'courseResources' => $courseResources,
            'recentQuestions' => \App\Models\LessonQuestion::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->latest()
                ->take(3)
                ->get(['id', 'subject', 'status', 'priority', 'created_at'])
                ->map(fn ($q) => [
                    'id' => $q->id,
                    'subject' => $q->subject,
                    'status' => $q->status,
                    'priority' => $q->priority,
                    'created_at' => $q->created_at?->toIso8601String(),
                ])->values(),
        ]);
    }

    /**
     * Téléchargement sécurisé d'une ressource (vérifie l'inscription au cours parent).
     */
    public function resourceDownload(Request $request, Resource $resource)
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        // Sécurité : la ressource doit être active et l'utilisateur inscrit
        if (! $resource->is_active) {
            abort(404);
        }

        $canAccess = match ($resource->resourceable_type) {
            Course::class => Enrollment::where('user_id', $user->id)
                ->where('course_id', $resource->resourceable_id)
                ->exists(),
            Lesson::class => Enrollment::where('user_id', $user->id)
                ->whereIn('course_id', Lesson::where('id', $resource->resourceable_id)
                    ->pluck('module_id')
                    ->map(fn ($mid) => Module::where('id', $mid)->value('course_id'))
                    ->filter())
                ->exists(),
            default => false,
        };

        if (! $canAccess && ! $user->hasRole('admin')) {
            abort(403, "Vous n'avez pas accès à cette ressource.");
        }

        // Compteur de téléchargements (analytics)
        $resource->increment('download_count');

        if ($resource->is_external) {
            return redirect()->away($resource->path);
        }

        $disk = Storage::disk('media');
        if (! $disk->exists($resource->path)) {
            Log::warning("Ressource introuvable sur le disque", [
                'resource_id' => $resource->id,
                'path' => $resource->path,
            ]);
            abort(404, 'Fichier physique introuvable.');
        }

        $fullPath = $disk->path($resource->path);
        $filename = $resource->title ?: basename($resource->path);

        return response()->download($fullPath, $filename, [
            'Content-Type' => $resource->mime_type ?? 'application/octet-stream',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Auto-save progression vidéo (heartbeat frontend toutes les ~30s).
     */
    public function updateProgress(Request $request, Course $course, Lesson $lesson)
    {
        $user = $request->user();
        abort_unless($user->isEnrolledIn($course), 403, 'Vous n\'êtes pas inscrit à ce cours.');

        $data = $request->validate([
            'progress_percent' => 'required|numeric|min:0|max:100',
            'time_spent_seconds' => 'nullable|integer|min:0',
            'current_position_seconds' => 'nullable|integer|min:0',
        ]);

        $progress = UserProgress::firstOrNew([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);

        $progress->course_id = $course->id;
        $progress->progress_percent = min(100, max($progress->progress_percent ?? 0, $data['progress_percent']));
        $progress->time_spent_seconds = ($progress->time_spent_seconds ?? 0) + ($data['time_spent_seconds'] ?? 0);
        $progress->last_accessed_at = now();

        if ($progress->progress_percent >= 100) {
            $progress->completed_at = $progress->completed_at ?? now();
        }
        $progress->save();

        // Met à jour l'enrollment "last_lesson_id" si colonne dispo
        if (\Schema::hasColumn('enrollments', 'last_lesson_id')) {
            \DB::table('enrollments')
                ->where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->update(['last_lesson_id' => $lesson->id, 'updated_at' => now()]);
        }

        return response()->json([
            'ok' => true,
            'progress_percent' => (float) $progress->progress_percent,
            'time_spent_seconds' => $progress->time_spent_seconds,
            'is_completed' => $progress->progress_percent >= 100,
        ]);
    }

    /**
     * Marque manuellement une leçon comme terminée.
     */
    public function completeLesson(Request $request, Course $course, Lesson $lesson)
    {
        $user = $request->user();
        abort_unless($user->isEnrolledIn($course), 403, 'Vous n\'êtes pas inscrit à ce cours.');

        $progress = UserProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            [
                'course_id' => $course->id,
                'progress_percent' => 100,
                'completed_at' => now(),
                'last_accessed_at' => now(),
            ]
        );

        return back()->with('success', 'Leçon marquée comme terminée.');
    }

    /**
     * Helper : prochaine leçon non terminée pour un cours.
     */
    private function getNextLessonSummary(int $courseId, int $userId): ?array
    {
        $completedIds = UserProgress::where('user_id', $userId)
            ->where('progress_percent', '>=', 100)
            ->pluck('lesson_id');

        $next = Lesson::whereHas('module', fn ($q) => $q->where('course_id', $courseId))
            ->whereNotIn('id', $completedIds)
            ->orderBy('order')
            ->first(['id', 'title', 'type']);

        return $next ? [
            'id' => $next->id,
            'title' => $next->title,
            'type' => $next->type,
        ] : null;
    }

    /**
     * Génère l'URL complète pour un fichier sur le disque media.
     * Retourne l'URL absolue (APP_URL + /storage/media/...) pour les fichiers locaux,
     * ou l'URL directe si c'est déjà une URL externe.
     */
    private function getMediaUrl(string $path): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        // Utilise le disque 'media' pour générer l'URL
        $url = Storage::disk('media')->url($path);
        // S'assure que c'est une URL absolue
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $url = URL::to($url);
        }
        return $url;
    }
}