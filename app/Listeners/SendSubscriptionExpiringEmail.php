<?php

namespace App\Listeners;

use App\Events\SubscriptionExpiring;
use App\Models\EmailTemplate;
use App\Services\Email\EmailTemplateService;
use Illuminate\Support\Facades\Log;

class SendSubscriptionExpiringEmail
{
    protected EmailTemplateService $emailService;

    public function __construct(EmailTemplateService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function handle(SubscriptionExpiring $event): void
    {
        $enrollment = $event->enrollment;
        $user = $enrollment->user;
        $course = $enrollment->course;

        if (!$user) {
            Log::warning('SendSubscriptionExpiringEmail: Missing user');
            return;
        }

        $template = EmailTemplate::where('key', 'subscription_expiring')
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return;
        }

        $this->emailService->sendTemplatedEmail('subscription_expiring', $user, [
            'course_name' => $course->title ?? 'Cours',
            'course_url' => url('/courses/' . ($course->id ?? '')),
            'days_remaining' => $event->daysRemaining,
            'expiry_date' => $enrollment->expires_at?->format('d/m/Y'),
            'renew_url' => url('/enrollments/' . $enrollment->id . '/renew'),
        ]);
    }
}
