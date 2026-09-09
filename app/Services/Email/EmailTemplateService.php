<?php

namespace App\Services\Email;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailTemplateService
{
    public function sendTemplatedEmail(string $templateKey, User $user, array $additionalData = []): bool
    {
        $template = EmailTemplate::where('key', $templateKey)->where('is_active', true)->first();

        if (!$template) {
            Log::warning("EmailTemplateService: Template not found or inactive", ['key' => $templateKey]);
            return false;
        }

        $data = array_merge([
            'student_name' => $user->name,
            'student_email' => $user->email,
        ], $additionalData);

        $replaced = $template->replaceVariables($data);

        try {
            Mail::html($replaced['content'], function ($message) use ($user, $replaced, $template) {
                $message->to($user->email, $user->name)
                        ->subject($replaced['subject']);
            });

            Log::info("EmailTemplateService: Email sent successfully", [
                'template' => $templateKey,
                'user' => $user->id,
                'email' => $user->email,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("EmailTemplateService: Failed to send email", [
                'template' => $templateKey,
                'user' => $user->id,
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
