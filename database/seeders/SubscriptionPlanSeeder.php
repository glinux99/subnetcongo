<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => 'starter',
                'name' => 'Starter',
                'description' => 'Pour découvrir la plateforme : accès aux cours essentiels et support communautaire.',
                'price' => 999,
                'currency' => 'USD',
                'interval' => 'month',
                'duration_months' => 1,
                'features' => [
                    'Accès à 5 cours au choix',
                    'Support par email',
                    'Certificat de fin de cours',
                ],
                'course_ids' => [],
                'is_active' => true,
            ],
            [
                'code' => 'professional',
                'name' => 'Professional',
                'description' => 'Accès complet au catalogue avec certificats illimités et support prioritaire.',
                'price' => 1999,
                'currency' => 'USD',
                'interval' => 'month',
                'duration_months' => 1,
                'features' => [
                    'Accès illimité à tous les cours',
                    'Certificats illimités',
                    'Support prioritaire',
                    'Parcours de formation recommandés',
                ],
                'course_ids' => [],
                'is_active' => true,
            ],
            [
                'code' => 'enterprise',
                'name' => 'Enterprise',
                'description' => 'Pour les équipes : tout Professional + API, gestionnaire dédié et formation sur mesure. Facturation annuelle.',
                'price' => 19999,
                'currency' => 'USD',
                'interval' => 'year',
                'duration_months' => 12,
                'features' => [
                    'Tout le plan Professional',
                    'Accès API',
                    'Gestionnaire de compte dédié',
                    'Formation sur mesure',
                    'Tableau de bord équipe',
                ],
                'course_ids' => [],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['code' => $plan['code']], $plan);
        }

        $this->command->info('3 plans d\'abonnement créés/actualisés (Starter, Professional, Enterprise).');
    }
}
