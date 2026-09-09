<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Models\EmailTemplate;
use App\Services\Email\EmailTemplateService;
use Illuminate\Support\Facades\Log;

class SendPaymentReceivedEmail
{
    protected EmailTemplateService $emailService;

    public function __construct(EmailTemplateService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function handle(PaymentReceived $event): void
    {
        $order = $event->order;
        $user = $order->user;

        if (!$user) {
            Log::warning('SendPaymentReceivedEmail: Missing user');
            return;
        }

        $template = EmailTemplate::where('key', 'payment_received')
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return;
        }

        $this->emailService->sendTemplatedEmail('payment_received', $user, [
            'order_number' => $order->order_number ?? 'N/A',
            'amount' => $order->amount ?? 0,
            'currency' => $order->currency ?? 'USD',
            'order_url' => url('/orders/' . $order->id),
            'payment_date' => $order->created_at?->format('d/m/Y H:i'),
        ]);
    }
}
