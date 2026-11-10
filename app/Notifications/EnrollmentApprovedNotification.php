<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class EnrollmentApprovedNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public Enrollment $enrollment)
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
            'type' => 'enrollment_approved',
            'title' => 'Inscription approuvée !',
            'message' => "Votre inscription au cours « {$courseTitle} » a été approuvée. Vous pouvez commencer immédiatement.",
            'icon' => 'pi-check-circle',
            'severity' => 'success',
            'url' => route('student.my-courses'),
            'enrollment_id' => $this->enrollment->id,
            'course_id' => $this->enrollment->course_id,
            'course_title' => $courseTitle,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
