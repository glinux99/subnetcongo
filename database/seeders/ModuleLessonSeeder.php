<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ModuleLessonSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::with('category')->get();

        foreach ($courses as $course) {
            $this->createModulesForCourse($course);
        }

        $this->command->info('Modules et leçons créés pour tous les cours !');
    }

    private function createModulesForCourse(Course $course): void
    {
        // Vérifier si des modules existent déjà pour ce cours
        if ($course->modules()->count() > 0) {
            return;
        }

        $modulesData = $this->getModulesForCourse($course);

        foreach ($modulesData as $index => $moduleData) {
            $module = Module::create([
                'course_id' => $course->id,
                'uuid' => (string) Str::uuid(),
                'slug' => Str::slug($moduleData['title']) . '-' . $course->id . '-' . $index,
                'title' => $moduleData['title'],
                'description' => $moduleData['description'],
                'order' => $index + 1,
                'is_published' => true,
                'estimated_duration' => $moduleData['estimated_duration'] ?? 60,
                'objectives' => $moduleData['objectives'] ?? [],
            ]);

            foreach ($moduleData['lessons'] as $lessonIndex => $lessonData) {
                Lesson::create([
                    'module_id' => $module->id,
                    'uuid' => (string) Str::uuid(),
                    'title' => $lessonData['title'],
                    'slug' => Str::slug($lessonData['title']) . '-' . $module->id . '-' . $lessonIndex,
                    'type' => $lessonData['type'] ?? 'video',
                    'content' => $lessonData['content'] ?? null,
                    'content_text' => $lessonData['content_text'] ?? null,
                    'video_url' => $lessonData['video_url'] ?? null,
                    'video_source' => $lessonData['video_source'] ?? 'youtube',
                    'duration_minutes' => $lessonData['duration_minutes'] ?? 15,
                    'order' => $lessonIndex + 1,
                    'is_published' => true,
                    'is_free_preview' => $lessonIndex === 0 && $index === 0, // Première leçon gratuite
                ]);
            }
        }
    }

    private function getModulesForCourse(Course $course): array
    {
        $categoryName = $course->category->name;

        // Structure par défaut selon la catégorie
        if ($categoryName === 'Infrastructure & Réseaux') {
            return [
                [
                    'title' => 'Introduction et Fondamentaux',
                    'description' => 'Découvrez les bases du cours et les concepts fondamentaux.',
                    'estimated_duration' => 90,
                    'objectives' => ['Comprendre les objectifs du cours', 'Maîtriser les concepts de base'],
                    'lessons' => [
                        ['title' => 'Présentation du cours', 'type' => 'video', 'duration_minutes' => 10, 'content_text' => 'Introduction au contenu du cours et aux compétences acquises.'],
                        ['title' => 'Concepts fondamentaux', 'type' => 'video', 'duration_minutes' => 25, 'content_text' => 'Les concepts essentiels à maîtriser avant de continuer.'],
                        ['title' => 'Quiz de validation', 'type' => 'quiz', 'duration_minutes' => 15, 'content_text' => 'Testez vos connaissances acquises dans ce module.'],
                    ],
                ],
                [
                    'title' => 'Configuration et Installation',
                    'description' => 'Apprenez à configurer et installer les équipements.',
                    'estimated_duration' => 120,
                    'lessons' => [
                        ['title' => 'Installation de base', 'type' => 'video', 'duration_minutes' => 30, 'content_text' => 'Guide pas à pas pour l\'installation.'],
                        ['title' => 'Configuration initiale', 'type' => 'video', 'duration_minutes' => 35, 'content_text' => 'Paramétrage des options essentielles.'],
                        ['title' => 'Documentation technique', 'type' => 'document', 'duration_minutes' => 15, 'content_text' => 'Ressources et documentation complémentaire.'],
                    ],
                ],
                [
                    'title' => 'Fonctionnalités Avancées',
                    'description' => 'Maîtrisez les fonctionnalités avancées et professionnelles.',
                    'estimated_duration' => 150,
                    'lessons' => [
                        ['title' => 'Configuration avancée', 'type' => 'video', 'duration_minutes' => 40, 'content_text' => 'Techniques avancées de configuration.'],
                        ['title' => 'Optimisation des performances', 'type' => 'video', 'duration_minutes' => 35, 'content_text' => 'Meilleures pratiques pour optimiser votre infrastructure.'],
                        ['title' => 'Dépannage avancé', 'type' => 'video', 'duration_minutes' => 45, 'content_text' => 'Méthodologie de dépannage complexe.'],
                    ],
                ],
                [
                    'title' => 'Sécurité et Maintenance',
                    'description' => 'Sécurisez votre infrastructure et assurez sa maintenance.',
                    'estimated_duration' => 100,
                    'lessons' => [
                        ['title' => 'Bases de la sécurité', 'type' => 'video', 'duration_minutes' => 30, 'content_text' => 'Principes de sécurité à appliquer.'],
                        ['title' => 'Maintenance préventive', 'type' => 'video', 'duration_minutes' => 25, 'content_text' => 'Plan de maintenance et bonnes pratiques.'],
                        ['title' => 'Sauvegardes et restauration', 'type' => 'video', 'duration_minutes' => 25, 'content_text' => 'Stratégies de sauvegarde et procédures de restauration.'],
                    ],
                ],
            ];
        }

        if ($categoryName === 'Cybersécurité') {
            return [
                [
                    'title' => 'Introduction à la Cybersécurité',
                    'description' => 'Fondamentaux de la sécurité informatique.',
                    'estimated_duration' => 75,
                    'lessons' => [
                        ['title' => 'Le paysage des menaces', 'type' => 'video', 'duration_minutes' => 20, 'content_text' => 'Comprendre les types d\'attaques et leurs motivations.'],
                        ['title' => 'Principes de sécurité', 'type' => 'video', 'duration_minutes' => 25, 'content_text' => 'CIA Triad et principes fondamentaux.'],
                        ['title' => 'Quiz introductif', 'type' => 'quiz', 'duration_minutes' => 15, 'content_text' => 'Validation des connaissances de base.'],
                    ],
                ],
                [
                    'title' => 'Reconnaissance et Énumération',
                    'description' => 'Techniques de collecte d\'informations.',
                    'estimated_duration' => 120,
                    'lessons' => [
                        ['title' => 'OSINT et reconnaissance passive', 'type' => 'video', 'duration_minutes' => 35, 'content_text' => 'Collecte d\'informations ouvertes.'],
                        ['title' => 'Scanning de réseau', 'type' => 'video', 'duration_minutes' => 30, 'content_text' => 'Nmap et techniques de scanning.'],
                        ['title' => 'Énumération de services', 'type' => 'video', 'duration_minutes' => 35, 'content_text' => 'Identification des services et vulnérabilités.'],
                    ],
                ],
                [
                    'title' => 'Exploitation',
                    'description' => 'Techniques d\'exploitation de vulnérabilités.',
                    'estimated_duration' => 150,
                    'lessons' => [
                        ['title' => 'Exploitation web', 'type' => 'video', 'duration_minutes' => 40, 'content_text' => 'SQL Injection, XSS et autres vulnérabilités web.'],
                        ['title' => 'Exploitation réseau', 'type' => 'video', 'duration_minutes' => 35, 'content_text' => 'Exploits réseau et privilege escalation.'],
                        ['title' => 'Metasploit Framework', 'type' => 'video', 'duration_minutes' => 45, 'content_text' => 'Utilisation avancée de Metasploit.'],
                    ],
                ],
                [
                    'title' => 'Post-Exploitation et Reporting',
                    'description' => 'Actions après compromission et rapport.',
                    'estimated_duration' => 100,
                    'lessons' => [
                        ['title' => 'Maintien d\'accès', 'type' => 'video', 'duration_minutes' => 30, 'content_text' => 'Backdoors et persistence.'],
                        ['title' => 'Exfiltration de données', 'type' => 'video', 'duration_minutes' => 25, 'content_text' => 'Techniques d\'exfiltration discrète.'],
                        ['title' => 'Rédaction de rapport', 'type' => 'document', 'duration_minutes' => 25, 'content_text' => 'Structure et contenu d\'un rapport de pentest.'],
                    ],
                ],
            ];
        }

        if ($categoryName === 'Télécoms & Satellites') {
            return [
                [
                    'title' => 'Fondamentaux des Télécoms',
                    'description' => 'Bases des télécommunications satellites.',
                    'estimated_duration' => 80,
                    'lessons' => [
                        ['title' => 'Introduction aux satellites', 'type' => 'video', 'duration_minutes' => 25, 'content_text' => 'Principes de communication par satellite.'],
                        ['title' => 'Spectre radio et fréquences', 'type' => 'video', 'duration_minutes' => 30, 'content_text' => 'Comprendre le spectre RF et les bandes de fréquence.'],
                        ['title' => 'Quiz de fond', 'type' => 'quiz', 'duration_minutes' => 15, 'content_text' => 'Test des connaissances théoriques.'],
                    ],
                ],
                [
                    'title' => 'Installation et Configuration',
                    'description' => 'Installation pratique des équipements.',
                    'estimated_duration' => 130,
                    'lessons' => [
                        ['title' => 'Installation du matériel', 'type' => 'video', 'duration_minutes' => 40, 'content_text' => 'Montage et installation des terminaux.'],
                        ['title' => 'Pointage et alignement', 'type' => 'video', 'duration_minutes' => 35, 'content_text' => 'Techniques de pointage précis.'],
                        ['title' => 'Configuration du modem', 'type' => 'video', 'duration_minutes' => 35, 'content_text' => 'Paramétrage et activation du service.'],
                    ],
                ],
                [
                    'title' => 'Maintenance et Dépannage',
                    'description' => 'Maintenance préventive et corrective.',
                    'estimated_duration' => 110,
                    'lessons' => [
                        ['title' => 'Maintenance préventive', 'type' => 'video', 'duration_minutes' => 30, 'content_text' => 'Plan de maintenance régulière.'],
                        ['title' => 'Diagnostic et dépannage', 'type' => 'video', 'duration_minutes' => 40, 'content_text' => 'Méthodologie de diagnostic des problèmes.'],
                        ['title' => 'Outils de test', 'type' => 'video', 'duration_minutes' => 30, 'content_text' => 'Utilisation des équipements de test.'],
                    ],
                ],
            ];
        }

        if ($categoryName === 'Cloud & Virtualisation') {
            return [
                [
                    'title' => 'Introduction au Cloud',
                    'description' => 'Concepts et modèles du cloud computing.',
                    'estimated_duration' => 70,
                    'lessons' => [
                        ['title' => 'Qu\'est-ce que le cloud ?', 'type' => 'video', 'duration_minutes' => 20, 'content_text' => 'Définitions et modèles de service.'],
                        ['title' => 'Avantages et inconvénients', 'type' => 'video', 'duration_minutes' => 25, 'content_text' => 'Analyse des bénéfices et limitations.'],
                        ['title' => 'Quiz introductif', 'type' => 'quiz', 'duration_minutes' => 15, 'content_text' => 'Validation des concepts de base.'],
                    ],
                ],
                [
                    'title' => 'Conteneurisation avec Docker',
                    'description' => 'Maîtrise de Docker et des conteneurs.',
                    'estimated_duration' => 140,
                    'lessons' => [
                        ['title' => 'Introduction à Docker', 'type' => 'video', 'duration_minutes' => 35, 'content_text' => 'Concepts et installation de Docker.'],
                        ['title' => 'Dockerfiles et images', 'type' => 'video', 'duration_minutes' => 35, 'content_text' => 'Création de Dockerfiles et gestion d\'images.'],
                        ['title' => 'Docker Compose', 'type' => 'video', 'duration_minutes' => 35, 'content_text' => 'Orchestration multi-conteneurs.'],
                    ],
                ],
                [
                    'title' => 'Orchestration avec Kubernetes',
                    'description' => 'Déploiement et gestion avec K8s.',
                    'estimated_duration' => 160,
                    'lessons' => [
                        ['title' => 'Architecture Kubernetes', 'type' => 'video', 'duration_minutes' => 40, 'content_text' => 'Composants et concepts K8s.'],
                        ['title' => 'Déploiement d\'applications', 'type' => 'video', 'duration_minutes' => 40, 'content_text' => 'Pods, Services et Deployments.'],
                        ['title' => 'Scaling et haute disponibilité', 'type' => 'video', 'duration_minutes' => 40, 'content_text' => 'Horizontal Pod Autoscaler et clustering.'],
                    ],
                ],
            ];
        }

        if ($categoryName === 'Développement Web & App') {
            return [
                [
                    'title' => 'Fondamentaux du Web',
                    'description' => 'HTML, CSS et JavaScript de base.',
                    'estimated_duration' => 90,
                    'lessons' => [
                        ['title' => 'HTML5 sémantique', 'type' => 'video', 'duration_minutes' => 25, 'content_text' => 'Structure et balises HTML5.'],
                        ['title' => 'CSS3 moderne', 'type' => 'video', 'duration_minutes' => 30, 'content_text' => 'Flexbox, Grid et animations.'],
                        ['title' => 'JavaScript ES6+', 'type' => 'video', 'duration_minutes' => 25, 'content_text' => 'Fondamentaux du JavaScript moderne.'],
                    ],
                ],
                [
                    'title' => 'Laravel Framework',
                    'description' => 'Développement backend avec Laravel.',
                    'estimated_duration' => 150,
                    'lessons' => [
                        ['title' => 'Installation et configuration', 'type' => 'video', 'duration_minutes' => 30, 'content_text' => 'Mise en place d\'un projet Laravel.'],
                        ['title' => 'MVC et Routes', 'type' => 'video', 'duration_minutes' => 35, 'content_text' => 'Architecture MVC et système de routing.'],
                        ['title' => 'Eloquent ORM', 'type' => 'video', 'duration_minutes' => 35, 'content_text' => 'Modèles et base de données.'],
                        ['title' => 'Authentification', 'type' => 'video', 'duration_minutes' => 30, 'content_text' => 'Système d\'authentification Laravel.'],
                    ],
                ],
                [
                    'title' => 'Vue.js 3 et Frontend',
                    'description' => 'Interface utilisateur avec Vue.js.',
                    'estimated_duration' => 140,
                    'lessons' => [
                        ['title' => 'Composition API', 'type' => 'video', 'duration_minutes' => 35, 'content_text' => 'Nouveautés de Vue 3 Composition API.'],
                        ['title' => 'Composants et Props', 'type' => 'video', 'duration_minutes' => 35, 'content_text' => 'Création et gestion de composants.'],
                        ['title' => 'State Management', 'type' => 'video', 'duration_minutes' => 35, 'content_text' => 'Pinia et gestion d\'état.'],
                    ],
                ],
            ];
        }

        if ($categoryName === 'Systèmes & Open Source') {
            return [
                [
                    'title' => 'Introduction aux Systèmes',
                    'description' => 'Fondamentaux des systèmes d\'exploitation.',
                    'estimated_duration' => 75,
                    'lessons' => [
                        ['title' => 'Architecture système', 'type' => 'video', 'duration_minutes' => 25, 'content_text' => 'Comprendre l\'architecture des OS.'],
                        ['title' => 'Linux vs Windows', 'type' => 'video', 'duration_minutes' => 25, 'content_text' => 'Comparaison des principaux systèmes.'],
                        ['title' => 'Quiz introductif', 'type' => 'quiz', 'duration_minutes' => 15, 'content_text' => 'Test des connaissances de base.'],
                    ],
                ],
                [
                    'title' => 'Administration Linux',
                    'description' => 'Commandes et administration Linux.',
                    'estimated_duration' => 140,
                    'lessons' => [
                        ['title' => 'Commandes essentielles', 'type' => 'video', 'duration_minutes' => 35, 'content_text' => 'Les commandes Linux à connaître.'],
                        ['title' => 'Gestion des utilisateurs', 'type' => 'video', 'duration_minutes' => 35, 'content_text' => 'Users, groups et permissions.'],
                        ['title' => 'Gestion des processus', 'type' => 'video', 'duration_minutes' => 35, 'content_text' => 'Processus, services et monitoring.'],
                    ],
                ],
                [
                    'title' => 'Automatisation',
                    'description' => 'Scripts et automatisation des tâches.',
                    'estimated_duration' => 120,
                    'lessons' => [
                        ['title' => 'Bash scripting', 'type' => 'video', 'duration_minutes' => 40, 'content_text' => 'Création de scripts Bash.'],
                        ['title' => 'Ansible basics', 'type' => 'video', 'duration_minutes' => 40, 'content_text' => 'Introduction à Ansible.'],
                        ['title' => 'CI/CD pipelines', 'type' => 'video', 'duration_minutes' => 30, 'content_text' => 'Automatisation du déploiement.'],
                    ],
                ],
            ];
        }

        // Structure générique par défaut
        return [
            [
                'title' => 'Introduction',
                'description' => 'Introduction au cours.',
                'estimated_duration' => 60,
                'lessons' => [
                    ['title' => 'Présentation', 'type' => 'video', 'duration_minutes' => 20, 'content_text' => 'Présentation du cours.'],
                    ['title' => 'Concepts de base', 'type' => 'video', 'duration_minutes' => 25, 'content_text' => 'Concepts fondamentaux.'],
                ],
            ],
            [
                'title' => 'Module Pratique',
                'description' => 'Mise en pratique.',
                'estimated_duration' => 90,
                'lessons' => [
                    ['title' => 'Démonstration', 'type' => 'video', 'duration_minutes' => 30, 'content_text' => 'Démonstration pratique.'],
                    ['title' => 'Exercices', 'type' => 'text', 'duration_minutes' => 30, 'content_text' => 'Exercices pratiques.'],
                ],
            ],
        ];
    }
}
