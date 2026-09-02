<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification as BaseNotification;

class ExamReminderNotification extends BaseNotification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public string $examTitle,
        public int $examId,
        public string $startsAt,
        public int $minutesLeft,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Examen imminent',
            'message' => "L'examen « {$this->examTitle} » commence dans {$this->minutesLeft} min.",
            'icon' => 'pi-pencil',
            'severity' => 'warn',
            'url' => route('exams.take', $this->examId),
            'exam_id' => $this->examId,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}