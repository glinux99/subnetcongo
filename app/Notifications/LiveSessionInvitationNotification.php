<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;

class LiveSessionInvitationNotification extends BaseNotification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public string $sessionTitle,
        public ?string $courseTitle,
        public string $instructorName,
        public string $startAtLabel,
        public string $joinUrl,
        public int $sessionId,
    ) {}

    public function via($notifiable): array
    {
        // Un destinataire externe (adresse e-mail seule) ne peut recevoir
        // que l'e-mail : pas de notification en base ni de broadcast.
        if ($notifiable instanceof AnonymousNotifiable) {
            return ['mail'];
        }

        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Invitation à une session live : ' . $this->sessionTitle)
            ->view('emails.live-session-invitation', [
                'recipientName' => $notifiable instanceof AnonymousNotifiable
                    ? null
                    : ($notifiable->name ?? null),
                'sessionTitle' => $this->sessionTitle,
                'courseTitle' => $this->courseTitle,
                'instructorName' => $this->instructorName,
                'startAtLabel' => $this->startAtLabel,
                'joinUrl' => $this->joinUrl,
            ]);
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Invitation à une session live',
            'message' => "Vous êtes invité(e) à « {$this->sessionTitle} » le {$this->startAtLabel}.",
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
