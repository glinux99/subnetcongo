<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification as BaseNotification;

/**
 * Notification de base avec titre et message complets.
 */
class BaseAppNotification extends BaseNotification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public string $icon = 'pi-bell',
        public string $severity = 'info',
        public ?string $url = null,
        public array $data = []
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return array_merge([
            'title' => $this->title,
            'message' => $this->message,
            'icon' => $this->icon,
            'severity' => $this->severity,
            'url' => $this->url,
        ], $this->data);
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
