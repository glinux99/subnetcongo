<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\PaymentGatewaySetting;

/**
 * Implémentation squelette Stripe (à connecter avec stripe/stripe-php en prod).
 * Pour le moment, fournit une simulation complète et sécurisée.
 */
class StripeGateway implements PaymentGateway
{
    public function __construct(protected ?PaymentGatewaySetting $settings = null) {}

    public function getProviderName(): string { return 'stripe'; }

    public function createIntent(float $amount, string $currency, array $metadata = []): array
    {
        // En prod : \Stripe\PaymentIntent::create([...])
        $reference = 'pi_stripe_' . bin2hex(random_bytes(12));

        return [
            'reference' => $reference,
            'client_secret' => 'pi_secret_' . bin2hex(random_bytes(12)),
            'redirect_url' => null,
            'amount' => $amount,
            'currency' => strtoupper($currency),
            'metadata' => $metadata,
        ];
    }

    public function capture(string $reference): array
    {
        return [
            'status' => 'succeeded',
            'reference' => $reference,
            'captured_at' => now()->toIso8601String(),
        ];
    }

    public function refund(string $reference, ?float $amount = null): array
    {
        return [
            'status' => 'refunded',
            'reference' => $reference,
            'amount' => $amount,
            'refunded_at' => now()->toIso8601String(),
        ];
    }

    public function verifyWebhook(array $payload, string $signature): bool
    {
        if (! $this->settings?->credentials) return false;
        $secret = $this->settings->credentials['webhook_secret'] ?? '';
        if (! $secret) return false;

        $expected = hash_hmac('sha256', json_encode($payload), $secret);
        return hash_equals($expected, $signature);
    }

    public function parseWebhook(array $payload): ?array
    {
        $type = $payload['type'] ?? null;
        if (! in_array($type, ['payment_intent.succeeded', 'payment_intent.payment_failed', 'charge.refunded'])) {
            return null;
        }
        return [
            'type' => $type,
            'reference' => $payload['data']['object']['id'] ?? null,
            'amount' => ($payload['data']['object']['amount'] ?? 0) / 100,
            'currency' => strtoupper($payload['data']['object']['currency'] ?? 'usd'),
            'status' => str_replace('payment_intent.', '', $type),
        ];
    }
}