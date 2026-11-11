<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Cours officiels SubNet Congo Sarl alignés sur les compétences de chaque
 * instructeur (PDF « Présentation de la société SubNet Congo », novembre 2026).
 */
class SubnetCongoCoursesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all()->keyBy('name');
        $instructors = User::role('instructor')->get()->keyBy('email');

        if ($instructors->isEmpty()) {
            $this->command->warn('Aucun instructeur trouvé. Exécutez UserSeeder d’abord.');
            return;
        }

        $getInstructor = fn (string $email) => $instructors[$email] ?? $instructors->first();

        $courses = [
            // ── Salomon Makanga : Bureautique & Gestion de Projets ───────────────
            [
                'title' => 'Microsoft Office Professionnel : Word, Excel, PowerPoint',
                'category' => 'Développement Web & App',
                'short_info' => 'Maîtrisez toute la suite Microsoft Office comme un pro.',
                'description' => 'Formation complète à la suite Microsoft Office : Word pour les documents, Excel pour les calculs et tableaux de bord, PowerPoint pour les présentations, Outlook pour la messagerie et Publisher/Project en complément. À l’issue, vous êtes opérationnel pour des fonctions administratives et de gestion.',
                'level' => 'beginner', 'price' => 99, 'duration_minutes' => 600,
                'thumbnail' => 'https://images.unsplash.com/photo-1585792977776-4a9683e13e30?w=800&q=80',
                'tags' => ['Microsoft Office', 'Word', 'Excel', 'PowerPoint', 'Bureautique'],
                'is_featured' => true, 'instructor' => 'makangasalomon@gmail.com',
            ],
            [
                'title' => 'Gestion de Projets Professionnels',
                'category' => 'Systèmes & Open Source',
                'short_info' => 'Conception, planification et suivi de vos projets, de l’idée à la livraison.',
                'description' => 'Méthodologies professionnelles de gestion de projets : étude de faisabilité, planification (WBS/Gantt), suivi des coûts et délais, reporting, gestion des risques, élaboration de projets financés, rédaction de rapports et présentations de soutenance.',
                'level' => 'intermediate', 'price' => 129, 'duration_minutes' => 720,
                'thumbnail' => 'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=800&q=80',
                'tags' => ['Gestion de projet', 'Planification', 'Gantt', 'Coordination'],
                'is_featured' => false, 'instructor' => 'makangasalomon@gmail.com',
            ],
            // ── Katya Muholu Joackim : Réseaux & Sécurité Électronique ──────────
            [
                'title' => 'Sécurité Électronique & Vidéosurveillance (CCTV)',
                'category' => 'Cybersécurité',
                'short_info' => 'Concevez et installez des systèmes de vidéosurveillance professionnels.',
                'description' => 'Étude et installation de systèmes de caméras de surveillance : choix des caméras (IP/analogiques), câblage, enregistrement (NVR/DVR), accès à distance, configuration et maintenance. Couvre CCTV, interphonie, contrôle d’accès et intrusion.',
                'level' => 'intermediate', 'price' => 149, 'duration_minutes' => 840,
                'thumbnail' => 'https://images.unsplash.com/photo-1558002038-1055907df827?w=800&q=80',
                'tags' => ['CCTV', 'Sécurité électronique', 'Vidéosurveillance', 'Intrusion'],
                'is_featured' => true, 'instructor' => 'joackimmuholu@gmail.com',
            ],
            [
                'title' => 'Administration & Support des Systèmes Informatiques',
                'category' => 'Infrastructure & Réseaux',
                'short_info' => 'Installation, configuration et maintenance de postes et serveurs.',
                'description' => 'Administration complète d’un parc informatique : installation Windows/Linux, configuration réseau, gestion utilisateurs/permissions, sauvegardes, maintenance préventive, helpdesk/support, assistance technique aux utilisateurs et résolution des incidents.',
                'level' => 'beginner', 'price' => 119, 'duration_minutes' => 720,
                'thumbnail' => 'https://images.unsplash.com/photo-1573164713988-8665fc963095?w=800&q=80',
                'tags' => ['Support IT', 'Administration', 'Helpdesk', 'Maintenance'],
                'is_featured' => false, 'instructor' => 'joackimmuholu@gmail.com',
            ],
            // ── Djongelo Okito Albert : Réseaux Informatiques ───────────────────
            [
                'title' => 'Mise en Place de Réseaux d’Entreprise (PAN, LAN, WAN)',
                'category' => 'Infrastructure & Réseaux',
                'short_info' => 'Étude, conception et déploiement de réseaux d’entreprise de bout en bout.',
                'description' => 'Conception et installation de réseaux PAN, LAN, WAN, MAN et SAN. Configuration des équipements réseau, adressage, VLAN, sécurité des réseaux, optimisation des performances. Inclut une étude pratique d’implémentation sur site.',
                'level' => 'intermediate', 'price' => 179, 'duration_minutes' => 900,
                'thumbnail' => 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=800&q=80',
                'tags' => ['Réseau LAN', 'VLAN', 'Configurations', 'Entreprise'],
                'is_featured' => false, 'instructor' => 'djongueloalbert@gmail.com',
            ],
            [
                'title' => 'Télécommunications : Antennes & Réseaux Télécoms',
                'category' => 'Télécoms & Satellites',
                'short_info' => 'Radiocommunication HF/VHF/UHF, VoIP, IP TV et antennes satellitaires.',
                'description' => 'Formation complète aux télécommunications : architecture de réseau radio Motorola HF/VHF/UHF, partage de données VoIP et fixe, réseaux IP TV, interphonie analogique et numérique. Installation d’antennes VSAT, Starlink et fibre optique.',
                'level' => 'advanced', 'price' => 199, 'duration_minutes' => 960,
                'thumbnail' => 'https://images.unsplash.com/photo-1508739773434-c26b3d09e071?w=800&q=80',
                'tags' => ['Télécoms', 'VSAT', 'VoIP', 'Motorola', 'Antennes'],
                'is_featured' => true, 'instructor' => 'djongueloalbert@gmail.com',
            ],
            // ── Ephatas Kasyono Prisca : Réseaux Informatiques ───────────────────
            [
                'title' => 'Maintenance & Dépannage des Infrastructures Réseau',
                'category' => 'Infrastructure & Réseaux',
                'short_info' => 'Diagnostic, réparation et optimisation des réseaux en entreprise.',
                'description' => 'Maintenance préventive et corrective d’infrastructures réseau : câblage, équipements actifs, diagnostics, optimisation des performances, sécurité de base, traitement des lenteurs et pannes. Spécialité réseaux d’entreprise.',
                'level' => 'intermediate', 'price' => 139, 'duration_minutes' => 720,
                'thumbnail' => 'https://images.unsplash.com/photo-1551803091-e20673f15770?w=800&q=80',
                'tags' => ['Maintenance', 'Dépannage', 'Câblage', 'Réseaux'],
                'is_featured' => false, 'instructor' => 'ephatakasyono@gmail.com',
            ],
            // ── Marc Tubadi : Robotique & IA ─────────────────────────────────────
            [
                'title' => 'Robotique & Systèmes Embarqués avec Arduino',
                'category' => 'Systèmes & Open Source',
                'short_info' => 'Construisez vos premiers robots et systèmes intelligents.',
                'description' => 'Pratique complète de l’électronique embarquée : capteurs, actionneurs, programmation Arduino, conception de robots autonomes, systèmes intelligents, logique de contrôle, prototypage. Projets pratiques avec matériel réel.',
                'level' => 'intermediate', 'price' => 179, 'duration_minutes' => 900,
                'thumbnail' => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=800&q=80',
                'tags' => ['Robotique', 'Arduino', 'Embarqués', 'Électronique'],
                'is_featured' => true, 'instructor' => 'marctubadi1@gmail.com',
            ],
            [
                'title' => 'Introduction à l’Intelligence Artificielle & Machine Learning',
                'category' => 'Systèmes & Open Source',
                'short_info' => 'Les fondamentaux de l’IA appliqués à vos projets.',
                'description' => 'Introduction accessible à l’intelligence artificielle et au machine learning : concepts, algorithmes, projets Python, applications concrètes (chatbots, reconnaissance, analyse de données). Orienté pratique, sans prérequis complexes.',
                'level' => 'beginner', 'price' => 169, 'duration_minutes' => 800,
                'thumbnail' => 'https://images.unsplash.com/photo-1555255707-c079bb499f68?w=800&q=80',
                'tags' => ['Intelligence Artificielle', 'Machine Learning', 'Python', 'IA'],
                'is_featured' => false, 'instructor' => 'marctubadi1@gmail.com',
            ],
            // ── Akose Stiva : Systèmes Embarqués & IoT ───────────────────────────
            [
                'title' => 'Internet des Objets (IoT) & Domotique Pratique',
                'category' => 'Systèmes & Open Source',
                'short_info' => 'Créez des objets connectés pour la maison et l’industrie.',
                'description' => 'Domotique et IoT pratiques : conception de systèmes connectés avec capteurs, microcontrôleurs ESP32/Arduino, communication, dashboard de monitoring, domotique pour maison intelligente. Projets clés en main.',
                'level' => 'beginner', 'price' => 149, 'duration_minutes' => 720,
                'thumbnail' => 'https://images.unsplash.com/photo-1558002038-1055907df827?w=800&q=80',
                'tags' => ['IoT', 'Domotique', 'ESP32', 'Objets connectés'],
                'is_featured' => false, 'instructor' => 'akosestive@gmail.com',
            ],
            // ── Tonny Kayayalo : Télécommunications & VSAT ───────────────────────
            [
                'title' => 'Installation Starlink & VSAT : Internet par Satellite',
                'category' => 'Télécoms & Satellites',
                'short_info' => 'Installez et gérez des installations VSAT et Starlink partout en RDC.',
                'description' => 'Formation pratique à l’installation et la maintenance d’équipements Internet par satellite : VSAT (bandes Ku/Ka/C), Starlink Business, pointage d’antenne, configuration, dépannage, mise en service complète. Interventions terrain à Goma et dans l’Est de la RDC.',
                'level' => 'intermediate', 'price' => 199, 'duration_minutes' => 840,
                'thumbnail' => 'https://images.unsplash.com/photo-1580913428020-50bcccaa1e4a?w=800&q=80',
                'tags' => ['Starlink', 'VSAT', 'Satellite', 'Internet rural'],
                'is_featured' => true, 'instructor' => 'tonnykayayalo@gmail.com',
            ],
            [
                'title' => 'Fibre Optique & Réseaux Très Haut Débit',
                'category' => 'Télécoms & Satellites',
                'short_info' => 'Déploiement de réseaux fibre optique jusqu’aux locaux client.',
                'description' => 'Installation et déploiement de réseaux fibre optique : tirage, soudure, certification, raccordement, administration des FTTH/FTTX. Destiné aux techniciens et entrepreneurs du secteur des télécoms.',
                'level' => 'advanced', 'price' => 249, 'duration_minutes' => 960,
                'thumbnail' => 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&q=80',
                'tags' => ['Fibre optique', 'FTTH', 'Soudure', 'Télécoms'],
                'is_featured' => false, 'instructor' => 'tonnykayayalo@gmail.com',
            ],
        ];

        $count = 0;
        foreach ($courses as $courseData) {
            $email = $courseData['instructor'];
            unset($courseData['instructor']);

            $instructor = $getInstructor($email);
            $slug = Str::slug($courseData['title']);
            $category = $categories->get($courseData['category']) ?? $categories->first();

            Course::firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => $courseData['title'],
                    'description' => $courseData['description'],
                    'thumbnail_path' => $courseData['thumbnail'],
                    'image' => $courseData['thumbnail'],
                    'price' => $courseData['price'],
                    'promo_price' => $courseData['price'] > 150 ? (int) ($courseData['price'] * 0.75) : 0.00,
                    'level' => $courseData['level'],
                    'status' => 'published',
                    'is_featured' => $courseData['is_featured'],
                    'short_info' => $courseData['short_info'],
                    'tags' => $courseData['tags'],
                    'duration_minutes' => $courseData['duration_minutes'],
                    'instructor_id' => $instructor->id,
                    'category_id' => $category->id,
                    'created_by' => $instructor->id,
                    'requires_enrollment' => $courseData['price'] > 0,
                    'certificate_enabled' => $courseData['price'] > 0,
                    'language' => 'fr',
                    'has_final_exam' => $courseData['price'] > 0,
                    'final_exam_weight' => $courseData['price'] > 0 ? 40.00 : 0.00,
                    'passing_score_20' => $courseData['price'] > 0 ? 10.00 : 0.00,
                    'seo_metadata' => [
                        'title' => $courseData['title'],
                        'description' => $courseData['short_info'],
                        'keywords' => implode(', ', $courseData['tags']),
                    ],
                ]
            );
            $count++;
        }

        $this->command->info("Cours SubNet Congo créés : {$count} cours alignés sur les spécialités des instructeurs");
    }
}
