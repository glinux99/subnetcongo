<?php

namespace App\Mail;

use App\Models\EmailCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public EmailCampaign $campaign;

    public ?string $testEmail;

    public function __construct(EmailCampaign $campaign, ?string $testEmail = null)
    {
        $this->campaign = $campaign;
        $this->testEmail = $testEmail;
    }

    public function envelope(): Envelope
    {
        $fromEmail = $this->campaign->from_email ?: config('mail.from.address');
        $fromName = $this->campaign->from_name ?: config('mail.from.name');
        $subject = $this->campaign->subject;

        if ($this->testEmail) {
            $subject = '[TEST] ' . $subject;
        }

        return new Envelope(
            from: new Address($fromEmail, $fromName),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->campaign->body,
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
