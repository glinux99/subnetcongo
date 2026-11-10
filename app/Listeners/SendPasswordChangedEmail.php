<?php

namespace App\Listeners;

use App\Services\Email\EmailTemplateService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Log;

class SendPasswordChangedEmail
{
    public function __construct(protected EmailTemplateService $emailService)
    {
    }

    public function handle(PasswordReset $event): void
    {
        $user = $event->user;

        if (!$user) {
            return;
        }

        try {
            $this->emailService->sendTemplatedEmail('password_changed', $user, [
                'changed_at' => now()->format('d/m/Y à H:i'),
            ]);
        } catch (\Throwable $e) {
            Log::warning('SendPasswordChangedEmail failed: ' . $e->getMessage());
        }
    }
}
