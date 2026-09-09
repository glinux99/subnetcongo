<?php

namespace App\Listeners;

use App\Events\WelcomeUser;
use App\Models\EmailTemplate;
use App\Services\Email\EmailTemplateService;
use Illuminate\Support\Facades\Log;

class SendWelcomeEmail
{
    protected EmailTemplateService $emailService;

    public function __construct(EmailTemplateService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function handle(WelcomeUser $event): void
    {
        $user = $event->user;

        $template = EmailTemplate::where('key', 'welcome')
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return;
        }

        $this->emailService->sendWelcome($user);
    }
}
