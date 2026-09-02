<?php

namespace Database\Seeders;

use App\Models\PaymentGatewaySetting;
use Illuminate\Database\Seeder;

class PaymentGatewaySettingSeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            ['provider' => 'stripe',     'enabled' => false, 'sandbox' => true,  'credentials' => ['publishable_key' => 'pk_test_xxx', 'secret_key' => 'sk_test_xxx'], 'settings' => ['webhook_secret' => 'whsec_xxx']],
            ['provider' => 'paypal',     'enabled' => false, 'sandbox' => true,  'credentials' => ['client_id' => '', 'client_secret' => ''], 'settings' => []],
            ['provider' => 'orange_money','enabled' => true, 'sandbox' => true,  'credentials' => ['client_id' => '', 'client_secret' => ''], 'settings' => ['merchant_key' => '']],
            ['provider' => 'mpesa',      'enabled' => true,  'sandbox' => true,  'credentials' => ['consumer_key' => '', 'consumer_secret' => ''], 'settings' => ['shortcode' => '174379', 'passkey' => '']],
            ['provider' => 'airtel_money','enabled' => true, 'sandbox' => true,  'credentials' => ['client_id' => '', 'client_secret' => ''], 'settings' => []],
        ];

        foreach ($gateways as $g) {
            PaymentGatewaySetting::updateOrCreate(
                ['provider' => $g['provider']],
                $g
            );
        }
    }
}
