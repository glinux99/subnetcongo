<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonQuestion;
use App\Notifications\NewQuestionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class QuestionController extends Controller
{
    /** Taille max par fichier : 10 Mo */
    private const MAX_FILE_SIZE = 10240;

    /** Mime types autorisés */
    private const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'application/pdf',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain', 'text/csv', 'text/markdown',
        'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed',
        'application/json', 'application/xml',
    ];

    /**
     * Liste des questions de l'étudiant (toutes ses questions + leurs réponses).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->input('status', 'all');

        $query = LessonQuestion::with(['course:id,title,thumbnail_path', 'lesson:id,title', 'instructor:id,name,avatar', 'replies.user', 'replies.attachments'])
            ->where('user_id', $user->id)
            ->whereNull('parent_id')
            ->latest();

        if (in_array($status, ['open', 'answered', 'resolved', 'closed'], true)) {
            $query->where('status', $status);
        }

        $questions = $query->paginate(10)->withQueryString();

        $stats = [
            'total' => LessonQuestion::where('user_id', $user->id)->whereNull('parent_id')->count(),
            'open' => LessonQuestion::where('user_id', $user->id)->whereNull('parent_id')->where('status', LessonQuestion::STATUS_OPEN)->count(),
            'answered' => LessonQuestion::where('user_id', $user->id)->whereNull('parent_id')->where('status', LessonQuestion::STATUS_ANSWERED)->count(),
            'resolved' => LessonQuestion::where('user_id', $user->id)->whereNull('parent_id')->where('status', LessonQuestion::STATUS_RESOLVED)->count(),
        ];

        $courses = Course::whereIn('id', $user->enrollments()->pluck('course_id'))->select('id', 'title')->get();

        return Inertia::render('Student/Questions', [
            'questions' => $questions,
            'stats' => $stats,
            'courses' => $courses,
            'filters' => ['status' => $status],
        ]);
    }

    /**
     * Pose une nouvelle question sur un cours / une leçon.
     * Redirige TOUJOURS en back (pas de JSON 200), avec message flash de succès / erreur.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'lesson_id' => 'nullable|exists:lessons,id',
            'subject' => 'required|string|min:5|max:255',
            'body' => 'required|string|min:10|max:5000',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:' . self::MAX_FILE_SIZE,
        ]);

        $user = Auth::user();
        $course = Course::findOrFail($data['course_id']);

        // L'étudiant doit être inscrit au cours
        abort_unless($user->isEnrolledIn($course), 403, 'Vous devez être inscrit à ce cours pour poser une question.');

        // Upload des pièces jointes (capture d'écran, fichiers…)
        $attachments = $this->handleUploads($request, 'attachments', 'questions');

        $question = LessonQuestion::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'lesson_id' => $data['lesson_id'] ?? null,
            'instructor_id' => $course->instructor_id ?? $course->created_by ?? null,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'attachments' => $attachments,
            'priority' => $data['priority'] ?? LessonQuestion::PRIORITY_NORMAL,
            'status' => LessonQuestion::STATUS_OPEN,
        ]);

        // Notifier l'instructeur (best-effort)
        if ($question->instructor_id && $question->instructor_id !== $user->id) {
            try {
                $instructor = \App\Models\User::find($question->instructor_id);
                if ($instructor) {
                    $instructor->notify(new NewQuestionNotification($question));
                }
            } catch (\Throwable $e) {
                Log::warning('Notify instructor failed: ' . $e->getMessage());
            }
        }

        // Toujours redirection back avec flash, jamais de JSON 200.
        return back()->with('success', 'Votre question a été envoyée à l\'instructeur.');
    }

    /**
     * Affiche le détail d'une question (avec réponses).
     */
    public function show(LessonQuestion $question)
    {
        abort_unless($question->user_id === Auth::id() || Auth::user()->hasAnyRole(['admin', 'instructor', 'superadmin']), 403);

        $question->load(['course:id,title', 'lesson:id,title', 'instructor:id,name,avatar', 'user:id,name,avatar', 'replies.user']);

        return Inertia::render('Student/QuestionDetail', [
            'question' => $question,
        ]);
    }

    /**
     * L'étudiant répond à la suite de sa question (follow-up).
     */
    public function reply(Request $request, LessonQuestion $question)
    {
        abort_unless($question->user_id === Auth::id(), 403);

        $data = $request->validate([
            'body' => 'required|string|min:2|max:3000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:' . self::MAX_FILE_SIZE,
        ]);

        $attachments = $this->handleUploads($request, 'attachments', 'questions/replies');

        $reply = LessonQuestion::create([
            'user_id' => Auth::id(),
            'course_id' => $question->course_id,
            'lesson_id' => $question->lesson_id,
            'instructor_id' => $question->instructor_id,
            'parent_id' => $question->id,
            'subject' => 'Re: ' . $question->subject,
            'body' => $data['body'],
            'attachments' => $attachments,
            'status' => LessonQuestion::STATUS_OPEN,
        ]);

        // Si la question était résolue, on la rouvre
        if ($question->status === LessonQuestion::STATUS_RESOLVED) {
            $question->update(['status' => LessonQuestion::STATUS_OPEN]);
        }

        return back()->with('success', 'Réponse envoyée.');
    }

    /**
     * L'étudiant marque sa question comme résolue.
     */
    public function resolve(LessonQuestion $question)
    {
        abort_unless($question->user_id === Auth::id(), 403);
        $question->update(['status' => LessonQuestion::STATUS_RESOLVED]);

        return back()->with('success', 'Question marquée comme résolue.');
    }

    /**
     * Upload + validation des pièces jointes.
     * Retourne un tableau [{name, original_name, mime, size, url}, ...].
     */
    private function handleUploads(Request $request, string $field, string $folder): array
    {
        if (! $request->hasFile($field)) {
            return [];
        }

        $files = $request->file($field);
        if (! is_array($files)) {
            $files = [$files];
        }

        $uploaded = [];
        foreach ($files as $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            // Filtre MIME (autorise ce qui est déclaré, sans doublon)
            $mime = $file->getMimeType() ?? $file->getClientMimeType();
            if (! in_array($mime, self::ALLOWED_MIMES, true)) {
                Log::warning('Attachment rejected: bad mime ' . $mime);
                continue;
            }

            $ext = $file->getClientOriginalExtension() ?: $file->extension();
            $name = Str::random(20) . ($ext ? '.' . $ext : '');
            $path = $file->storeAs($folder, $name, 'public');

            $uploaded[] = [
                'name' => $name,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $mime,
                'size' => $file->getSize(),
                'url' => Storage::url($path),
                'is_image' => str_starts_with((string) $mime, 'image/'),
            ];
        }

        return $uploaded;
    }
}
