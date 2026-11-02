<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $instructors = User::role('instructor')->get();
        
        if ($instructors->isEmpty()) {
            $this->command->warn('Aucun instructeur trouvé. Créez d\'abord des instructeurs avec UserSeeder.');
            return;
        }

        $categories = Category::all()->keyBy('name');

        $courses = [
            // === Infrastructure & Réseaux ===
            [
                'title' => 'Cisco CCNA 200-301 : Le Guide Complet',
                'category' => 'Infrastructure & Réseaux',
                'short_info' => 'Devenez un expert en routage et commutation Cisco.',
                'description' => "## Pourquoi suivre cette formation ?

Cette formation complète vous permettra de maîtriser les fondamentaux des réseaux Cisco et de préparer la certification CCNA 200-301. Vous apprendrez à configurer, gérer et dépanner des réseaux d'entreprise.

### Ce que vous allez apprendre
- Configuration des routeurs et commutateurs Cisco
- Protocoles de routage (OSPF, EIGRP, BGP)
- Commutation LAN et VLAN
- Sécurité réseau de base
- Dépannage réseau

### Prérequis
- Connaissances de base en informatique
- Motivation pour apprendre le networking",
                'level' => 'beginner',
                'price' => 199,
                'promo_price' => 149,
                'duration_minutes' => 1800,
                'thumbnail' => 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&q=80',
                'tags' => ['Cisco', 'CCNA', 'Networking', 'Routing'],
                'is_featured' => true,
            ],
            [
                'title' => 'MikroTik RouterOS : Formation Complète',
                'category' => 'Infrastructure & Réseaux',
                'short_info' => 'Maîtrisez les routeurs MikroTik pour les réseaux professionnels.',
                'description' => "## Pourquoi suivre cette formation ?

Apprenez à configurer et gérer des équipements MikroTik pour créer des réseaux performants et sécurisés. Cette formation couvre toutes les fonctionnalités essentielles de RouterOS.

### Ce que vous allez apprendre
- Configuration de base RouterOS
- Hotspot et gestion WiFi
- Firewall et sécurité avancée
- VPN et tunnels
- Monitoring et reporting

### Prérequis
- Connaissances de base en réseaux IP
- Accès à un routeur MikroTik",
                'level' => 'intermediate',
                'price' => 149,
                'promo_price' => 99,
                'duration_minutes' => 1200,
                'thumbnail' => 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=800&q=80',
                'tags' => ['MikroTik', 'RouterOS', 'WiFi', 'Firewall'],
                'is_featured' => false,
            ],
            [
                'title' => 'Introduction aux Réseaux Informatiques',
                'category' => 'Infrastructure & Réseaux',
                'short_info' => 'Les fondamentaux du networking pour débutants.',
                'description' => "## Pourquoi suivre cette formation ?

Découvrez les bases des réseaux informatiques : modèle OSI, adressage IP, protocoles TCP/IP. Le point de départ idéal pour une carrière dans le networking.

### Ce que vous allez apprendre
- Modèle OSI et TCP/IP
- Adressage IPv4 et IPv6
- Câblage et équipements réseau
- DNS et DHCP
- Notions de sécurité réseau

### Prérequis
- Aucun prérequis nécessaire",
                'level' => 'beginner',
                'price' => 0,
                'promo_price' => null,
                'duration_minutes' => 600,
                'thumbnail' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800&q=80',
                'tags' => ['Réseaux', 'TCP/IP', 'OSI', 'Networking'],
                'is_featured' => true,
            ],
            [
                'title' => 'Ubiquiti UniFi : Architecture Réseau',
                'category' => 'Infrastructure & Réseaux',
                'short_info' => 'Déployez des réseaux professionnels avec Ubiquiti.',
                'description' => "## Pourquoi suivre cette formation ?

Maîtrisez l'écosystème Ubiquiti UniFi pour créer des réseaux WiFi et câblés performants pour les entreprises et particuliers.

### Ce que vous allez apprendre
- Configuration du Controller UniFi
- Design WiFi professionnel
- Sécurité et VLANs
- Surveillance et analytics
- Gestion multi-sites

### Prérequis
- Connaissances de base en réseaux",
                'level' => 'intermediate',
                'price' => 129,
                'promo_price' => 89,
                'duration_minutes' => 900,
                'thumbnail' => 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&q=80',
                'tags' => ['Ubiquiti', 'UniFi', 'WiFi', 'Enterprise'],
                'is_featured' => false,
            ],

            // === Cybersécurité ===
            [
                'title' => 'Ethical Hacking : Pentesting Avancé',
                'category' => 'Cybersécurité',
                'short_info' => 'Apprenez les techniques des hackers pour mieux protéger.',
                'description' => "## Pourquoi suivre cette formation ?

Formez-vous aux techniques de hacking éthique et aux tests de pénétration pour identifier et corriger les vulnérabilités avant les attaquants.

### Ce que vous allez apprendre
- Reconnaissance et énumération
- Exploitation de vulnérabilités
- Post-exploitation
- Escalade de privilèges
- Rédaction de rapports de pentest

### Prérequis
- Connaissances réseaux solides
- Maîtrise de Linux
- Notions de programmation",
                'level' => 'advanced',
                'price' => 299,
                'promo_price' => 199,
                'duration_minutes' => 2400,
                'thumbnail' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800&q=80',
                'tags' => ['Hacking', 'Kali Linux', 'Security', 'Pentesting'],
                'is_featured' => true,
            ],
            [
                'title' => 'Sécurité Linux pour Administrateurs',
                'category' => 'Cybersécurité',
                'short_info' => 'Sécurisez vos serveurs Linux contre les attaques.',
                'description' => "## Pourquoi suivre cette formation ?

Apprenez à durcir vos serveurs Linux et à mettre en place des mesures de sécurité efficaces pour protéger vos infrastructures.

### Ce que vous allez apprendre
- Hardening de serveurs Linux
- Gestion des utilisateurs et permissions
- Configuration de firewalls (iptables, ufw)
- Surveillance et logs
- Audit de sécurité

### Prérequis
- Administration Linux de base",
                'level' => 'intermediate',
                'price' => 149,
                'promo_price' => 99,
                'duration_minutes' => 1200,
                'thumbnail' => 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=800&q=80',
                'tags' => ['Linux', 'Security', 'Hardening', 'Server'],
                'is_featured' => false,
            ],
            [
                'title' => 'Introduction à la Cybersécurité',
                'category' => 'Cybersécurité',
                'short_info' => 'Comprendre les menaces et les bonnes pratiques.',
                'description' => "## Pourquoi suivre cette formation ?

Découvrez les fondamentaux de la cybersécurité : types d'attaques, bonnes pratiques, outils de protection. Essentiel pour tous les professionnels IT.

### Ce que vous allez apprendre
- Types de cyberattaques
- Mots de passe et authentification
- Phishing et social engineering
- Protection des données
- Outils de sécurité de base

### Prérequis
- Aucun prérequis",
                'level' => 'beginner',
                'price' => 0,
                'promo_price' => null,
                'duration_minutes' => 450,
                'thumbnail' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=800&q=80',
                'tags' => ['Sécurité', 'Cyber', 'Introduction', 'Awareness'],
                'is_featured' => true,
            ],

            // === Télécoms & Satellites ===
            [
                'title' => 'Starlink for Business : Installation Pro',
                'category' => 'Télécoms & Satellites',
                'short_info' => 'Déployez l\'internet par satellite en zones reculées.',
                'description' => "## Pourquoi suivre cette formation ?

Maîtrisez l'installation et la configuration de Starlink Business pour connecter les entreprises et zones rurales à internet haut débit.

### Ce que vous allez apprendre
- Installation du matériel Starlink
- Configuration du réseau local
- Optimisation des performances
- Maintenance et dépannage
- Cas d'usage business

### Prérequis
- Connaissances réseaux de base",
                'level' => 'intermediate',
                'price' => 179,
                'promo_price' => 129,
                'duration_minutes' => 900,
                'thumbnail' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=800&q=80',
                'tags' => ['Starlink', 'Satellite', 'Telecom', 'Installation'],
                'is_featured' => true,
            ],
            [
                'title' => 'Fibre Optique FTTH : Déploiement Complet',
                'category' => 'Télécoms & Satellites',
                'short_info' => 'Techniques d\'installation et de raccordement fibre.',
                'description' => "## Pourquoi suivre cette formation ?

Apprenez les techniques professionnelles pour le déploiement de réseaux fibre optique FTTH : installation, fusion, testage.

### Ce que vous allez apprendre
- Introduction à la fibre optique
- Techniques de câblage
- Fusion de fibres (splicing)
- Test et mesure (OTDR)
- Raccordement terminal

### Prérequis
- Intérêt pour les télécoms",
                'level' => 'intermediate',
                'price' => 249,
                'promo_price' => 179,
                'duration_minutes' => 1500,
                'thumbnail' => 'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=800&q=80',
                'tags' => ['Fibre', 'FTTH', 'Optique', 'Telecom'],
                'is_featured' => false,
            ],
            [
                'title' => 'Technologies VSAT et Télécom Satellites',
                'category' => 'Télécoms & Satellites',
                'short_info' => 'Comprendre et déployer les solutions VSAT.',
                'description' => "## Pourquoi suivre cette formation ?

Maîtrisez les technologies VSAT (Very Small Aperture Terminal) pour les communications par satellite en zones isolées.

### Ce que vous allez apprendre
- Principes des communications satellites
- Installation de terminaux VSAT
- Configuration des modems
- Pointage et alignement
- Maintenance

### Prérequis
- Connaissances techniques de base",
                'level' => 'advanced',
                'price' => 299,
                'promo_price' => 219,
                'duration_minutes' => 1800,
                'thumbnail' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=800&q=80',
                'tags' => ['VSAT', 'Satellite', 'Telecom', 'RF'],
                'is_featured' => false,
            ],

            // === Cloud & Virtualisation ===
            [
                'title' => 'Maîtriser Docker & Kubernetes',
                'category' => 'Cloud & Virtualisation',
                'short_info' => 'Conteneurisez vos applications et orchestrez-les.',
                'description' => "## Pourquoi suivre cette formation ?

Apprenez à conteneuriser vos applications avec Docker et à les orchestrer à l'échelle avec Kubernetes, les standards du DevOps moderne.

### Ce que vous allez apprendre
- Création et gestion de conteneurs Docker
- Docker Compose pour le développement
- Architecture Kubernetes
- Déploiement et scaling
- Monitoring et troubleshooting

### Prérequis
- Connaissances Linux
- Notions de développement",
                'level' => 'advanced',
                'price' => 299,
                'promo_price' => 199,
                'duration_minutes' => 2400,
                'thumbnail' => 'https://images.unsplash.com/photo-1667372393119-3d4c48d07fc9?w=800&q=80',
                'tags' => ['Docker', 'Kubernetes', 'DevOps', 'Containers'],
                'is_featured' => true,
            ],
            [
                'title' => 'AWS Cloud Practitioner : Préparation',
                'category' => 'Cloud & Virtualisation',
                'short_info' => 'Certification AWS Cloud Practitioner.',
                'description' => "## Pourquoi suivre cette formation ?

Préparez-vous à la certification AWS Cloud Practitioner et découvrez les fondamentaux du cloud Amazon Web Services.

### Ce que vous allez apprendre
- Concepts fondamentaux du cloud
- Services AWS principaux (EC2, S3, RDS)
- Sécurité et conformité
- Modèles de pricing
- Architecture cloud

### Prérequis
- Aucun prérequis cloud",
                'level' => 'beginner',
                'price' => 199,
                'promo_price' => 149,
                'duration_minutes' => 1200,
                'thumbnail' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=800&q=80',
                'tags' => ['AWS', 'Cloud', 'Certification', 'Amazon'],
                'is_featured' => true,
            ],
            [
                'title' => 'Proxmox VE : Virtualisation Avancée',
                'category' => 'Cloud & Virtualisation',
                'short_info' => 'Créez votre infrastructure virtualisée avec Proxmox.',
                'description' => "## Pourquoi suivre cette formation ?

Maîtrisez Proxmox Virtual Environment pour créer et gérer des machines virtuelles et conteneurs Linux.

### Ce que vous allez apprendre
- Installation de Proxmox VE
- Création de VMs et conteneurs LXC
- Stockage et réseaux virtuels
- HA et clustering
- Sauvegardes et snapshots

### Prérequis
- Administration Linux",
                'level' => 'intermediate',
                'price' => 149,
                'promo_price' => 99,
                'duration_minutes' => 1050,
                'thumbnail' => 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&q=80',
                'tags' => ['Proxmox', 'Virtualisation', 'KVM', 'LXC'],
                'is_featured' => false,
            ],
            [
                'title' => 'Introduction au Cloud Computing',
                'category' => 'Cloud & Virtualisation',
                'short_info' => 'Comprendre les modèles cloud IaaS, PaaS, SaaS.',
                'description' => "## Pourquoi suivre cette formation ?

Découvrez les concepts du cloud computing : modèles de service, avantages, cas d'usage et fournisseurs principaux.

### Ce que vous allez apprendre
- Modèles IaaS, PaaS, SaaS
- Déploiements public vs privé
- Avantages et challenges
- Principaux fournisseurs (AWS, Azure, GCP)
- Migration vers le cloud

### Prérequis
- Aucun prérequis",
                'level' => 'beginner',
                'price' => 0,
                'promo_price' => null,
                'duration_minutes' => 400,
                'thumbnail' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=800&q=80',
                'tags' => ['Cloud', 'IaaS', 'PaaS', 'SaaS'],
                'is_featured' => false,
            ],

            // === Développement Web & App ===
            [
                'title' => 'Laravel 11 & Vue.js 3 : Masterclass',
                'category' => 'Développement Web & App',
                'short_info' => 'Bâtissez des applications SaaS robustes et scalables.',
                'description' => "## Pourquoi suivre cette formation ?

Maîtrisez le duo Laravel + Vue.js pour créer des applications web modernes avec Inertia.js et architectures API.

### Ce que vous allez apprendre
- Architecture Laravel 11
- Composants Vue.js 3 avec Composition API
- Inertia.js pour le SPA
- Authentification et autorisation
- Déploiement en production

### Prérequis
- Connaissances PHP
- Notions de JavaScript",
                'level' => 'intermediate',
                'price' => 249,
                'promo_price' => 179,
                'duration_minutes' => 2100,
                'thumbnail' => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=800&q=80',
                'tags' => ['PHP', 'Laravel', 'VueJS', 'Fullstack'],
                'is_featured' => true,
            ],
            [
                'title' => 'API REST avec Laravel : Guide Complet',
                'category' => 'Développement Web & App',
                'short_info' => 'Créez des API RESTful robustes avec Laravel.',
                'description' => "## Pourquoi suivre cette formation ?

Apprenez à concevoir et développer des API REST modernes avec Laravel : authentification, validation, documentation, tests.

### Ce que vous allez apprendre
- Conception d'API REST
- Routes et contrôleurs Laravel
- Authentification API (Sanctum, Passport)
- Validation et erreurs
- Documentation avec OpenAPI/Swagger

### Prérequis
- Connaissances Laravel de base",
                'level' => 'intermediate',
                'price' => 179,
                'promo_price' => 129,
                'duration_minutes' => 1350,
                'thumbnail' => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=800&q=80',
                'tags' => ['Laravel', 'API', 'REST', 'Backend'],
                'is_featured' => false,
            ],
            [
                'title' => 'Introduction au Développement Web',
                'category' => 'Développement Web & App',
                'short_info' => 'HTML, CSS et JavaScript pour débutants.',
                'description' => "## Pourquoi suivre cette formation ?

Découvrez les fondamentaux du développement web : HTML5, CSS3 et JavaScript. La base pour tout développeur web.

### Ce que vous allez apprendre
- Structure HTML5
- Styling CSS3 (Flexbox, Grid)
- JavaScript ES6+
- Création de pages interactives
- Bonnes pratiques web

### Prérequis
- Aucun prérequis",
                'level' => 'beginner',
                'price' => 0,
                'promo_price' => null,
                'duration_minutes' => 750,
                'thumbnail' => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=800&q=80',
                'tags' => ['HTML', 'CSS', 'JavaScript', 'Web'],
                'is_featured' => true,
            ],

            // === Systèmes & Open Source ===
            [
                'title' => 'Administration Linux LPIC-1',
                'category' => 'Systèmes & Open Source',
                'short_info' => 'Le socle indispensable pour tout administrateur.',
                'description' => "## Pourquoi suivre cette formation ?

Préparez-vous à la certification Linux Professional Institute LPIC-1 et maîtrisez l'administration Linux.

### Ce que vous allez apprendre
- Commandes Linux avancées
- Gestion des utilisateurs et groupes
- Permissions et droits
- Gestion des processus
- Scripts Bash

### Prérequis
- Utilisation basique de Linux",
                'level' => 'beginner',
                'price' => 199,
                'promo_price' => 149,
                'duration_minutes' => 1800,
                'thumbnail' => 'https://images.unsplash.com/photo-1629654297299-c8506221ca97?w=800&q=80',
                'tags' => ['Linux', 'LPIC', 'System', 'Administration'],
                'is_featured' => true,
            ],
            [
                'title' => 'Windows Server 2022 : Administration',
                'category' => 'Systèmes & Open Source',
                'short_info' => 'Gérez des environnements Windows Server.',
                'description' => "## Pourquoi suivre cette formation ?

Maîtrisez l'administration de Windows Server 2022 : Active Directory, GPO, services réseau, gestion de parc.

### Ce que vous allez apprendre
- Installation et configuration Windows Server
- Active Directory et utilisateurs
- Group Policy Objects
- Services réseau (DNS, DHCP, File)
- Monitoring et maintenance

### Prérequis
- Connaissances Windows",
                'level' => 'intermediate',
                'price' => 229,
                'promo_price' => 169,
                'duration_minutes' => 1650,
                'thumbnail' => 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&q=80',
                'tags' => ['Windows', 'Server', 'AD', 'Microsoft'],
                'is_featured' => false,
            ],
            [
                'title' => 'Automatisation avec Ansible',
                'category' => 'Systèmes & Open Source',
                'short_info' => 'Automatisez vos tâches d\'administration.',
                'description' => "## Pourquoi suivre cette formation ?

Apprenez à automatiser vos tâches d'administration système avec Ansible : playbooks, rôles, inventaires.

### Ce que vous allez apprendre
- Concepts Ansible
- Écriture de playbooks
- Gestion de l'inventaire
- Rôles et réutilisation
- Intégration CI/CD

### Prérequis
- Administration Linux/Unix",
                'level' => 'advanced',
                'price' => 199,
                'promo_price' => 149,
                'duration_minutes' => 1350,
                'thumbnail' => 'https://images.unsplash.com/photo-1629654297299-c8506221ca97?w=800&q=80',
                'tags' => ['Ansible', 'Automation', 'DevOps', 'Infrastructure'],
                'is_featured' => false,
            ],
        ];

        foreach ($courses as $courseData) {
            $category = $categories->get($courseData['category']);
            $instructor = $instructors->random();
            $slug = Str::slug($courseData['title']);

            Course::firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => $courseData['title'],
                    'description' => $courseData['description'],
                    'thumbnail_path' => $courseData['thumbnail'],
                    'image' => $courseData['thumbnail'], // Ajouté pour compatibilité avec le frontend
                    'price' => $courseData['price'],
                    'promo_price' => $courseData['promo_price'] ?? 0.00,
                    'level' => $courseData['level'],
                    'status' => 'published',
                    'is_featured' => $courseData['is_featured'],
                    'short_info' => $courseData['short_info'],
                    'tags' => $courseData['tags'],
                    'duration_minutes' => $courseData['duration_minutes'],
                    'instructor_id' => $instructor->id,
                    'category_id' => $category->id ?? $categories->first()->id,
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
        }

        $this->command->info(count($courses) . ' cours créés avec succès !');
    }
}
