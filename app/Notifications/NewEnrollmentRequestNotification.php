<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewEnrollmentRequestNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public Enrollment $enrollment, public array $meta = [])
    {
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'enrollment_request',
            'enrollment_id' => $this->enrollment->id,
            'course_id' => $this->enrollment->course_id,
            'course_title' => $this->enrollment->course?->title,
            'student_id' => $this->enrollment->user_id,
            'student_name' => $this->enrollment->user?->name,
            'payment_method' => $this->meta['payment_method'] ?? null,
            'phone_number' => $this->meta['phone_number'] ?? null,
            'message' => 'Nouvelle demande d\'inscription de ' . ($this->enrollment->user?->name ?? '—') . ' pour le cours « ' . ($this->enrollment->course?->title ?? '—') . ' ».',
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
