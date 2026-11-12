<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        Language::firstOrCreate(['code' => 'fr'],['name' => 'Français', 'is_active' => true, 'is_default' => true]);
        Language::firstOrCreate(['code' => 'en'],['name' => 'English', 'is_active' => true, 'is_default' => false]);
        Language::firstOrCreate(['code' => 'de'],['name' => 'Deutsch', 'is_active' => true, 'is_default' => false]);
        Language::firstOrCreate(['code' => 'sw'],['name' => 'Kiswahili', 'is_active' => true, 'is_default' => false]);
        Language::firstOrCreate(['code' => 'ln'],['name' => 'Lingála', 'is_active' => true, 'is_default' => false]);
    }
}
