<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Détecte le rôle principal de l'utilisateur pour adapter le message.
     */
    private function roleKey(): string
    {
        if ($this->user->hasRole('superadmin') || $this->user->hasRole('admin')) {
            return 'admin';
        }
        if ($this->user->hasRole('instructor') || $this->user->hasRole('formateur') || $this->user->hasRole('teacher')) {
            return 'instructor';
        }
        if ($this->user->hasAnyRole(['moderator', 'support-agent', 'finance-manager'])) {
            return 'staff';
        }
        return 'student';
    }

    public function envelope(): Envelope
    {
        $subjects = [
            'admin'       => 'Bienvenue dans l\'équipe SubnetCongo Academy',
            'instructor'  => 'Bienvenue dans l\'espace instructeur SubnetCongo',
            'staff'       => 'Bienvenue dans l\'équipe SubnetCongo Academy',
            'student'     => 'Bienvenue sur SubnetCongo Academy, ' . $this->user->name . ' !',
        ];

        return new Envelope(
            subject: $subjects[$this->roleKey()],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'userName'    => $this->user->name,
                'userEmail'   => $this->user->email,
                'roleKey'     => $this->roleKey(),
                'dashboardUrl' => url('/dashboard'),
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
