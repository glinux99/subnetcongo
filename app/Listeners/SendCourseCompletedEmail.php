<?php

namespace App\Listeners;

use App\Events\CourseCompleted;
use App\Models\EmailTemplate;
use App\Services\Email\EmailTemplateService;
use Illuminate\Support\Facades\Log;

class SendCourseCompletedEmail
{
    protected EmailTemplateService $emailService;

    public function __construct(EmailTemplateService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function handle(CourseCompleted $event): void
    {
        $enrollment = $event->enrollment;
        $user = $enrollment->user;
        $course = $enrollment->course;

        if (!$user || !$course) {
            Log::warning('SendCourseCompletedEmail: Missing user or course');
            return;
        }

        $template = EmailTemplate::where('key', 'course_completed')
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return;
        }

        $this->emailService->sendTemplatedEmail('course_completed', $user, [
            'course_name' => $course->title ?? 'Cours',
            'course_url' => url('/courses/' . $course->id),
            'completion_date' => now()->format('d/m/Y'),
            'progress' => $enrollment->progress_percent ?? 100,
        ]);
    }
}
