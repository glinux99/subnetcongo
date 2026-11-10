<?php

namespace Database\Seeders;

use App\Models\GreetingMessage;
use Illuminate\Database\Seeder;

class GreetingMessageSeeder extends Seeder
{
    /**
     * Base de connaissances des messages d'accueil du tableau de bord.
     * Chaque utilisateur reçoit un message différent à chaque connexion.
     */
    public function run(): void
    {
        $messages = [
            // ─── GÉNÉRIQUES (tous rôles) ───
            ['all', 'all', 'Chaque jour est une opportunité d\'apprendre quelque chose de nouveau.'],
            ['all', 'all', 'La régularité est la clé de toute réussite. Continuez ainsi !'],
            ['all', 'all', 'Votre engagement aujourd\'hui construit vos compétences de demain.'],
            ['all', 'all', 'De petites progrès chaque jour mènent à de grands résultats.'],
            ['all', 'all', 'Restez concentré, la version la meilleure de vous-même est en construction.'],
            ['all', 'all', 'Chaque connexion est un pas de plus vers vos objectifs.'],
            ['all', 'all', 'Votre parcours compte autant que votre destination.'],
            ['all', 'all', 'Persistez, la maîtrise vient avec la pratique.'],
            ['all', 'morning', 'Bonne matinée ! Commencez la journée du bon pied.'],
            ['all', 'morning', 'Le matin est le moment idéal pour poser les fondations de votre journée.'],
            ['all', 'afternoon', 'Bon après-midi ! Prenez une courte pause si besoin, puis continuez.'],
            ['all', 'evening', 'Bonsoir ! Une belle journée se termine, préparez la suivante.'],
            ['all', 'evening', 'Le soir est parfait pour réviser ce que vous avez appris aujourd\'hui.'],

            // ─── ÉTUDIANTS ───
            ['student', 'all', 'Votre prochain certificat est plus proche que vous ne le pensez.'],
            ['student', 'all', 'Chaque leçon terminée vous rapproche de votre objectif.'],
            ['student', 'all', 'N\'hésitez pas à revoir les modules qui vous semblent difficiles.'],
            ['student', 'all', 'Vos progrès sont visibles : consultez vos statistiques pour le constater.'],
            ['student', 'all', 'Un examen à la fois : avancez méthodiquement, vous verrez la différence.'],
            ['student', 'all', 'La curiosité est votre meilleure alliée dans cette formation.'],
            ['student', 'all', 'Terminez ce qui est commencé : la satisfaction en vaut la peine.'],
            ['student', 'all', 'Vos instructeurs sont là pour vous guider, posez-leur vos questions.'],
            ['student', 'all', 'Revenez régulièrement : la constance bat l\'intensité ponctuelle.'],
            ['student', 'morning', 'Bonjour ! Aujourd\'hui est une nouvelle chance de progresser dans votre formation.'],
            ['student', 'evening', 'Bonsoir ! Révisez rapidement vos notes du jour avant de vous reposer.'],

            // ─── INSTRUCTEURS ───
            ['instructor', 'all', 'Vos apprenants comptent sur votre expertise : inspirons-les !'],
            ['instructor', 'all', 'Un bon cours peut changer une carrière. Le vôtre le peut.'],
            ['instructor', 'all', 'Pensez à répondre aux questions de vos élèves pour les fidéliser.'],
            ['instructor', 'all', 'Mettez à jour vos contenus régulièrement pour garder l\'engagement.'],
            ['instructor', 'all', 'Chaque retour de vos apprenants est une opportunité d\'améliorer.'],
            ['instructor', 'all', 'Votre pédagogie façonne l\'avenir des professionnels de demain.'],
            ['instructor', 'all', 'Nouveaux contenus en vue ? Vos apprenants vous attendent.'],
            ['instructor', 'morning', 'Bonjour ! Une journée passionnante vous attend avec vos apprenants.'],
            ['instructor', 'evening', 'Bonsoir ! Merci pour votre engagement envers la réussite de vos élèves.'],

            // ─── ADMINS ───
            ['admin', 'all', 'La plateforme tourne grâce à votre vigilance : bon travail.'],
            ['admin', 'all', 'Chaque inscription validée est un apprenant de plus qui progresse.'],
            ['admin', 'all', 'Surveillez les métriques : elles racontent l\'histoire de la plateforme.'],
            ['admin', 'all', 'Un système bien administré grandit de manière saine.'],
            ['admin', 'all', 'Pensez aux utilisateurs qui comptent sur vos décisions rapides.'],
            ['admin', 'all', 'L\'excellence opérationnelle commence par l\'attention aux détails.'],
            ['admin', 'all', 'Votre pilotage guide la croissance de SubnetCongo Academy.'],
            ['admin', 'morning', 'Bonjour ! Les indicateurs du jour vous attendent dans le tableau de bord.'],
            ['admin', 'evening', 'Bonsoir ! Une journée de pilotage accomplie : consultez le bilan.'],

            // ─── STAFF (support, finance, modération) ───
            ['staff', 'all', 'Votre réactivité fait la différence pour nos utilisateurs.'],
            ['staff', 'all', 'Chaque demande traitée rapidement renforce la confiance.'],
            ['staff', 'all', 'Soyez la solution que nos utilisateurs recherchent.'],
            ['staff', 'all', 'Votre travail en coulisses est essentiel à l\'expérience globale.'],
            ['staff', 'all', 'La qualité du support détermine la fidélité des apprenants.'],
            ['staff', 'all', 'Empathie et efficacité : les deux piliers de votre mission.'],
            ['staff', 'morning', 'Bonjour ! Votre équipe compte sur vous pour une journée efficace.'],
            ['staff', 'evening', 'Bonsoir ! Merci pour votre disponibilité au service de nos utilisateurs.'],
        ];

        foreach ($messages as $i => [$audience, $period, $message]) {
            GreetingMessage::updateOrCreate(
                ['audience' => $audience, 'period' => $period, 'message' => $message],
                ['is_active' => true, 'sort_order' => $i]
            );
        }
    }
}
