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
        $askerName = $this->question->user?->name ?? 'Un apprenant';

        return [
            'type' => 'lesson_question',
            'title' => 'Nouvelle question d\'un apprenant',
            'message' => "{$askerName} a posé une nouvelle question : « {$this->question->subject} ». Une réponse est attendue.",
            'icon' => 'pi-question-circle',
            'severity' => 'info',
            'url' => route('instructor.questions.index'),
            'question_id' => $this->question->id,
            'subject' => $this->question->subject,
            'course_id' => $this->question->course_id,
            'lesson_id' => $this->question->lesson_id,
            'asker_id' => $this->question->user_id,
            'priority' => $this->question->priority,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
