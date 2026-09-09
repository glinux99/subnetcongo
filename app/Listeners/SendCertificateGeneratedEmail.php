<?php

namespace App\Listeners;

use App\Events\CertificateGenerated;
use App\Models\EmailTemplate;
use App\Services\Email\EmailTemplateService;
use Illuminate\Support\Facades\Log;

class SendCertificateGeneratedEmail
{
    protected EmailTemplateService $emailService;

    public function __construct(EmailTemplateService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function handle(CertificateGenerated $event): void
    {
        $certificate = $event->certificate;
        $user = $certificate->user;
        $course = $certificate->course;

        if (!$user || !$course) {
            Log::warning('SendCertificateGeneratedEmail: Missing user or course');
            return;
        }

        $template = EmailTemplate::where('key', 'certificate_generated')
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return;
        }

        $this->emailService->sendCertificateGenerated($user, [
            'course_name' => $course->title,
            'certificate_url' => url('/certificates/' . $certificate->id),
            'certificate_code' => $certificate->certificate_code,
            'completion_date' => $certificate->issued_at?->format('d/m/Y'),
        ]);
    }
}
