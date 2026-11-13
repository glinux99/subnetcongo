<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification as BaseNotification;

class LiveSessionStartedNotification extends BaseNotification implements ShouldBroadcastNow
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
            'title' => 'Session live en cours',
            'message' => "« {$this->sessionTitle} » a commencé. Rejoignez la salle maintenant !",
            'icon' => 'pi-video',
            'severity' => 'danger',
            'url' => $this->joinUrl ?: route('live-sessions.mine'),
            'session_id' => $this->sessionId,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
