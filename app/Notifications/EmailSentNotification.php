<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification as BaseNotification;

/**
 * Notification envoyée à l'expéditeur lorsqu'il envoie un email depuis la plateforme.
 */
class EmailSentNotification extends BaseNotification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public array $recipients,
        public string $subject,
        public string $sentAt,
        public ?int $sentEmailId = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        $toList = implode(', ', $this->recipients);

        return [
            'type' => 'email_sent',
            'title' => 'Email envoyé avec succès',
            'message' => "Vous avez envoyé l'email « {$this->subject} » à {$toList} le {$this->sentAt}.",
            'icon' => 'pi-send',
            'severity' => 'success',
            'url' => $this->sentEmailId ? route('admin.emails.sent.show', $this->sentEmailId) : route('admin.emails.sent'),
            'recipients' => $this->recipients,
            'subject' => $this->subject,
            'sent_at' => $this->sentAt,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
