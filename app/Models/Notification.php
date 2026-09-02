<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification as BaseNotification;

class Notification extends BaseNotification
{
    protected $table = 'notifications';

    /**
     * Marquer comme lue.
     */
    public function markAsRead(): bool
    {
        if ($this->read_at) {
            return true;
        }
        $this->read_at = now();
        return $this->save();
    }

    /**
     * Type lisible (humain).
     */
    public function getHumanTypeAttribute(): string
    {
        $map = [
            'App\Notifications\CourseEnrolledNotification' => 'Inscription confirmée',
            'App\Notifications\CertificateIssuedNotification' => 'Certificat délivré',
            'App\Notifications\ExamReminderNotification' => 'Rappel d\'examen',
            'App\Notifications\LiveSessionStartingNotification' => 'Session live imminente',
            'App\Notifications\ForumReplyNotification' => 'Réponse sur le forum',
            'App\Notifications\PaymentReceivedNotification' => 'Paiement reçu',
            'App\Notifications\NewReviewNotification' => 'Nouvel avis',
        ];
        return $map[$this->type] ?? class_basename($this->type);
    }

    /**
     * Icône PrimeIcons dérivée du type.
     */
    public function getIconAttribute(): string
    {
        $data = $this->data ?? [];
        return $data['icon'] ?? $this->guessIconFromType();
    }

    protected function guessIconFromType(): string
    {
        return match (true) {
            str_contains($this->type, 'Course') => 'pi pi-book',
            str_contains($this->type, 'Certificate') => 'pi pi-verified',
            str_contains($this->type, 'Exam') => 'pi pi-pencil',
            str_contains($this->type, 'Live') => 'pi pi-video',
            str_contains($this->type, 'Forum') => 'pi pi-comments',
            str_contains($this->type, 'Payment') => 'pi pi-dollar',
            str_contains($this->type, 'Review') => 'pi pi-star',
            default => 'pi pi-bell',
        };
    }

    /**
     * Couleur pour badge.
     */
    public function getSeverityAttribute(): string
    {
        return $this->data['severity'] ?? 'info';
    }

    /**
     * URL de redirection.
     */
    public function getUrlAttribute(): ?string
    {
        return $this->data['url'] ?? null;
    }

    /**
     * Titre formaté.
     */
    public function getTitleAttribute(): string
    {
        return $this->data['title'] ?? $this->human_type;
    }

    /**
     * Message formaté.
     */
    public function getMessageAttribute(): string
    {
        return $this->data['message'] ?? '';
    }
}