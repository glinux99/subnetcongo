<?php

namespace App\Listeners;

use App\Events\SupportTicketCreated;
use App\Models\EmailTemplate;
use App\Services\Email\EmailTemplateService;
use Illuminate\Support\Facades\Log;

class SendSupportTicketCreatedEmail
{
    protected EmailTemplateService $emailService;

    public function __construct(EmailTemplateService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function handle(SupportTicketCreated $event): void
    {
        $user = $event->user;
        $ticketData = $event->ticketData;

        if (!$user) {
            Log::warning('SendSupportTicketCreatedEmail: Missing user');
            return;
        }

        $template = EmailTemplate::where('key', 'support_ticket_created')
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return;
        }

        $this->emailService->sendTemplatedEmail('support_ticket_created', $user, [
            'ticket_id' => $ticketData['id'] ?? 'N/A',
            'ticket_subject' => $ticketData['subject'] ?? 'Support',
            'ticket_url' => url('/support/' . ($ticketData['id'] ?? '')),
            'created_at' => now()->format('d/m/Y H:i'),
        ]);
    }
}
