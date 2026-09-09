<?php

namespace App\Listeners;

use App\Events\EnrollmentConfirmed;
use App\Models\EmailTemplate;
use App\Services\Email\EmailTemplateService;
use Illuminate\Support\Facades\Log;

class SendEnrollmentConfirmationEmail
{
    protected EmailTemplateService $emailService;

    public function __construct(EmailTemplateService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function handle(EnrollmentConfirmed $event): void
    {
        $enrollment = $event->enrollment;
        $user = $enrollment->user;
        $course = $enrollment->course;

        if (!$user || !$course) {
            Log::warning('SendEnrollmentConfirmationEmail: Missing user or course', [
                'enrollment_id' => $enrollment->id,
            ]);
            return;
        }

        // Check if template is active
        $template = EmailTemplate::where('key', 'enrollment_confirmed')
            ->where('is_active', true)
            ->first();

        if (!$template) {
            Log::info('SendEnrollmentConfirmationEmail: Template not active or not found', [
                'enrollment_id' => $enrollment->id,
            ]);
            return;
        }

        // Send the email
        $this->emailService->sendEnrollmentConfirmed($user, [
            'course_name' => $course->title,
            'course_url' => url('/courses/' . $course->id),
            'enrollment_date' => $enrollment->enrolled_at?->format('d/m/Y') ?? now()->format('d/m/Y'),
            'expiry_date' => $enrollment->expires_at?->format('d/m/Y'),
        ]);

        Log::info('SendEnrollmentConfirmationEmail: Email sent successfully', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
        ]);
    }
}
