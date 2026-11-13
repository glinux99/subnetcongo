<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LiveSessionReminderNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public string $sessionTitle,
        public ?string $courseTitle,
        public string $instructorName,
        public string $startAtLabel,
        public string $joinUrl,
        public int $sessionId,
        public int $minutesBefore,
    ) {}

    public function via($notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return ['mail'];
        }

        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Rappel : votre session live commence bientôt')
            ->view('emails.live-session-reminder', [
                'recipientName' => $notifiable instanceof AnonymousNotifiable
                    ? null
                    : ($notifiable->name ?? null),
                'sessionTitle' => $this->sessionTitle,
                'courseTitle' => $this->courseTitle,
                'instructorName' => $this->instructorName,
                'startAtLabel' => $this->startAtLabel,
                'joinUrl' => $this->joinUrl,
                'minutesBefore' => $this->minutesBefore,
            ]);
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Session live imminente',
            'message' => "« {$this->sessionTitle} » commence dans {$this->minutesBefore} minute(s). Rejoignez la salle !",
            'icon' => 'pi-video',
            'severity' => 'info',
            'url' => $this->joinUrl,
            'session_id' => $this->sessionId,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
