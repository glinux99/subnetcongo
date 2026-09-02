<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\PaymentGatewaySetting;
use InvalidArgumentException;

/**
 * Orchestrateur central pour les paiements.
 * Choisit le bon provider selon le pays / préférence / disponibilité.
 */
class PaymentService
{
    protected array $gateways = [];

    public function __construct()
    {
        // Les gateways sont enregistrés à la demande via gateway()
    }

    /**
     * Récupère ou instancie le provider demandé.
     */
    public function gateway(string $provider): PaymentGateway
    {
        $settings = PaymentGatewaySetting::where('provider', $provider)->first();
        if (! $settings) {
            throw new InvalidArgumentException("Provider de paiement inconnu : {$provider}");
        }

        return match ($provider) {
            'stripe' => new StripeGateway($settings),
            'paypal' => new PayPalGateway($settings),
            'orange_money', 'mpesa', 'airtel_money' => new MobileMoneyGateway($settings, $provider),
            default => throw new InvalidArgumentException("Provider non implémenté : {$provider}"),
        };
    }

    /**
     * Liste les providers activés.
     */
    public function enabledProviders(): \Illuminate\Support\Collection
    {
        return PaymentGatewaySetting::where('enabled', true)->get();
    }

    /**
     * Recommandation automatique selon pays et montant.
     */
    public function recommendProvider(string $countryCode, float $amount): string
    {
        // Afrique Centrale / de l'Est : Mobile Money d'abord
        $mmCountries = ['CD', 'CG', 'GA', 'CM', 'CF', 'TD', 'KE', 'TZ', 'UG', 'RW', 'BI', 'CI', 'SN', 'ML'];
        if (in_array(strtoupper($countryCode), $mmCountries)) {
            $mm = PaymentGatewaySetting::whereIn('provider', ['orange_money', 'mpesa', 'airtel_money'])
                ->where('enabled', true)->first();
            if ($mm) return $mm->provider;
        }

        // Europe / Americas : Stripe ou PayPal
        $stripe = PaymentGatewaySetting::where('provider', 'stripe')->where('enabled', true)->first();
        if ($stripe) return 'stripe';
        $paypal = PaymentGatewaySetting::where('provider', 'paypal')->where('enabled', true)->first();
        if ($paypal) return 'paypal';

        // Fallback premier provider activé
        $first = PaymentGatewaySetting::where('enabled', true)->first();
        return $first?->provider ?? 'stripe';
    }
}