<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTestSeeder extends Seeder
{
    public function run(): void
    {
        // S'assurer que tous les templates par défaut sont chargés
        EmailTemplate::seedDefaults();

        $this->command->info('Templates d\'emails par défaut chargés avec succès.');
        $this->command->info('Templates actifs : ' . EmailTemplate::where('is_active', true)->count());
        $this->command->info('Templates inactifs : ' . EmailTemplate::where('is_active', false)->count());
    }
}
