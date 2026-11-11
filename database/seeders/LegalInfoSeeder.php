<?php

namespace Database\Seeders;

use App\Models\LegalInfo;
use Illuminate\Database\Seeder;

class LegalInfoSeeder extends Seeder
{
    public function run(): void
    {
        $info = [
            'company_name' => 'SubNet Congo Sarl',
            'legal_form' => 'Sarl',
            'rccm' => 'CD/GOM/RCCM/24-B-00042',
            'ifu' => null,
            'tax_id' => 'A2217118G',
            'nif' => '19-J6100-N37879J',
            'ai' => '1018177400',
            'country' => 'République Démocratique du Congo',
            'province' => 'Nord-Kivu',
            'city' => 'Goma',
            'address_line' => 'Av. du Touriste 001, Q. Les Volcans, Goma',
            'po_box' => null,
            'website' => 'www.subnetcongo.net',
            'capital_social' => null,
            'tva_rate' => null,
            'vat_number' => null,
            'currency' => 'CDF',
        ];

        $existing = LegalInfo::first();
        if ($existing) {
            $existing->fill($info)->save();
        } else {
            LegalInfo::create($info);
        }

        $this->command->info('Informations légales SubNet Congo appliquées (RCCM, NIF, N° d’impôt, CNSS, siège)');
    }
}
