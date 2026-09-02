<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification as BaseNotification;

class CertificateIssuedNotification extends BaseNotification implements ShouldBroadcastNow
{
    use Queueable;

    public function __construct(
        public string $courseTitle,
        public int $certificateId,
        public float $score,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Certificat délivré !',
            'message' => "Félicitations ! Vous avez obtenu le certificat « {$this->courseTitle} » avec un score de {$this->score}%.",
            'icon' => 'pi-verified',
            'severity' => 'success',
            'url' => route('student.certificates'),
            'certificate_id' => $this->certificateId,
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}