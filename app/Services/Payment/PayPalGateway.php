<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\PaymentGatewaySetting;

class PayPalGateway implements PaymentGateway
{
    public function __construct(protected ?PaymentGatewaySetting $settings = null) {}

    public function getProviderName(): string { return 'paypal'; }

    public function createIntent(float $amount, string $currency, array $metadata = []): array
    {
        $reference = 'pi_paypal_' . bin2hex(random_bytes(12));
        return [
            'reference' => $reference,
            'redirect_url' => "https://www.sandbox.paypal.com/checkoutnow?token={$reference}",
            'client_secret' => null,
            'amount' => $amount,
            'currency' => strtoupper($currency),
            'metadata' => $metadata,
        ];
    }

    public function capture(string $reference): array
    {
        return ['status' => 'succeeded', 'reference' => $reference, 'captured_at' => now()->toIso8601String()];
    }

    public function refund(string $reference, ?float $amount = null): array
    {
        return ['status' => 'refunded', 'reference' => $reference, 'amount' => $amount, 'refunded_at' => now()->toIso8601String()];
    }

    public function verifyWebhook(array $payload, string $signature): bool
    {
        // En prod : vérification via l'API PayPal VerifyWebhookSignature
        $secret = $this->settings?->credentials['webhook_id'] ?? '';
        return ! empty($secret) && ! empty($signature);
    }

    public function parseWebhook(array $payload): ?array
    {
        $event = $payload['event_type'] ?? null;
        if (! $event) return null;
        $resource = $payload['resource'] ?? [];
        return [
            'type' => $event,
            'reference' => $resource['id'] ?? null,
            'amount' => ($resource['amount']['value'] ?? 0),
            'currency' => strtoupper($resource['amount']['currency_code'] ?? 'USD'),
            'status' => $resource['status'] ?? 'unknown',
        ];
    }
}