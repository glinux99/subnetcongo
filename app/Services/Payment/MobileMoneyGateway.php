<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\PaymentGatewaySetting;

/**
 * Implémentation squelette pour Mobile Money (Orange Money / M-Pesa / Airtel Money).
 * En production, chaque provider a son API REST spécifique — ce service abstrait la différence.
 */
class MobileMoneyGateway implements PaymentGateway
{
    public function __construct(
        protected ?PaymentGatewaySetting $settings = null,
        protected string $providerName = 'orange_money',
    ) {}

    public function getProviderName(): string { return $this->providerName; }

    public function createIntent(float $amount, string $currency, array $metadata = []): array
    {
        $reference = 'pi_mm_' . bin2hex(random_bytes(10));
        // En prod : appel API Orange/M-Pesa/Airtel pour initier le push USSD
        return [
            'reference' => $reference,
            'redirect_url' => null,
            'client_secret' => null,
            'amount' => $amount,
            'currency' => strtoupper($currency),
            'metadata' => array_merge($metadata, [
                'phone_required' => true,
                'instructions' => "Vous allez recevoir un push USSD sur votre téléphone. Composez votre code PIN pour confirmer le paiement de {$amount} {$currency}.",
            ]),
        ];
    }

    public function capture(string $reference): array
    {
        // Mobile Money est asynchrone — on interroge le statut régulièrement
        return ['status' => 'pending', 'reference' => $reference];
    }

    public function refund(string $reference, ?float $amount = null): array
    {
        return ['status' => 'refunded', 'reference' => $reference, 'amount' => $amount];
    }

    public function verifyWebhook(array $payload, string $signature): bool
    {
        $secret = $this->settings?->credentials['webhook_secret'] ?? '';
        return ! empty($secret) && ! empty($signature);
    }

    public function parseWebhook(array $payload): ?array
    {
        $status = $payload['status'] ?? null;
        if (! in_array($status, ['SUCCESS', 'FAILED', 'PENDING'])) return null;
        return [
            'type' => "transaction.{$status}",
            'reference' => $payload['transaction_id'] ?? null,
            'amount' => $payload['amount'] ?? 0,
            'currency' => $payload['currency'] ?? 'XAF',
            'status' => strtolower($status),
        ];
    }
}