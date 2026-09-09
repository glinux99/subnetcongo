<?php

namespace App\Listeners;

use App\Events\EnrollmentRejected;
use App\Models\EmailTemplate;
use App\Services\Email\EmailTemplateService;
use Illuminate\Support\Facades\Log;

class SendEnrollmentRejectedEmail
{
    protected EmailTemplateService $emailService;

    public function __construct(EmailTemplateService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function handle(EnrollmentRejected $event): void
    {
        $enrollment = $event->enrollment;
        $user = $enrollment->user;
        $course = $enrollment->course;

        if (!$user) {
            Log::warning('SendEnrollmentRejectedEmail: Missing user');
            return;
        }

        $template = EmailTemplate::where('key', 'enrollment_rejected')
            ->where('is_active', true)
            ->first();

        if (!$template) {
            Log::info('SendEnrollmentRejectedEmail: Template not active');
            return;
        }

        $this->emailService->sendTemplatedEmail('enrollment_rejected', $user, [
            'course_name' => $course->title ?? 'Cours',
            'course_url' => url('/courses/' . ($course->id ?? '')),
            'reason' => $event->reason,
            'rejection_date' => now()->format('d/m/Y'),
        ]);
    }
}
