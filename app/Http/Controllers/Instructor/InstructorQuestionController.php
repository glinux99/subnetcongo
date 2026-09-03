<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LessonQuestion;
use App\Notifications\QuestionAnsweredNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class InstructorQuestionController extends Controller
{
    private const MAX_FILE_SIZE = 10240;

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

    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->input('status', 'all');

        $courseIds = $user->hasAnyRole(['admin', 'superadmin'])
            ? Course::pluck('id')
            : Course::where('instructor_id', $user->id)->orWhere('created_by', $user->id)->pluck('id');

        $query = LessonQuestion::with(['user:id,name,avatar,email', 'course:id,title,thumbnail_path', 'lesson:id,title', 'replies.user'])
            ->whereNull('parent_id')
            ->whereIn('course_id', $courseIds)
            ->latest();

        if (in_array($status, ['open', 'answered', 'resolved', 'closed'], true)) {
            $query->where('status', $status);
        }

        $questions = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => LessonQuestion::whereIn('course_id', $courseIds)->whereNull('parent_id')->count(),
            'open' => LessonQuestion::whereIn('course_id', $courseIds)->whereNull('parent_id')->where('status', LessonQuestion::STATUS_OPEN)->count(),
            'answered' => LessonQuestion::whereIn('course_id', $courseIds)->whereNull('parent_id')->where('status', LessonQuestion::STATUS_ANSWERED)->count(),
        ];

        return Inertia::render('Instructor/Questions/Index', [
            'questions' => $questions,
            'stats' => $stats,
            'filters' => ['status' => $status],
        ]);
    }

    public function answer(Request $request, LessonQuestion $question)
    {
        $data = $request->validate([
            'answer' => 'required|string|min:5|max:5000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:' . self::MAX_FILE_SIZE,
        ]);

        $attachments = $this->handleUploads($request, 'attachments', 'questions/answers');

        $question->update([
            'answer' => $data['answer'],
            'answer_attachments' => $attachments,
            'answered_at' => now(),
            'status' => LessonQuestion::STATUS_ANSWERED,
            'instructor_id' => Auth::id(),
        ]);

        // Notifier l'étudiant
        try {
            $question->user?->notify(new QuestionAnsweredNotification($question));
        } catch (\Throwable $e) {
            Log::warning('Notify student failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Réponse envoyée à l\'apprenant.');
    }

    public function close(LessonQuestion $question)
    {
        $question->update(['status' => LessonQuestion::STATUS_CLOSED]);
        return back()->with('success', 'Question fermée.');
    }

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

            $mime = $file->getMimeType() ?? $file->getClientMimeType();
            if (! in_array($mime, self::ALLOWED_MIMES, true)) {
                Log::warning('Instructor attachment rejected: bad mime ' . $mime);
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
