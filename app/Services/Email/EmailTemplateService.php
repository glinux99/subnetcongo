<?php

namespace App\Services\Email;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailTemplateService
{
    public function sendTemplatedEmail(string $templateKey, User $user, array $additionalData = [], array $bcc = []): bool
    {
        return $this->sendTemplatedEmailTo($templateKey, $user->email, $user->name, array_merge([
            'student_name' => $user->name,
            'student_email' => $user->email,
        ], $additionalData), $bcc);
    }

    /**
     * Envoie un email templated à un destinataire quelconque (utilisateur ou non).
     *
     * @param array<int,string> $bcc Adresses en copie cachée
     */
    public function sendTemplatedEmailTo(string $templateKey, string $email, string $name = '', array $data = [], array $bcc = []): bool
    {
        $template = EmailTemplate::where('key', $templateKey)->where('is_active', true)->first();

        if (!$template) {
            Log::warning("EmailTemplateService: Template not found or inactive", ['key' => $templateKey]);
            return false;
        }

        // Fusionne les BCC configurés sur le template avec ceux passés explicitement
        $bcc = array_values(array_unique(array_merge(
            $template->bcc_emails ?? [],
            $bcc
        )));

        $data = array_merge([
            'student_name' => $name,
            'student_email' => $email,
        ], $data);

        $replaced = $template->replaceVariables($data);

        try {
            Mail::html($replaced['content'], function ($message) use ($email, $name, $replaced, $bcc) {
                $message->to($email, $name ?: null)
                        ->subject($replaced['subject']);
                if (!empty($bcc)) {
                    $message->bcc($bcc);
                }
            });

            Log::info("EmailTemplateService: Email sent successfully", [
                'template' => $templateKey,
                'email' => $email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("EmailTemplateService: Failed to send email", [
                'template' => $templateKey,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendCertificateGenerated(User $user, array $certificateData): bool
    {
        return $this->sendTemplatedEmail('certificate_generated', $user, [
            'course_name' => $certificateData['course_name'] ?? '',
            'certificate_url' => $certificateData['certificate_url'] ?? url('/certificates'),
            'certificate_code' => $certificateData['certificate_code'] ?? '',
            'completion_date' => $certificateData['completion_date'] ?? now()->format('d/m/Y'),
        ]);
    }

    public function sendEnrollmentConfirmed(User $user, array $enrollmentData): bool
    {
        return $this->sendTemplatedEmail('enrollment_confirmed', $user, [
            'course_name' => $enrollmentData['course_name'] ?? '',
            'course_url' => $enrollmentData['course_url'] ?? url('/courses'),
            'enrollment_date' => $enrollmentData['enrollment_date'] ?? now()->format('d/m/Y'),
            'expiry_date' => $enrollmentData['expiry_date'] ?? '',
        ]);
    }

    public function sendCourseAdded(User $user, array $courseData): bool
    {
        return $this->sendTemplatedEmail('course_added', $user, [
            'course_name' => $courseData['course_name'] ?? '',
            'course_description' => $courseData['course_description'] ?? '',
            'course_url' => $courseData['course_url'] ?? url('/courses'),
            'added_date' => $courseData['added_date'] ?? now()->format('d/m/Y'),
        ]);
    }

    public function sendWelcome(User $user): bool
    {
        return $this->sendTemplatedEmail('welcome', $user, [
            'dashboard_url' => url('/dashboard'),
            'welcome_date' => now()->format('d/m/Y'),
        ]);
    }
}
