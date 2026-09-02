<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification as BaseNotification;

class LiveSessionStartingNotification extends BaseNotification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public string $sessionTitle,
        public int $sessionId,
        public ?string $joinUrl = null,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Session live imminente',
            'message' => "« {$this->sessionTitle} » commence bientôt. Rejoignez la salle !",
            'icon' => 'pi-video',
            'severity' => 'info',
            'url' => $this->joinUrl ?: route('live-sessions.show', $this->sessionId),
            'session_id' => $this->sessionId,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}