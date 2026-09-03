<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class EnrollmentRevokedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public Enrollment $enrollment, public string $reason)
    {
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'enrollment_revoked',
            'enrollment_id' => $this->enrollment->id,
            'course_id' => $this->enrollment->course_id,
            'course_title' => $this->enrollment->course?->title,
            'reason' => $this->reason,
            'message' => 'Votre accès au cours "' . ($this->enrollment->course?->title ?? '—') . '" a été révoqué. Motif : ' . $this->reason,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
