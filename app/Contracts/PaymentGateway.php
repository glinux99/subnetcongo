<?php

namespace App\Contracts;

/**
 * Interface commune pour toutes les passerelles de paiement.
 * Chaque provider (Stripe, PayPal, Mobile Money, ...) implémente cette interface.
 */
interface PaymentGateway
{
    /**
     * Initialise une intention de paiement.
     * @param float $amount Montant en devise principale
     * @param string $currency Code ISO (USD, EUR, CDF, XAF, ...)
     * @param array $metadata Données additionnelles (order_id, user_id, course_id, ...)
     * @return array ['reference' => string, 'redirect_url' => string|null, 'client_secret' => string|null, ...]
     */
    public function createIntent(float $amount, string $currency, array $metadata = []): array;

    /**
     * Confirme/capture le paiement après redirection.
     * @return array Statut: 'succeeded', 'pending', 'failed' + détails
     */
    public function capture(string $reference): array;

    /**
     * Rembourse un paiement.
     */
    public function refund(string $reference, ?float $amount = null): array;

    /**
     * Vérifie la signature d'un webhook entrant.
     * @return bool true si le webhook est authentique
     */
    public function verifyWebhook(array $payload, string $signature): bool;

    /**
     * Traite un webhook et retourne l'événement normalisé.
     * @return array|null null si l'événement n'est pas reconnu
     */
    public function parseWebhook(array $payload): ?array;

    /**
     * Identifiant unique du provider.
     */
    public function getProviderName(): string;
}