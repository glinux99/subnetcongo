<?php

namespace App\Listeners;

use App\Events\LiveSessionReminder;
use App\Models\EmailTemplate;
use App\Models\Enrollment;
use App\Services\Email\EmailTemplateService;
use Illuminate\Support\Facades\Log;

class SendLiveSessionReminderEmail
{
    protected EmailTemplateService $emailService;

    public function __construct(EmailTemplateService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function handle(LiveSessionReminder $event): void
    {
        $liveSession = $event->liveSession;

        $template = EmailTemplate::where('key', 'live_session_reminder')
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return;
        }

        // Get all enrolled students
        $enrollments = Enrollment::where('course_id', $liveSession->course_id)
            ->where('status', 'active')
            ->with('user')
            ->get();

        foreach ($enrollments as $enrollment) {
            if ($enrollment->user) {
                $this->emailService->sendTemplatedEmail('live_session_reminder', $enrollment->user, [
                    'session_title' => $liveSession->title ?? 'Session Live',
                    'course_name' => $liveSession->course?->title ?? 'Cours',
                    'session_url' => url('/live-sessions/' . $liveSession->id),
                    'start_time' => $liveSession->start_at?->format('d/m/Y H:i'),
                    'minutes_until' => $event->liveSession->start_at?->diffInMinutes(now()),
                ]);
            }
        }
    }
}
