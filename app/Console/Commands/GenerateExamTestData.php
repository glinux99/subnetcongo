<?php

namespace App\Console\Commands;

use App\Models\{Exam, Question, QuestionOption, ExamAttempt, ExamResult, User, Course};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateExamTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam:generate-test-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère des données de test pour les examens et questions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Début de la génération des données de test pour les examens...');

        // Récupérer les cours existants
        $courses = Course::limit(5)->get();
        if ($courses->isEmpty()) {
            $this->error('❌ Aucun cours trouvé. Veuillez d\'abord créer des cours.');
            return Command::FAILURE;
        }

        $this->info("📚 {$courses->count()} cours trouvés");

        // Récupérer les utilisateurs étudiants
        $students = User::where('id', '>', 30)->limit(5)->get();
        if ($students->isEmpty()) {
            $this->warn('⚠️  Pas assez d\'étudiants trouvés. Utilisation des utilisateurs existants.');
            $students = User::limit(5)->get();
        }

        $this->info("👥 {$students->count()} étudiants trouvés");

        // Créer les examens de test
        $exams = [];
        $examConfigs = [
            [
                'title' => 'Quiz Réseaux Informatiques - Niveau Débutant',
                'type' => 'Quiz',
                'course_id' => $courses[0]->id,
                'duration' => 30,
                'passing_score' => 70,
                'attempts' => 3,
                'description' => 'Testez vos connaissances de base en réseaux informatiques',
            ],
            [
                'title' => 'Certification Cisco CCNA - Module 1',
                'type' => 'Certification',
                'course_id' => $courses[0]->id,
                'duration' => 60,
                'passing_score' => 80,
                'attempts' => 2,
                'description' => 'Examen de certification Cisco CCNA - Module Fondamentaux',
            ],
            [
                'title' => 'Quiz MikroTik - Configuration de Base',
                'type' => 'Quiz',
                'course_id' => $courses[1]->id,
                'duration' => 45,
                'passing_score' => 75,
                'attempts' => 3,
                'description' => 'Testez vos compétences en configuration MikroTik RouterOS',
            ],
            [
                'title' => 'Test de Placement Cybersécurité',
                'type' => 'Placement',
                'course_id' => $courses[2]->id,
                'duration' => 90,
                'passing_score' => 60,
                'attempts' => 1,
                'description' => 'Évaluation de vos compétences en cybersécurité',
            ],
            [
                'title' => 'Quiz Docker & Kubernetes - Intermédiaire',
                'type' => 'Quiz',
                'course_id' => $courses[3]->id,
                'duration' => 50,
                'passing_score' => 70,
                'attempts' => 3,
                'description' => 'Testez vos connaissances en conteneurisation et orchestration',
            ],
        ];

        foreach ($examConfigs as $config) {
            $exam = $this->createExam($config);
            $exams[] = $exam;
            $this->info("✅ Examen créé: {$exam->title}");
        }

        // Créer des questions pour chaque examen
        foreach ($exams as $exam) {
            $this->info("📝 Création des questions pour: {$exam->title}");
            $questionCount = rand(10, 15);
            $this->createQuestionsForExam($exam, $questionCount);
            $this->info("✅ {$questionCount} questions créées pour {$exam->title}");
        }

        // Simuler des tentatives d'examens
        $this->info("🎯 Simulation des tentatives d'examens...");
        foreach ($exams as $exam) {
            foreach ($students as $student) {
                $this->simulateExamAttempt($exam, $student);
            }
        }

        $this->info('✨ Données de test générées avec succès!');
        $this->newLine();
        $this->table(
            ['Examen', 'Questions', 'Tentatives', 'Moyenne'],
            collect($exams)->map(function ($exam) {
                return [
                    $exam->title,
                    $exam->questions()->count(),
                    $exam->attempts()->count(),
                    round($exam->results()->avg('score_percentage') ?? 0, 1) . '%',
                ];
            })
        );

        return Command::SUCCESS;
    }

    private function createExam(array $config): Exam
    {
        return Exam::create([
            'uuid' => (string) Str::uuid(),
            'title' => $config['title'],
            'course_id' => $config['course_id'],
            'type' => $config['type'],
            'status' => Exam::STATUS_ACTIVE,
            'pass_percentage' => $config['passing_score'],
            'duration_minutes' => $config['duration'],
            'attempts_allowed' => $config['attempts'],
            'description' => $config['description'],
            'is_published' => true,
            'available_from' => now()->subDays(7),
            'available_until' => now()->addDays(30),
            'created_by' => 1,
            'settings' => [
                'shuffle_questions' => false,
                'shuffle_options' => false,
                'show_results_instantly' => true,
                'allow_review' => true,
                'proctoring_mode' => 'none',
            ],
        ]);
    }

    private function createQuestionsForExam(Exam $exam, int $count)
    {
        $questionTypes = [
            'multiple_choice' => 0.4,
            'true_false' => 0.25,
            'short_answer' => 0.2,
            'multiple_choice_multiple' => 0.1,
            'essay' => 0.05,
        ];

        $questionData = $this->getQuestionDataByCourse($exam->course_id);

        for ($i = 0; $i < $count; $i++) {
            $type = $this->getRandomType($questionTypes);
            $points = rand(1, 5);
            $data = $questionData[array_rand($questionData)];

            $question = Question::create([
                'uuid' => (string) Str::uuid(),
                'exam_id' => $exam->id,
                'question_text' => $data['question'],
                'type' => $type,
                'points' => $points,
                'explanation' => $data['explanation'] ?? null,
                'order' => $i + 1,
                'created_by' => 1,
                'status' => 'published',
            ]);

            // Créer les options selon le type
            if ($type === 'multiple_choice' && isset($data['options'])) {
                $this->createMultipleChoiceOptions($question, $data['options'], $data['correct']);
            } elseif ($type === 'multiple_choice_multiple' && isset($data['options'])) {
                $this->createMultipleChoiceMultipleOptions($question, $data['options'], $data['correct_multiple'] ?? [0]);
            } elseif ($type === 'true_false') {
                $question->update(['correct_answer' => ($data['correct'] ?? true) ? 'true' : 'false']);
            } elseif ($type === 'short_answer') {
                $question->update(['correct_answer' => $data['correct_answer'] ?? 'correct']);
            } elseif ($type === 'essay') {
                $question->update(['correct_answer' => null]); // Essay requires manual grading
            }
        }

        // Mettre à jour le total des points de l'examen
        $exam->update(['total_points' => $exam->questions()->sum('points')]);
    }

    private function getRandomType(array $types): string
    {
        $rand = mt_rand(1, 100);
        $cumulative = 0;
        foreach ($types as $type => $percentage) {
            $cumulative += $percentage * 100;
            if ($rand <= $cumulative) {
                return $type;
            }
        }
        return 'multiple_choice';
    }

    private function createMultipleChoiceOptions(Question $question, array $options, int $correctIndex)
    {
        foreach ($options as $index => $text) {
            QuestionOption::create([
                'question_id' => $question->id,
                'option_text' => $text,
                'is_correct' => $index === $correctIndex,
                'order' => $index,
            ]);
        }
        // Mettre à jour correct_answer avec l'ID de l'option correcte
        $correctOption = $question->options()->where('is_correct', true)->first();
        if ($correctOption) {
            $question->update(['correct_answer' => $correctOption->id]);
        }
    }

    private function createMultipleChoiceMultipleOptions(Question $question, array $options, array $correctIndices)
    {
        foreach ($options as $index => $text) {
            QuestionOption::create([
                'question_id' => $question->id,
                'option_text' => $text,
                'is_correct' => in_array($index, $correctIndices),
                'order' => $index,
            ]);
        }
    }

    private function getQuestionDataByCourse(int $courseId): array
    {
        // Données de questions par thème de cours
        $networkingQuestions = [
            [
                'question' => 'Quelle est la fonction principale d\'un routeur dans un réseau?',
                'options' => ['Connecter des réseaux différents', 'Stocker des données', 'Imprimer des documents', 'Gérer les utilisateurs'],
                'correct' => 0,
                'explanation' => 'Un routeur connecte différents réseaux et dirige les paquets de données entre eux.',
            ],
            [
                'question' => 'Quelle est l\'adresse IP de loopback par défaut?',
                'options' => ['192.168.1.1', '127.0.0.1', '10.0.0.1', '172.16.0.1'],
                'correct' => 1,
                'explanation' => '127.0.0.1 est l\'adresse IP de loopback utilisée pour tester la configuration réseau locale.',
                'correct_answer' => '127.0.0.1',
            ],
            [
                'question' => 'Quel protocole est utilisé pour la communication web sécurisée?',
                'options' => ['HTTP', 'FTP', 'HTTPS', 'SMTP'],
                'correct' => 2,
                'explanation' => 'HTTPS (HTTP Secure) utilise SSL/TLS pour sécuriser les communications web.',
            ],
            [
                'question' => 'Combien de bits compose une adresse IPv4?',
                'options' => ['16 bits', '32 bits', '64 bits', '128 bits'],
                'correct' => 1,
                'explanation' => 'Une adresse IPv4 est composée de 32 bits, divisée en 4 octets de 8 bits chacun.',
                'correct_answer' => '32',
            ],
            [
                'question' => 'Quel est le port par défaut du protocole SSH?',
                'options' => ['21', '22', '23', '80'],
                'correct' => 1,
                'explanation' => 'SSH utilise le port 22 par défaut pour les connexions sécurisées.',
            ],
            [
                'question' => 'Le DNS permet de:',
                'options' => ['Connecter des réseaux', 'Résoudre les noms de domaine en IP', 'Envoyer des emails', 'Télécharger des fichiers'],
                'correct' => 1,
                'explanation' => 'Le DNS (Domain Name System) traduit les noms de domaine en adresses IP.',
            ],
            [
                'question' => 'Un commutateur (switch) opère à quel couche du modèle OSI?',
                'options' => ['Couche 1', 'Couche 2', 'Couche 3', 'Couche 4'],
                'correct' => 1,
                'explanation' => 'Un switch opère à la couche 2 ( liaison de données) du modèle OSI.',
            ],
            [
                'question' => 'Quelle commande ping envoie 4 paquets sous Windows?',
                'options' => ['ping -c 4', 'ping 4', 'ping (par défaut envoie 4)', 'ping -n 4'],
                'correct' => 3,
                'explanation' => 'Sous Windows, ping -n 4 envoie 4 paquets. Sous Linux, c\'est ping -c 4.',
            ],
            [
                'question' => 'Vrai ou Faux: Un masque de sous-réseau 255.255.255.0 correspond à /24',
                'correct' => true,
                'explanation' => '255.255.255.0 a 24 bits à 1, donc /24.',
                'options' => ['Vrai', 'Faux'],
            ],
            [
                'question' => 'Quel est le rôle du protocole DHCP?',
                'options' => ['Sécuriser les communications', 'Attribuer automatiquement des adresses IP', 'Résoudre les noms de domaine', 'Transférer des fichiers'],
                'correct' => 1,
                'explanation' => 'DHCP attribue dynamiquement des adresses IP aux périphériques du réseau.',
            ],
            [
                'question' => 'Le protocole ICMP est utilisé pour:',
                'options' => ['Transfert de fichiers', 'Diagnostic réseau (ping)', 'Courrier électronique', 'Navigation web'],
                'correct' => 1,
                'explanation' => 'ICMP est utilisé pour les diagnostics réseau comme ping et traceroute.',
            ],
            [
                'question' => 'Quelle est la plage d\'adresses IP privées de classe C?',
                'options' => ['10.0.0.0 - 10.255.255.255', '172.16.0.0 - 172.31.255.255', '192.168.0.0 - 192.168.255.255', '192.0.0.0 - 192.255.255.255'],
                'correct' => 2,
                'explanation' => '192.168.0.0/16 est la plage d\'adresses privées de classe C.',
            ],
            [
                'question' => 'Quel protocole de routage est basé sur l\'état des liens?',
                'options' => ['RIP', 'OSPF', 'BGP', 'EIGRP'],
                'correct' => 1,
                'explanation' => 'OSPF est un protocole de routage à état de liens (Link-State).',
            ],
            [
                'question' => 'Vrai ou Faux: NAT permet de partager une adresse IP publique entre plusieurs périphériques',
                'correct' => true,
                'explanation' => 'NAT (Network Address Translation) permet à plusieurs périphériques de partager une IP publique.',
                'options' => ['Vrai', 'Faux'],
            ],
            [
                'question' => 'Quel est le port par défaut du protocole HTTP?',
                'options' => ['80', '443', '8080', '21'],
                'correct' => 0,
                'explanation' => 'HTTP utilise le port 80 par défaut pour les communications non sécurisées.',
            ],
        ];

        $mikrotikQuestions = [
            [
                'question' => 'Quelle commande permet de voir la configuration IP dans MikroTik?',
                'options' => ['ip address print', 'show ip', 'display ip', 'ip config show'],
                'correct' => 0,
                'explanation' => 'La commande "ip address print" affiche les adresses IP configurées.',
            ],
            [
                'question' => 'Quel est le port par défaut pour l\'accès Winbox à MikroTik?',
                'options' => ['22', '23', '8291', '80'],
                'correct' => 2,
                'explanation' => 'Winbox utilise le port 8291 par défaut.',
            ],
            [
                'question' => 'Vrai ou Faux: RouterOS peut être utilisé comme serveur VPN',
                'correct' => true,
                'explanation' => 'RouterOS supporte plusieurs protocoles VPN incluant PPTP, L2TP, SSTP, et OpenVPN.',
                'options' => ['Vrai', 'Faux'],
            ],
            [
                'question' => 'Quelle commande crée un nouveau bridge dans MikroTik?',
                'options' => ['bridge add', 'interface bridge add', 'bridge create', 'new bridge'],
                'correct' => 1,
                'explanation' => 'interface bridge add crée un nouveau pont (bridge).',
            ],
            [
                'question' => 'Le firewall MikroTik utilise:',
                'options' => ['iptables', 'chain-based rules', 'Windows Firewall', 'Cisco ACL'],
                'correct' => 1,
                'explanation' => 'Le firewall MikroTik utilise un système de règles basées sur des chaînes (chains).',
            ],
            [
                'question' => 'Quelle commande active le service DHCP?',
                'options' => ['ip dhcp-server enable', 'dhcp enable', 'service dhcp start', 'ip dhcp setup'],
                'correct' => 0,
                'explanation' => 'ip dhcp-server enable active le serveur DHCP spécifié.',
            ],
            [
                'question' => 'Vrai ou Faux: MikroTik supporte le routage BGP',
                'correct' => true,
                'explanation' => 'RouterOS supporte le protocole de routage BGP depuis la version 6.',
                'options' => ['Vrai', 'Faux'],
            ],
            [
                'question' => 'Quel outil permet de surveiller le trafic en temps réel?',
                'options' => ['torch', 'monitor', 'sniffer', 'watch'],
                'correct' => 0,
                'explanation' => 'Torch est un outil de surveillance de trafic en temps réel dans MikroTik.',
            ],
            [
                'question' => 'Comment sauvegarder la configuration MikroTik?',
                'options' => ['backup save', 'system backup save', '/export file', '/system backup save'],
                'correct' => 3,
                'explanation' => '/system backup save crée une sauvegarde de la configuration.',
            ],
            [
                'question' => 'Le système QoS dans MikroTik utilise:',
                'options' => ['Queues', 'Buckets', 'Pools', 'Streams'],
                'correct' => 0,
                'explanation' => 'MikroTik utilise des queues pour implémenter la QoS (Quality of Service).',
            ],
        ];

        $cybersecurityQuestions = [
            [
                'question' => 'Quelle est la meilleure pratique pour les mots de passe?',
                'options' => ['Utiliser le même mot de passe partout', 'Mots de passe courts et simples', 'Mots de passe longs et uniques', 'Partager les mots de passe'],
                'correct' => 2,
                'explanation' => 'Les mots de passe doivent être longs, complexes et uniques pour chaque service.',
            ],
            [
                'question' => 'Qu\'est-ce que le phishing?',
                'options' => ['Un type de pare-feu', 'Une attaque par hameçonnage', 'Un antivirus', 'Un protocole de sécurité'],
                'correct' => 1,
                'explanation' => 'Le phishing est une technique d\'ingénierie sociale pour voler des informations sensibles.',
            ],
            [
                'question' => 'Vrai ou Faux: Le chiffrement symétrique utilise la même clé pour chiffrer et déchiffrer',
                'correct' => true,
                'explanation' => 'En chiffrement symétrique, la même clé est utilisée pour les deux opérations.',
                'options' => ['Vrai', 'Faux'],
            ],
            [
                'question' => 'Quel protocole sécurise les communications web?',
                'options' => ['HTTP', 'FTP', 'TLS/SSL', 'TCP'],
                'correct' => 2,
                'explanation' => 'TLS/SSL (Transport Layer Security/Secure Sockets Layer) sécurise les communications.',
            ],
            [
                'question' => 'Qu\'est-ce qu\'un VPN?',
                'options' => ['Virtual Private Network', 'Very Public Network', 'Visual Protocol Node', 'Virtual Port Network'],
                'correct' => 0,
                'explanation' => 'VPN (Virtual Private Network) crée un tunnel sécurisé sur un réseau public.',
            ],
            [
                'question' => 'Le principe du moindre privilège signifie:',
                'options' => ['Donner tous les droits à tous', 'Donner uniquement les droits nécessaires', 'Ne donner aucun droit', 'Donner les droits seulement aux admins'],
                'correct' => 1,
                'explanation' => 'Le principe du moindre privilège accorde uniquement les permissions nécessaires.',
            ],
            [
                'question' => 'Vrai ou Faux: Un firewall peut bloquer les attaques DDoS',
                'correct' => true,
                'explanation' => 'Les firewalls modernes peuvent atténuer certaines attaques DDoS.',
                'options' => ['Vrai', 'Faux'],
            ],
            [
                'question' => 'Qu\'est-ce que le 2FA?',
                'options' => ['Two-Factor Authentication', 'Two-File Access', 'Twice-Fast Authentication', 'Two-Frequency Access'],
                'correct' => 0,
                'explanation' => '2FA (Two-Factor Authentication) nécessite deux formes de vérification.',
            ],
            [
                'question' => 'Quel type d\'attaque exploite des failles logicielles?',
                'options' => ['Phishing', 'Exploit', 'Social Engineering', 'DDoS'],
                'correct' => 1,
                'explanation' => 'Un exploit tire parti de vulnérabilités logicielles.',
            ],
            [
                'question' => 'Vrai ou Faux: Les mises à jour de sécurité sont importantes',
                'correct' => true,
                'explanation' => 'Les mises à jour corrigent les vulnérabilités découvertes.',
                'options' => ['Vrai', 'Faux'],
            ],
        ];

        $dockerQuestions = [
            [
                'question' => 'Quelle commande construit une image Docker?',
                'options' => ['docker build', 'docker create', 'docker make', 'docker image'],
                'correct' => 0,
                'explanation' => 'docker build construit une image Docker à partir d\'un Dockerfile.',
            ],
            [
                'question' => 'Quel fichier définit la configuration d\'un conteneur Docker?',
                'options' => ['Containerfile', 'Dockerfile', 'Config.yml', 'docker.conf'],
                'correct' => 1,
                'explanation' => 'Le Dockerfile contient les instructions pour construire une image Docker.',
            ],
            [
                'question' => 'Vrai ou Faux: Docker conteneurise directement le système d\'exploitation',
                'correct' => false,
                'explanation' => 'Docker conteneurise les applications, pas l\'OS complet. Il partage le noyau hôte.',
                'options' => ['Vrai', 'Faux'],
            ],
            [
                'question' => 'Quelle commande liste les conteneurs en cours d\'exécution?',
                'options' => ['docker list', 'docker ps', 'docker show', 'docker containers'],
                'correct' => 1,
                'explanation' => 'docker ps affiche les conteneurs en cours d\'exécution.',
            ],
            [
                'question' => 'Kubernetes orchestre:',
                'options' => ['Bases de données', 'Conteneurs', 'Réseaux', 'Stockage'],
                'correct' => 1,
                'explanation' => 'Kubernetes orchestre le déploiement et la gestion des conteneurs.',
            ],
            [
                'question' => 'Qu\'est-ce qu\'un Pod dans Kubernetes?',
                'options' => ['Un conteneur', 'Un groupe de conteneurs', 'Un réseau', 'Un volume'],
                'correct' => 1,
                'explanation' => 'Un Pod est le plus petit objet Kubernetes, contenant un ou plusieurs conteneurs.',
            ],
            [
                'question' => 'Vrai ou Faux: Docker Swarm est un orchestrateur de conteneurs',
                'correct' => true,
                'explanation' => 'Docker Swarm est l\'outil d\'orchestration natif de Docker.',
                'options' => ['Vrai', 'Faux'],
            ],
            [
                'question' => 'Quelle commande supprime un conteneur Docker?',
                'options' => ['docker delete', 'docker remove', 'docker rm', 'docker clear'],
                'correct' => 2,
                'explanation' => 'docker rm supprime un conteneur arrêté.',
            ],
            [
                'question' => 'Compose permet de:',
                'options' => ['Créer des images', 'Gérer des applications multi-conteneurs', 'Surveiller les logs', 'Configurer le réseau'],
                'correct' => 1,
                'explanation' => 'Docker Compose définit et exécute des applications multi-conteneurs.',
            ],
            [
                'question' => 'Vrai ou Faux: Les conteneurs Docker sont plus légers que les VM',
                'correct' => true,
                'explanation' => 'Les conteneurs partagent le noyau hôte, les sont plus légers que les VM complètes.',
                'options' => ['Vrai', 'Faux'],
            ],
        ];

        // Sélectionner les questions appropriées selon le cours
        $courseTopics = [
            1 => $networkingQuestions,  // Cisco CCNA
            2 => $mikrotikQuestions,    // MikroTik
            3 => $networkingQuestions,  // Introduction aux Réseaux
            4 => $networkingQuestions,  // Ubiquiti
            5 => $cybersecurityQuestions, // Ethical Hacking
            6 => $cybersecurityQuestions, // Sécurité Linux
            7 => $cybersecurityQuestions, // Cybersécurité
            8 => $networkingQuestions,  // Starlink
            9 => $networkingQuestions,  // Fibre Optique
            10 => $networkingQuestions, // VSAT
            11 => $dockerQuestions,     // Docker & Kubernetes
            12 => $networkingQuestions, // AWS
            13 => $networkingQuestions, // Proxmox
            14 => $networkingQuestions, // Cloud Computing
            15 => $dockerQuestions,     // Laravel
            16 => $dockerQuestions,     // API REST
            17 => $dockerQuestions,     // Dev Web
            18 => $cybersecurityQuestions, // Linux LPIC-1
            19 => $networkingQuestions, // Windows Server
            20 => $dockerQuestions,     // Ansible
        ];

        return $courseTopics[$courseId] ?? $networkingQuestions;
    }

    private function simulateExamAttempt(Exam $exam, User $student)
    {
        // Vérifier si l'étudiant a déjà tenté cet examen
        $existingAttempts = ExamAttempt::where('user_id', $student->id)
            ->where('exam_id', $exam->id)
            ->count();

        if ($existingAttempts >= $exam->attempts_allowed && $exam->attempts_allowed > 0) {
            return;
        }

        // Calculer le score
        $questions = $exam->questions()->with('options')->get();
        $totalPoints = 0;
        $earnedPoints = 0;
        $answers = [];

        foreach ($questions as $question) {
            $totalPoints += $question->points;

            // Simuler un taux de réussite aléatoire (60-90%)
            $shouldGetCorrect = rand(1, 100) <= rand(60, 90);

            if ($shouldGetCorrect) {
                // Donner la bonne réponse
                if ($question->type === 'multiple_choice') {
                    $correctOption = $question->options->where('is_correct', true)->first();
                    if ($correctOption) {
                        $answers[$question->id] = $correctOption->id;
                        $earnedPoints += $question->points;
                    }
                } elseif ($question->type === 'true_false') {
                    $answers[$question->id] = $question->correct_answer;
                    $earnedPoints += $question->points;
                } elseif ($question->type === 'short_answer') {
                    $answers[$question->id] = $question->correct_answer;
                    $earnedPoints += $question->points;
                }
            } else {
                // Donner une mauvaise réponse aléatoire
                if ($question->type === 'multiple_choice') {
                    $wrongOption = $question->options->where('is_correct', false)->first();
                    if ($wrongOption) {
                        $answers[$question->id] = $wrongOption->id;
                    }
                } elseif ($question->type === 'true_false') {
                    $answers[$question->id] = $question->correct_answer === 'true' ? 'false' : 'true';
                } elseif ($question->type === 'short_answer') {
                    $answers[$question->id] = 'wrong answer';
                }
            }
        }

        $scorePercentage = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;
        $isPassed = $scorePercentage >= $exam->pass_percentage;

        // Créer une tentative
        $attempt = ExamAttempt::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'started_at' => now()->subMinutes(rand(10, $exam->duration_minutes)),
            'completed_at' => now(),
            'status' => ExamAttempt::STATUS_COMPLETED,
            'answers' => $answers,
            'raw_score' => $earnedPoints,
            'ip_address' => '192.168.1.' . rand(1, 254),
            'user_agent' => 'Mozilla/5.0 (Test Browser)',
            'time_spent_seconds' => rand(300, $exam->duration_minutes * 60),
        ]);

        // Créer le résultat
        ExamResult::create([
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'course_id' => $exam->course_id,
            'exam_attempt_id' => $attempt->id,
            'total_points' => $earnedPoints,
            'score_percentage' => $scorePercentage,
            'is_passed' => $isPassed,
            'passed_at' => $isPassed ? now() : null,
            'feedback' => $isPassed
                ? 'Félicitations! Vous avez réussi cet examen.'
                : 'Continuez vos efforts. Vous n\'avez pas atteint le seuil de réussite.',
        ]);

        $this->line("  📊 {$student->name}: {$scorePercentage}% " . ($isPassed ? '✅' : '❌'));
    }
}
