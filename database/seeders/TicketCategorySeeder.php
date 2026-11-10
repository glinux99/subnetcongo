<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Accès aux cours',       'slug' => 'acces-cours',        'description' => 'Problème d\'inscription, cours verrouillé, accès refusé', 'color' => '#3b82f6', 'is_active' => true],
            ['name' => 'Paiement & Facturation', 'slug' => 'paiement',          'description' => 'Paiement mobile money, virement, remboursement, reçu',      'color' => '#10b981', 'is_active' => true],
            ['name' => 'Bug technique',          'slug' => 'bug-technique',      'description' => 'Erreur d\'affichage, vidéo qui ne joue pas, page bloquée',     'color' => '#ef4444', 'is_active' => true],
            ['name' => 'Compte & Sécurité',      'slug' => 'compte-securite',    'description' => 'Mot de passe, email de connexion, authentification',          'color' => '#8b5cf6', 'is_active' => true],
            ['name' => 'Contenu pédagogique',    'slug' => 'contenu',            'description' => 'Question sur un cours, ressources manquantes, correction',       'color' => '#f59e0b', 'is_active' => true],
            ['name' => 'Examens & Certificats',  'slug' => 'examens-certificats','description' => 'Passage d\'examen, résultat, téléchargement de certificat',       'color' => '#06b6d4', 'is_active' => true],
            ['name' => 'Autre demande',          'slug' => 'autre',              'description' => 'Toute autre question ou suggestion',                            'color' => '#64748b', 'is_active' => true],
        ];

        foreach ($categories as $cat) {
            TicketCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                $cat
            );
        }
    }
}
