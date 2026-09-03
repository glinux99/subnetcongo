<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewEnrollmentRequestNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public Enrollment $enrollment, public array $meta = [])
    {
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        $studentName = $this->enrollment->user?->name ?? 'Un apprenant';
        $courseTitle = $this->enrollment->course?->title ?? 'un cours';
        $method = $this->meta['payment_method'] ?? null;
        $txId   = $this->meta['transaction_id'] ?? null;
        $phone  = $this->meta['phone_number'] ?? null;
        $note   = $this->meta['note'] ?? null;

        $methodLabel = $method ? strtoupper(str_replace('_', ' ', $method)) : null;

        $lines = [];
        $lines[] = "📩 Nouvelle demande d'inscription";
        $lines[] = "{$studentName} souhaite rejoindre « {$courseTitle} »";
        if ($methodLabel) $lines[] = "Méthode annoncée : {$methodLabel}";
        if ($phone)       $lines[] = "Téléphone déclaré : {$phone}";
        if ($txId)        $lines[] = "ID transaction fournie : {$txId}";
        if ($note)        $lines[] = "Note : « {$note} »";
        $lines[] = "— Statut actuel : en attente de votre validation.";

        return [
            'type'            => 'enrollment_request',
            'severity'        => 'info',
            'icon'            => 'pi-user-plus',
            'enrollment_id'   => $this->enrollment->id,
            'course_id'       => $this->enrollment->course_id,
            'course_title'    => $courseTitle,
            'student_id'      => $this->enrollment->user_id,
            'student_name'    => $studentName,
            'payment_method'  => $method,
            'phone_number'    => $phone,
            'transaction_id'  => $txId,
            'note'            => $note,
            'url'             => route('admin.enrollments.show', $this->enrollment->id, false),
            'title'           => "Demande d'inscription à valider",
            'message'         => implode("\n", $lines),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
