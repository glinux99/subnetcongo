<?php

namespace Database\Seeders;

use App\Models\Realization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RealizationsSeeder extends Seeder
{
    public function run(): void
    {
        $realizations = [
            [
                'title' => 'Visite du représentant de MOTOROLA AFRIQUE à SubNet Congo',
                'summary' => 'Visite officielle du représentant régional de Motorola Afrique chez SubNet Congo Sarl.',
                'description' => "MOTOROLA AFRIQUE a visité les installations de SubNet Congo à Goma pour évaluer nos projets réseau et télécommunication. Cette rencontre a permis de renforcer le partenariat stratégique dans les solutions de radiocommunication Motorola HF, VHF et UHF.",
                'image' => '/media/realizations/real-p15-1.jpg', 'client' => 'MOTOROLA AFRIQUE', 'location' => 'Goma, Nord-Kivu', 'completed_at' => '2024-11-15', 'category' => 'Partenariats', 'tags' => ['MOTOROLA', 'Réseaux', 'Télécoms'],
            ],
            [
                'title' => 'Formation en conception et bases de données',
                'summary' => 'Formation pratique en conception et administration de bases de données.',
                'description' => "Formation complète en conception, administration et gestion de bases de données. Les participants ont appris à créer et optimiser leurs systèmes d'information pour la gestion de projets.",
                'image' => '/media/realizations/real-p16-1.jpg', 'client' => 'Particuliers & Entreprises', 'location' => 'Goma', 'completed_at' => '2025-02-20', 'category' => 'Formation & Certification', 'tags' => ['Base de données', 'SQL', 'Formation'],
            ],
            [
                'title' => 'Étude de faisabilité pour un micro-central à Béni',
                'summary' => 'Étude technique sur le terrain pour la construction d’un micro-central énergétique à Béni.',
                'description' => "Décente sur terrain des ingénieurs réalisateurs, formateurs et étudiants formés par SubNet Congo pour une étude de pré-faisabilité en vue de la construction d'un micro-central énergétique à Béni (territoire du Nord-Kivu).",
                'image' => '/media/realizations/real-p17-1.jpg', 'client' => 'Micro-Central de Béni', 'location' => 'Béni, Nord-Kivu', 'completed_at' => '2025-05-10', 'category' => 'Électricité & Énergie', 'tags' => ['Énergie', 'Micro-central', 'Étude'],
            ],
            [
                'title' => 'Visite des installations de NURU — Parc solaire',
                'summary' => 'Visite guidée des installations de NURU et son parc solaire photovoltaïque.',
                'description' => "Visite guidée des ingénieurs de SubNet Congo au sein de l'entreprise NURU pour découvrir son parc solaire photovoltaïque et se familiariser avec les installations en énergie propre.",
                'image' => '/media/realizations/real-p18-1.jpg', 'client' => 'NURU', 'location' => 'Goma, Nord-Kivu', 'completed_at' => '2025-06-22', 'category' => 'Électricité & Énergie', 'tags' => ['Solaire', 'Énergie', 'NURU'],
            ],
            [
                'title' => 'Assemblage d’antennes VSAT',
                'summary' => 'Pratique d’assemblage et de configuration d’antennes VSAT.',
                'description' => "Formation pratique sur l'assemblage et l'installation d'antennes VSAT (connexion Internet via satellite) après une formation en maintenance d'antennes. Montage, orientation et test de connexion.",
                'image' => '/media/realizations/real-p19-1.jpg', 'client' => 'SubNet Congo Lab', 'location' => 'Goma', 'completed_at' => '2025-03-18', 'category' => 'Réseaux & Télécoms', 'tags' => ['VSAT', 'Satellite', 'Installation'],
            ],
            [
                'title' => 'Installation de caméras de surveillance',
                'summary' => 'Mise en place de systèmes de vidéosurveillance pour particuliers et entreprises.',
                'description' => "Installation de systèmes de caméras de surveillance (CCTV) pour maisons, commerces et entreprises. Projet réalisé en collaboration avec des stagiaires de SubNet Congo.",
                'image' => '/media/realizations/real-p20-1.jpg', 'client' => 'Particuliers & Entreprises', 'location' => 'Goma & environs', 'completed_at' => '2025-04-15', 'category' => 'Sécurité & Surveillance', 'tags' => ['CCTV', 'Surveillance', 'Sécurité'],
            ],
            [
                'title' => 'Remise officielle des brevets — Formation réseau & télécom',
                'summary' => 'Cérémonie de remise des brevets après la formation réseau et télécom.',
                'description' => "Remise officielle des brevets aux participants à l'issue d'une formation certifiante en réseau et télécommunication. Les techniciens formés rejoignent nos équipes terrain.",
                'image' => '/media/realizations/real-p21-1.jpg', 'client' => 'SubNet Congo — Formation', 'location' => 'Goma', 'completed_at' => '2025-07-05', 'category' => 'Formation & Certification', 'tags' => ['Briefing', 'Brevets', 'Réseau'],
            ],
            [
                'title' => 'Mission terrain avec nos partenaires',
                'summary' => 'Équipe d’ingénieurs techniciens en mission terrain auprès de nos partenaires.',
                'description' => "Les ingénieurs techniciens de SubNet Congo et leurs stagiaires académiques en mission sur le terrain chez nos partenaires. Dépannage, installation et suivi d'infrastructures.",
                'image' => '/media/realizations/real-p22-1.jpg', 'client' => 'Partenaires SubNet', 'location' => 'Nord-Kivu', 'completed_at' => '2025-08-12', 'category' => 'Partenariats', 'tags' => ['Terrain', 'Équipe', 'Partenaires'],
            ],
            [
                'title' => 'Surveillance vidéo — projets réalisés par nos techniciennes',
                'summary' => 'Mise en place de surveillance vidéo par nos techniciennes formées.',
                'description' => "Nos techniciennes ont mis en place des systèmes de surveillance vidéo pour des clients à Goma et dans les zones environnantes. Projet complet : étude, installation, configuration et formation du client.",
                'image' => '/media/realizations/real-p24-1.jpg', 'client' => 'Divers clients', 'location' => 'Goma', 'completed_at' => '2025-09-20', 'category' => 'Sécurité & Surveillance', 'tags' => ['Vidéosurveillance', 'Techniciennes', 'CCTV'],
            ],
            [
                'title' => 'Formation KOBO Collect & SPSS',
                'summary' => 'Formation en collecte et traitement de données KOBO Collect et SPSS.',
                'description' => "Formation en collecte et traitement des données avec KOBO Collect et SPSS pour des organisations humanitaires et de développement. Des statistiques précises pour une meilleure prise de décision.",
                'image' => '/media/realizations/real-p25-1.jpg', 'client' => 'Organisations humanitaires', 'location' => 'Goma', 'completed_at' => '2025-10-08', 'category' => 'Formation & Certification', 'tags' => ['KoboCollect', 'SPSS', 'Données'],
            ],
            [
                'title' => 'Séance pratique de conception de schémas électriques',
                'summary' => 'Séance pratique en électricité : schémas électriques et télécoms.',
                'description' => "Séance pratique de conception de schémas électriques et de systèmes de télécommunication. Les apprenants ont conçu des plans complets pour installations domestiques et industrielles.",
                'image' => '/media/realizations/real-p26-2.jpg', 'client' => 'SubNet Congo — Formation', 'location' => 'Goma', 'completed_at' => '2025-11-15', 'category' => 'Électricité & Énergie', 'tags' => ['Électricité', 'Schémas', 'Pratique'],
            ],
            [
                'title' => 'Formation en électricité — session pratique',
                'summary' => 'Formation pratique en installations électriques domestiques et industrielles.',
                'description' => "Les ingénieurs formés en électricité ont participé à leur séance pratique : installation, câblage et mise en service d'équipements électriques domestiques et industriels.",
                'image' => '/media/realizations/real-p27-1.jpg', 'client' => 'SubNet Congo — Formation', 'location' => 'Goma', 'completed_at' => '2025-11-20', 'category' => 'Électricité & Énergie', 'tags' => ['Électricité', 'Formation', 'Pratique'],
            ],
            [
                'title' => 'Rien ne marche sans le courant électrique',
                'summary' => 'Projets énergétiques : énergie solaire, barrages hydroélectriques et énergies renouvelables.',
                'description' => "Notre département électricité conçoit et réalise des projets de parc solaire photovoltaïque, des projets de barrages hydroélectriques et des unités d'énergie éolienne pour désenclaver les zones hors réseau.",
                'image' => '/media/realizations/real-p28-1.jpg', 'client' => 'Partenaires énergétiques', 'location' => 'Nord-Kivu', 'completed_at' => '2026-01-10', 'category' => 'Électricité & Énergie', 'tags' => ['Énergie', 'Solaire', 'Hydro'],
            ],
            [
                'title' => 'Formation des jeunes entrepreneuses — Éducation financière',
                'summary' => 'Formation des jeunes femmes entrepreneuses en éducation financière.',
                'description' => "Série de formations dédiées aux jeunes femmes entrepreneuses sur l'éducation financière, la gestion d'entreprise et le leadership. Plusieurs cohortes formées à Goma.",
                'image' => '/media/realizations/real-p29-1.jpg', 'client' => 'Partenaires sociaux', 'location' => 'Goma', 'completed_at' => '2026-02-14', 'category' => 'Formation & Certification', 'tags' => ['Entreprenariat', 'Femmes', 'Finance'],
            ],
            [
                'title' => 'Soutenance publique des projets réseau & télécom',
                'summary' => 'Soutenance publique des projets réalisés par les apprenants réseau et télécom.',
                'description' => "Soutenance publique des projets en réseau et télécommunication réalisés par nos apprenants. Jury composé d'experts Motorola et des ingénieurs de SubNet Congo.",
                'image' => '/media/realizations/real-p30-1.jpg', 'client' => 'SubNet Congo — Formation', 'location' => 'Goma', 'completed_at' => '2026-03-05', 'category' => 'Formation & Certification', 'tags' => ['Soutenance', 'Réseau', 'Télécoms'],
            ],
            [
                'title' => 'Remise des brevets à la maison d’accueil BAKANJA',
                'summary' => 'Soutenance publique et remise des brevets à la grande salle BAKANJA à Goma.',
                'description' => "Récente soutenance publique au grand hall de la maison d'accueil BAKANJA. Défense en groupe des projets réseau et télécom et remise des brevets aux étudiants formés en bureautique.",
                'image' => '/media/realizations/real-p31-1.jpg', 'client' => 'Maison d’accueil BAKANJA', 'location' => 'Goma', 'completed_at' => '2026-04-18', 'category' => 'Formation & Certification', 'tags' => ['BAKANJA', 'Brevets', 'Réseau'],
            ],
            [
                'title' => 'Remise des brevets en bureautique — Word & Excel',
                'summary' => 'Cérémonie de remise des brevets après formation Word et Excel.',
                'description' => "Exposé et remise officielle des brevets aux enfants formés à Word et Excel après les vacances. Deux ordinations, taux de réussite élevé.",
                'image' => '/media/realizations/real-p32-1.jpg', 'client' => 'SubNet Congo — Formation', 'location' => 'Goma', 'completed_at' => '2026-05-30', 'category' => 'Formation & Certification', 'tags' => ['Bureautique', 'Word', 'Excel'],
            ],
        ];

        foreach ($realizations as $i => $data) {
            $slug = Str::slug($data['title']);
            Realization::updateOrCreate(
                ['slug' => $slug],
                array_merge($data, [
                    'slug' => $slug,
                    'is_published' => true,
                ])
            );
        }

        $this->command->info('Réalisations semées : ' . count($realizations) . ' projets SubNet Congo créés');
    }
}
