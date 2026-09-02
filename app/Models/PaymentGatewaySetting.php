<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewaySetting extends Model
{
    protected $table = 'payment_gateway_settings';
    protected $fillable = ['provider', 'enabled', 'sandbox', 'credentials', 'settings'];

    protected $casts = [
        'enabled' => 'boolean',
        'sandbox' => 'boolean',
        'credentials' => 'array',
        'settings' => 'array',
    ];

    /**
     * Liste des providers supportés.
     */
    public const PROVIDERS = [
        'stripe' => 'Stripe',
        'paypal' => 'PayPal',
        'orange_money' => 'Orange Money',
        'mpesa' => 'M-Pesa',
        'airtel_money' => 'Airtel Money',
    ];

    public static function getEnabled()
    {
        return self::where('enabled', true)->get()->keyBy('provider');
    }
}