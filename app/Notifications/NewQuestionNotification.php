<?php

namespace App\Notifications;

use App\Models\LessonQuestion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewQuestionNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public LessonQuestion $question)
    {
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'lesson_question',
            'question_id' => $this->question->id,
            'subject' => $this->question->subject,
            'course_id' => $this->question->course_id,
            'lesson_id' => $this->question->lesson_id,
            'asker_id' => $this->question->user_id,
            'priority' => $this->question->priority,
            'message' => 'Nouvelle question d\'un apprenant : ' . $this->question->subject,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
