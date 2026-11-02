<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::where('has_final_exam', true)->get();

        foreach ($courses as $course) {
            $this->createExamForCourse($course);
        }

        $this->command->info('Examens et questions créés pour les cours avec examen final !');
    }

    private function createExamForCourse(Course $course): void
    {
        // Vérifier si un examen existe déjà pour ce cours
        $existingExam = Exam::where('course_id', $course->id)
            ->where('is_final_exam', true)
            ->first();

        if ($existingExam) {
            return;
        }

        $exam = Exam::create([
            'course_id' => $course->id,
            'uuid' => (string) Str::uuid(),
            'title' => "Examen Final : {$course->title}",
            'description' => "Évaluation finale pour valider vos connaissances acquises dans le cours {$course->title}.",
            'type' => 'Certification',
            'status' => 'Active',
            'pass_percentage' => 60.00,
            'duration_minutes' => 60,
            'attempts_allowed' => 3,
            'is_published' => true,
            'randomize_questions' => true,
            'randomize_options' => true,
            'show_correct_answers' => true,
            'show_results_immediately' => true,
            'is_final_exam' => true,
            'created_by' => $course->instructor_id,
        ]);

        // Créer 10 questions par examen
        $questions = $this->getQuestionsForCategory($course->category->name);

        foreach ($questions as $index => $questionData) {
            $question = Question::create([
                'exam_id' => $exam->id,
                'uuid' => (string) Str::uuid(),
                'question_text' => $questionData['question'],
                'type' => 'multiple_choice',
                'points' => 10,
                'order' => $index + 1,
                'explanation' => $questionData['explanation'] ?? null,
            ]);

            // Créer les options
            foreach ($questionData['options'] as $optionIndex => $optionText) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => $optionText,
                    'is_correct' => $optionIndex === $questionData['correct'],
                    'order' => $optionIndex + 1,
                ]);
            }
        }
    }

    private function getQuestionsForCategory(string $category): array
    {
        if ($category === 'Infrastructure & Réseaux') {
            return [
                [
                    'question' => 'Quel protocole est utilisé pour l\'échange d\'informations de routage entre routeurs ?',
                    'options' => ['HTTP', 'OSPF', 'FTP', 'DNS'],
                    'correct' => 1,
                    'explanation' => 'OSPF (Open Shortest Path First) est un protocole de routage dynamique utilisé pour échanger des informations de routage.',
                ],
                [
                    'question' => 'Quelle est la fonction principale d\'un commutateur (switch) ?',
                    'options' => ['Connecter des réseaux différents', 'Acheminer les paquets vers le bon port', 'Traduire les adresses IP', 'Bloquer les attaques réseau'],
                    'correct' => 1,
                    'explanation' => 'Un switch achemine les paquets vers le bon port en utilisant les adresses MAC.',
                ],
                [
                    'question' => 'Quel est le port par défaut pour SSH ?',
                    'options' => ['21', '22', '23', '80'],
                    'correct' => 1,
                    'explanation' => 'SSH utilise le port 22 par défaut pour les connexions sécurisées.',
                ],
                [
                    'question' => 'Quelle commande permet de tester la connectivité réseau ?',
                    'options' => ['ipconfig', 'ping', 'tracert', 'netstat'],
                    'correct' => 1,
                    'explanation' => 'La commande ping envoie des paquets ICMP pour tester la connectivité.',
                ],
                [
                    'question' => 'Quelle classe d\'adresse IP permet le plus d\'hôtes ?',
                    'options' => ['Classe A', 'Classe B', 'Classe C', 'Classe D'],
                    'correct' => 0,
                    'explanation' => 'La classe A (0.0.0.0 - 127.255.255.255) permet plus de 16 millions d\'hôtes.',
                ],
                [
                    'question' => 'Quel protocole traduit les noms de domaine en adresses IP ?',
                    'options' => ['DHCP', 'DNS', 'ARP', 'SNMP'],
                    'correct' => 1,
                    'explanation' => 'DNS (Domain Name System) traduit les noms de domaine en adresses IP.',
                ],
                [
                    'question' => 'Quelle couche du modèle OSI gère le routage ?',
                    'options' => ['Couche 2 (Liaison de données)', 'Couche 3 (Réseau)', 'Couche 4 (Transport)', 'Couche 7 (Application)'],
                    'correct' => 1,
                    'explanation' => 'La couche 3 (Réseau) du modèle OSI gère le routage des paquets.',
                ],
                [
                    'question' => 'Qu\'est-ce qu\'un VLAN ?',
                    'options' => ['Un type de routeur', 'Un réseau local virtuel', 'Un protocole de sécurité', 'Un câblage spécial'],
                    'correct' => 1,
                    'explanation' => 'Un VLAN (Virtual Local Area Network) permet de segmenter un réseau physique en plusieurs réseaux logiques.',
                ],
                [
                    'question' => 'Quel protocole est utilisé pour l\'assignation automatique d\'adresses IP ?',
                    'options' => ['DNS', 'DHCP', 'ARP', 'ICMP'],
                    'correct' => 1,
                    'explanation' => 'DHCP (Dynamic Host Configuration Protocol) assigne automatiquement des adresses IP.',
                ],
                [
                    'question' => 'Quelle est la longueur d\'une adresse IPv6 ?',
                    'options' => ['32 bits', '64 bits', '128 bits', '256 bits'],
                    'correct' => 2,
                    'explanation' => 'Une adresse IPv6 fait 128 bits, soit 8 groupes de 4 caractères hexadécimaux.',
                ],
            ];
        }

        if ($category === 'Cybersécurité') {
            return [
                [
                    'question' => 'Qu\'est-ce que le phishing ?',
                    'options' => ['Une attaque par force brute', 'Une technique d\'ingénierie sociale', 'Un malware informatique', 'Une vulnérabilité réseau'],
                    'correct' => 1,
                    'explanation' => 'Le phishing est une technique d\'ingénierie sociale visant à tromper les victimes pour obtenir des informations sensibles.',
                ],
                [
                    'question' => 'Quel principe de sécurité signifie que seules les personnes autorisées peuvent accéder aux données ?',
                    'options' => ['Intégrité', 'Confidentialité', 'Disponibilité', 'Authenticité'],
                    'correct' => 1,
                    'explanation' => 'La confidentialité garantit que seules les personnes autorisées peuvent accéder aux données.',
                ],
                [
                    'question' => 'Qu\'est-ce qu\'un honeypot ?',
                    'options' => ['Un type de firewall', 'Un système leurre pour attirer les attaquants', 'Un outil de scan de vulnérabilités', 'Un antivirus'],
                    'correct' => 1,
                    'explanation' => 'Un honeypot est un système leurre conçu pour attirer les attaquants et étudier leurs techniques.',
                ],
                [
                    'question' => 'Quel outil est utilisé pour le scanning de ports ?',
                    'options' => ['Wireshark', 'Nmap', 'Metasploit', 'Burp Suite'],
                    'correct' => 1,
                    'explanation' => 'Nmap est l\'outil le plus utilisé pour le scanning de ports et la découverte réseau.',
                ],
                [
                    'question' => 'Qu\'est-ce que l\'authentification à deux facteurs (2FA) ?',
                    'options' => ['Un seul mot de passe', 'Deux formes d\'identification différentes', 'Deux utilisateurs pour un compte', 'Deux firewalls'],
                    'correct' => 1,
                    'explanation' => 'La 2FA nécessite deux formes d\'identification différentes (ex: mot de passe + code SMS).',
                ],
                [
                    'question' => 'Quelle attaque exploite une injection de code malveillant dans une base de données ?',
                    'options' => ['XSS', 'CSRF', 'SQL Injection', 'DDoS'],
                    'correct' => 2,
                    'explanation' => 'SQL Injection exploite des vulnérabilités dans les requêtes base de données.',
                ],
                [
                    'question' => 'Qu\'est-ce que le chiffrement symétrique ?',
                    'options' => ['Une clé publique et privée', 'La même clé pour chiffrer et déchiffrer', 'Pas de clé', 'Deux clés différentes'],
                    'correct' => 1,
                    'explanation' => 'Le chiffrement symétrique utilise la même clé pour chiffrer et déchiffrer les données.',
                ],
                [
                    'question' => 'Quel est le but d\'un pentest ?',
                    'options' => ['Détruire le système', 'Identifier les vulnérabilités', 'Voler des données', 'Installer des malwares'],
                    'correct' => 1,
                    'explanation' => 'Un pentest (test de pénétration) vise à identifier les vulnérabilités pour les corriger.',
                ],
                [
                    'question' => 'Qu\'est-ce qu\'un malware ?',
                    'options' => ['Un logiciel légitime', 'Un logiciel malveillant', 'Un antivirus', 'Un pare-feu'],
                    'correct' => 1,
                    'explanation' => 'Un malware est un logiciel malveillant conçu pour endommager ou compromettre un système.',
                ],
                [
                    'question' => 'Quel protocole sécurise les communications web ?',
                    'options' => ['HTTP', 'HTTPS', 'FTP', 'SMTP'],
                    'correct' => 1,
                    'explanation' => 'HTTPS utilise TLS/SSL pour sécuriser les communications web.',
                ],
            ];
        }

        if ($category === 'Télécoms & Satellites') {
            return [
                [
                    'question' => 'Quelle bande de fréquence est utilisée pour Starlink ?',
                    'options' => ['VHF', 'Ku/Ka', 'UHF', 'HF'],
                    'correct' => 1,
                    'explanation' => 'Starlink utilise les bandes Ku et Ka pour les communications satellites.',
                ],
                [
                    'question' => 'Qu\'est-ce que VSAT ?',
                    'options' => ['Un type de fibre', 'Very Small Aperture Terminal', 'Un protocole réseau', 'Un logiciel'],
                    'correct' => 1,
                    'explanation' => 'VSAT (Very Small Aperture Terminal) désigne les petites antennes satellite pour communications.',
                ],
                [
                    'question' => 'Quelle est l\'orbite typique des satellites de télécommunications ?',
                    'options' => ['LEO', 'GEO', 'MEO', 'HEO'],
                    'correct' => 1,
                    'explanation' => 'Les satellites de télécommunications sont principalement en orbite géostationnaire (GEO).',
                ],
                [
                    'question' => 'Qu\'est-ce que la latence satellite ?',
                    'options' => ['La vitesse du signal', 'Le délai de transmission', 'La qualité du signal', 'La fréquence'],
                    'correct' => 1,
                    'explanation' => 'La latence est le délai de transmission entre l\'émission et la réception du signal.',
                ],
                [
                    'question' => 'Quel équipement est nécessaire pour recevoir Starlink ?',
                    'options' => ['Une antenne TV', 'Une antenne phased-array', 'Un modem fibre', 'Un routeur WiFi seul'],
                    'correct' => 1,
                    'explanation' => 'Starlink utilise une antenne phased-array orientable électroniquement.',
                ],
                [
                    'question' => 'Qu\'est-ce que le dBm ?',
                    'options' => ['Une unité de temps', 'Une unité de puissance', 'Une unité de fréquence', 'Une unité de distance'],
                    'correct' => 1,
                    'explanation' => 'Le dBm (décibel-milliwatt) est une unité de mesure de puissance du signal.',
                ],
                [
                    'question' => 'Quelle technologie utilise la fibre optique pour transmettre des données ?',
                    'options' => ['Électricité', 'Lumière', 'Ondes radio', 'Son'],
                    'correct' => 1,
                    'explanation' => 'La fibre optique utilise la lumière pour transmettre les données à très haut débit.',
                ],
                [
                    'question' => 'Qu\'est-ce que FTTH ?',
                    'options' => ['Fiber To The Home', 'Fast Transmission To Home', 'Fiber Technology Two Hubs', 'Free To The House'],
                    'correct' => 0,
                    'explanation' => 'FTTH (Fiber To The Home) signifie fibre jusqu\'au domicile.',
                ],
                [
                    'question' => 'Quel outil permet de tester une fibre optique ?',
                    'options' => ['Multimètre', 'OTDR', 'Oscilloscope', 'Analyseur de spectre'],
                    'correct' => 1,
                    'explanation' => 'L\'OTDR (Optical Time Domain Reflectometer) est utilisé pour tester et caractériser les fibres optiques.',
                ],
                [
                    'question' => 'Quelle est la vitesse typique d\'une connexion Starlink ?',
                    'options' => ['1 Mbps', '10 Mbps', '50-200 Mbps', '1000 Mbps'],
                    'correct' => 2,
                    'explanation' => 'Starlink offre des débits typiques entre 50 et 200 Mbps.',
                ],
            ];
        }

        if ($category === 'Cloud & Virtualisation') {
            return [
                [
                    'question' => 'Qu\'est-ce qu\'un conteneur Docker ?',
                    'options' => ['Une machine virtuelle', 'Une application packagée avec ses dépendances', 'Un système d\'exploitation', 'Un base de données'],
                    'correct' => 1,
                    'explanation' => 'Un conteneur Docker package une application avec toutes ses dépendances pour s\'exécuter de manière isolée.',
                ],
                [
                    'question' => 'Quel fichier est utilisé pour construire une image Docker ?',
                    'options' => ['docker-compose.yml', 'Dockerfile', 'docker-run.sh', 'docker.cfg'],
                    'correct' => 1,
                    'explanation' => 'Le Dockerfile contient les instructions pour construire une image Docker.',
                ],
                [
                    'question' => 'Qu\'est-ce que Kubernetes ?',
                    'options' => ['Un type de conteneur', 'Un système d\'orchestration de conteneurs', 'Un système de fichiers', 'Un éditeur de code'],
                    'correct' => 1,
                    'explanation' => 'Kubernetes est un système d\'orchestration pour gérer des conteneurs à grande échelle.',
                ],
                [
                    'question' => 'Quel service AWS est utilisé pour le calcul (compute) ?',
                    'options' => ['S3', 'EC2', 'RDS', 'Lambda'],
                    'correct' => 1,
                    'explanation' => 'EC2 (Elastic Compute Cloud) est le service de calcul d\'AWS.',
                ],
                [
                    'question' => 'Qu\'est-ce que l\'IaaS ?',
                    'options' => ['Infrastructure as a Service', 'Internet as a Service', 'Integration as a Service', 'Information as a Service'],
                    'correct' => 0,
                    'explanation' => 'IaaS (Infrastructure as a Service) fournit une infrastructure informatique virtualisée.',
                ],
                [
                    'question' => 'Quelle commande démarre un conteneur Docker ?',
                    'options' => ['docker build', 'docker run', 'docker start', 'docker ps'],
                    'correct' => 1,
                    'explanation' => 'docker run crée et démarre un nouveau conteneur.',
                ],
                [
                    'question' => 'Qu\'est-ce qu\'un Pod dans Kubernetes ?',
                    'options' => ['Un type de stockage', 'Un groupe de conteneurs', 'Un réseau virtuel', 'Une base de données'],
                    'correct' => 1,
                    'explanation' => 'Un Pod est le plus petit objet Kubernetes, contenant un ou plusieurs conteneurs.',
                ],
                [
                    'question' => 'Quel service AWS est utilisé pour le stockage objet ?',
                    'options' => ['EC2', 'S3', 'RDS', 'VPC'],
                    'correct' => 1,
                    'explanation' => 'S3 (Simple Storage Service) est le service de stockage objet d\'AWS.',
                ],
                [
                    'question' => 'Qu\'est-ce que Docker Compose ?',
                    'options' => ['Un éditeur Docker', 'Un outil pour définir des applications multi-conteneurs', 'Un type de conteneur', 'Un système de monitoring'],
                    'correct' => 1,
                    'explanation' => 'Docker Compose permet de définir et exécuter des applications multi-conteneurs.',
                ],
                [
                    'question' => 'Quelle caractéristique distingue les conteneurs des VMs ?',
                    'options' => ['Plus lourds', 'Partagent le noyau de l\'OS hôte', 'Nécessitent un OS complet', 'Plus lents'],
                    'correct' => 1,
                    'explanation' => 'Les conteneurs partagent le noyau de l\'OS hôte, les rendant plus légers que les VMs.',
                ],
            ];
        }

        if ($category === 'Développement Web & App') {
            return [
                [
                    'question' => 'Quelle balise HTML est utilisée pour créer un lien ?',
                    'options' => ['<link>', '<a>', '<href>', '<url>'],
                    'correct' => 1,
                    'explanation' => 'La balise <a> (anchor) est utilisée pour créer des liens hypertexte.',
                ],
                [
                    'question' => 'Quel mot-clé CSS permet de centrer horizontalement un élément flex ?',
                    'options' => ['align-center', 'justify-content: center', 'text-align: center', 'margin: auto'],
                    'correct' => 1,
                    'explanation' => 'justify-content: center centre les éléments dans un conteneur flex.',
                ],
                [
                    'question' => 'Quelle méthode HTTP est utilisée pour envoyer des données au serveur ?',
                    'options' => ['GET', 'POST', 'PUT', 'DELETE'],
                    'correct' => 1,
                    'explanation' => 'POST est utilisé pour envoyer des données au serveur dans le corps de la requête.',
                ],
                [
                    'question' => 'Qu\'est-ce que Laravel ?',
                    'options' => ['Un framework JavaScript', 'Un framework PHP', 'Un base de données', 'Un système d\'exploitation'],
                    'correct' => 1,
                    'explanation' => 'Laravel est un framework PHP pour le développement d\'applications web.',
                ],
                [
                    'question' => 'Quelle commande Artisan crée un contrôleur Laravel ?',
                    'options' => ['php artisan make:model', 'php artisan make:controller', 'php artisan make:migration', 'php artisan serve'],
                    'correct' => 1,
                    'explanation' => 'php artisan make:controller crée un nouveau contrôleur Laravel.',
                ],
                [
                    'question' => 'Qu\'est-ce que Vue.js ?',
                    'options' => ['Un framework PHP', 'Un framework JavaScript', 'Une base de données', 'Un serveur web'],
                    'correct' => 1,
                    'explanation' => 'Vue.js est un framework JavaScript progressif pour construire des interfaces utilisateur.',
                ],
                [
                    'question' => 'Quelle propriété Vue.js est utilisée pour la réactivité ?',
                    'options' => ['data', 'computed', 'methods', 'watch'],
                    'correct' => 0,
                    'explanation' => 'La propriété data contient les données réactives d\'un composant Vue.',
                ],
                [
                    'question' => 'Qu\'est-ce qu\'une API REST ?',
                    'options' => ['Un langage de programmation', 'Une architecture d\'API web', 'Une base de données', 'Un protocole réseau'],
                    'correct' => 1,
                    'explanation' => 'REST est une architecture d\'API basée sur les verbes HTTP et les ressources.',
                ],
                [
                    'question' => 'Quel code HTTP indique une réussite ?',
                    'options' => ['400', '404', '200', '500'],
                    'correct' => 2,
                    'explanation' => 'Le code HTTP 200 indique que la requête a réussi.',
                ],
                [
                    'question' => 'Qu\'est-ce que l\'Eloquent ORM dans Laravel ?',
                    'options' => ['Un système de template', 'Un ORM pour interagir avec la base de données', 'Un système de routing', 'Un outil de cache'],
                    'correct' => 1,
                    'explanation' => 'Eloquent est l\'ORM de Laravel pour interagir avec la base de données de manière orientée objet.',
                ],
            ];
        }

        if ($category === 'Systèmes & Open Source') {
            return [
                [
                    'question' => 'Quelle commande Linux liste les fichiers du répertoire courant ?',
                    'options' => ['list', 'dir', 'ls', 'show'],
                    'correct' => 2,
                    'explanation' => 'La commande ls liste les fichiers et répertoires du répertoire courant.',
                ],
                [
                    'question' => 'Quelle commande Linux change les permissions d\'un fichier ?',
                    'options' => ['chown', 'chmod', 'chgrp', 'attr'],
                    'correct' => 1,
                    'explanation' => 'chmod change les permissions (mode) d\'un fichier ou répertoire.',
                ],
                [
                    'question' => 'Qu\'est-ce que Bash ?',
                    'options' => ['Un éditeur de texte', 'Un shell Unix', 'Un gestionnaire de paquets', 'Un système de fichiers'],
                    'correct' => 1,
                    'explanation' => 'Bash (Bourne Again SHell) est un shell Unix/command-line interpreter.',
                ],
                [
                    'question' => 'Quelle commande affiche les processus en cours ?',
                    'options' => ['ls', 'ps', 'top', 'show'],
                    'correct' => 1,
                    'explanation' => 'ps affiche les processus en cours d\'exécution.',
                ],
                [
                    'question' => 'Qu\'est-ce que le LPIC-1 ?',
                    'options' => ['Une certification Linux', 'Un logiciel Linux', 'Une distribution Linux', 'Un shell'],
                    'correct' => 0,
                    'explanation' => 'LPIC-1 est la certification niveau 1 du Linux Professional Institute.',
                ],
                [
                    'question' => 'Quelle commande installe un paquet sur Debian/Ubuntu ?',
                    'options' => ['rpm', 'yum', 'apt-get', 'pacman'],
                    'correct' => 2,
                    'explanation' => 'apt-get est le gestionnaire de paquets sur Debian/Ubuntu.',
                ],
                [
                    'question' => 'Qu\'est-ce qu\'Ansible ?',
                    'options' => ['Un langage de programmation', 'Un outil d\'automatisation IT', 'Un système d\'exploitation', 'Un éditeur de code'],
                    'correct' => 1,
                    'explanation' => 'Ansible est un outil d\'automatisation IT pour la configuration et le déploiement.',
                ],
                [
                    'question' => 'Quel fichier contient les utilisateurs Linux ?',
                    'options' => ['/etc/passwd', '/etc/users', '/etc/shadow', '/etc/group'],
                    'correct' => 0,
                    'explanation' => '/etc/passwd contient les informations des comptes utilisateurs.',
                ],
                [
                    'question' => 'Quelle commande édite un fichier en mode terminal ?',
                    'options' => ['edit', 'nano', 'write', 'open'],
                    'correct' => 1,
                    'explanation' => 'nano est un éditeur de texte simple en mode terminal.',
                ],
                [
                    'question' => 'Qu\'est-ce qu\'un service systemd ?',
                    'options' => ['Un type de fichier', 'Un gestionnaire de services et processus', 'Un système de fichiers', 'Un éditeur'],
                    'correct' => 1,
                    'explanation' => 'systemd est un système d\'initialisation et gestionnaire de services pour Linux.',
                ],
            ];
        }

        // Questions génériques par défaut
        return [
            [
                'question' => 'Quelle est la bonne réponse ?',
                'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
                'correct' => 0,
                'explanation' => 'C\'est la bonne réponse.',
            ],
        ];
    }
}
