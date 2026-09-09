<?php

namespace App\Listeners;

use App\Events\EnrollmentRevoked;
use App\Models\EmailTemplate;
use App\Services\Email\EmailTemplateService;
use Illuminate\Support\Facades\Log;

class SendEnrollmentRevokedEmail
{
    protected EmailTemplateService $emailService;

    public function __construct(EmailTemplateService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function handle(EnrollmentRevoked $event): void
    {
        $enrollment = $event->enrollment;
        $user = $enrollment->user;
        $course = $enrollment->course;

        if (!$user) {
            Log::warning('SendEnrollmentRevokedEmail: Missing user');
            return;
        }

        $template = EmailTemplate::where('key', 'enrollment_revoked')
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return;
        }

        $this->emailService->sendTemplatedEmail('enrollment_revoked', $user, [
            'course_name' => $course->title ?? 'Cours',
            'course_url' => url('/courses/' . ($course->id ?? '')),
            'revocation_reason' => $event->reason,
            'revocation_date' => now()->format('d/m/Y'),
        ]);
    }
}
