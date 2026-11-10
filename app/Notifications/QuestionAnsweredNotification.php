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
        $instructorName = $this->question->instructor?->name ?? 'Un instructeur';

        return [
            'type' => 'question_answered',
            'title' => 'Votre question a reçu une réponse',
            'message' => "{$instructorName} a répondu à votre question « {$this->question->subject} ». Consultez la réponse maintenant.",
            'icon' => 'pi-check-square',
            'severity' => 'success',
            'url' => route('student.questions.index'),
            'question_id' => $this->question->id,
            'course_id' => $this->question->course_id,
            'subject' => $this->question->subject,
            'instructor_id' => $this->question->instructor_id,
            'preview' => \Str::limit($this->question->answer ?? '', 120),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
