<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification as BaseNotification;

/**
 * Notification envoyée lorsqu'un utilisateur s'inscrit à un cours.
 */
class CourseEnrolledNotification extends BaseNotification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public string $courseTitle,
        public int $courseId,
        public ?string $courseImage = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Inscription confirmée',
            'message' => "Vous êtes maintenant inscrit au cours « {$this->courseTitle} ».",
            'icon' => 'pi-book',
            'severity' => 'success',
            'url' => route('student.lesson-player', ['course' => $this->courseId, 'lesson' => 1]),
            'course_id' => $this->courseId,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}