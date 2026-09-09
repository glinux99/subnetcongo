<?php

namespace App\Listeners;

use App\Events\PaymentFailed;
use App\Models\EmailTemplate;
use App\Services\Email\EmailTemplateService;
use Illuminate\Support\Facades\Log;

class SendPaymentFailedEmail
{
    protected EmailTemplateService $emailService;

    public function __construct(EmailTemplateService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function handle(PaymentFailed $event): void
    {
        $order = $event->order;
        $user = $order->user;

        if (!$user) {
            Log::warning('SendPaymentFailedEmail: Missing user');
            return;
        }

        $template = EmailTemplate::where('key', 'payment_failed')
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return;
        }

        $this->emailService->sendTemplatedEmail('payment_failed', $user, [
            'order_number' => $order->order_number ?? 'N/A',
            'amount' => $order->amount ?? 0,
            'currency' => $order->currency ?? 'USD',
            'failure_reason' => $event->reason,
            'retry_url' => url('/checkout/' . $order->id),
            'payment_date' => now()->format('d/m/Y H:i'),
        ]);
    }
}
