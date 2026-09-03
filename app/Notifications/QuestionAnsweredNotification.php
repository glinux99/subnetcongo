<?php

namespace App\Notifications;

use App\Models\LessonQuestion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class QuestionAnsweredNotification extends Notification implements ShouldBroadcast
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
            'type' => 'question_answered',
            'question_id' => $this->question->id,
            'course_id' => $this->question->course_id,
            'subject' => $this->question->subject,
            'instructor_id' => $this->question->instructor_id,
            'preview' => \Str::limit($this->question->answer ?? '', 120),
            'message' => 'Votre question a reçu une réponse : ' . $this->question->subject,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
