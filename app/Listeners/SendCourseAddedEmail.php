<?php

namespace App\Listeners;

use App\Events\CourseAdded;
use App\Models\EmailTemplate;
use App\Services\Email\EmailTemplateService;
use Illuminate\Support\Facades\Log;

class SendCourseAddedEmail
{
    protected EmailTemplateService $emailService;

    public function __construct(EmailTemplateService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function handle(CourseAdded $event): void
    {
        $user = $event->user;
        $course = $event->course;

        if (!$user) {
            Log::warning('SendCourseAddedEmail: Missing user');
            return;
        }

        $template = EmailTemplate::where('key', 'course_added')
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return;
        }

        $this->emailService->sendCourseAdded($user, [
            'course_name' => $course->title ?? 'Cours',
            'course_description' => $course->description ?? '',
            'course_url' => url('/courses/' . ($course->id ?? '')),
            'added_date' => now()->format('d/m/Y'),
        ]);
    }
}
