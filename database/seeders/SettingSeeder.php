<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [['key' => 'site_name', 'value' => 'Mon E-learning', 'type' => 'string', 'group' => 'general'],['key' => 'contact_email', 'value' => 'contact@elearning.com', 'type' => 'string', 'group' => 'general'],['key' => 'currency_symbol', 'value' => '€', 'type' => 'string', 'group' => 'payment'],['key' => 'allow_registration', 'value' => 'true', 'type' => 'boolean', 'group' => 'general'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
