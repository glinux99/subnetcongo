<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class EnrollmentRejectedNotification extends Notification implements ShouldBroadcast
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
        $courseTitle = $this->enrollment->course?->title ?? '—';
        
        return [
            'type' => 'enrollment_rejected',
            'title' => 'Inscription refusée',
            'message' => "Votre inscription au cours « {$courseTitle} » a été refusée. Motif : {$this->reason}",
            'icon' => 'pi-times-circle',
            'severity' => 'danger',
            'url' => route('student.my-enrollments'),
            'enrollment_id' => $this->enrollment->id,
            'course_id' => $this->enrollment->course_id,
            'course_title' => $courseTitle,
            'reason' => $this->reason,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
