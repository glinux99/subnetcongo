<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Seeder d'un cours RÉEL, prêt à l'emploi (LOCAL UNIQUEMENT).
 *
 * « Les Fondamentaux des Réseaux Informatiques » — cours complet de niveau
 * débutant avec de vrais contenus :
 *   - vidéos YouTube réelles (PowerCert Animated Videos, Jeremy's IT Lab, danscourses)
 *   - leçons texte rédigées (OSI, TCP/IP, subnetting pas à pas, DNS)
 *   - documents PDF générés (fiche de révision OSI, table de subnetting)
 *   - quiz de fin de module (exams avec questions QCM / vrai-faux / réponse courte)
 *   - devoir à rendre (assignment avec échéance et barème)
 *   - examen final de fin de cours (10 questions, 60 % de réussite)
 *
 * Inscrit genesiskikimba@gmail.com au cours. Idempotent (updateOrCreate/firstOrCreate).
 */
class RealCourseSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::role('instructor')->first() ?? User::find(6);
        $category = Category::find(1) ?? Category::first();

        $course = Course::updateOrCreate(
            ['slug' => 'fondamentaux-reseaux-informatiques'],
            [
                'title' => 'Les Fondamentaux des Réseaux Informatiques',
                'description' => '<p><strong>Comprenez enfin comment fonctionne un réseau.</strong> Cette formation pose des bases solides : le modèle OSI et TCP/IP, les équipements (switch, routeur, pare-feu), l\'adressage IPv4 et le subnetting, puis les grands services réseau (DHCP, DNS). Chaque module combine des vidéos animées, des leçons à lire, des fiches PDF téléchargeables et des quiz corrigés.</p>',
                'short_info' => 'OSI, TCP/IP, adressage IPv4, subnetting, DHCP et DNS expliqués simplement, avec quiz et TP corrigés.',
                'level' => 'beginner',
                'language' => 'fr',
                'status' => 'published',
                'is_featured' => false,
                'requires_enrollment' => true,
                'certificate_enabled' => true,
                'duration_minutes' => 210,
                'price' => 29.99,
                'tags' => ['réseaux', 'tcp/ip', 'subnetting', 'ccna', 'débutant'],
                'prerequisites' => [
                    'Aucune expérience réseau requise : nous partons de zéro.',
                    'Un ordinateur avec un accès Internet pour les TP.',
                ],
                'instructor_id' => $instructor?->id,
                'category_id' => $category?->id,
                'created_by' => $instructor?->id,
                'published_at' => now(),
            ]
        );

        // ------------------------------------------------------------------
        // Module 1 — Introduction aux réseaux
        // ------------------------------------------------------------------
        $m1 = Module::updateOrCreate(
            ['course_id' => $course->id, 'slug' => 'introduction-aux-reseaux'],
            [
                'title' => 'Introduction aux réseaux',
                'description' => 'À quoi sert un réseau ? Comprenez les notions de LAN, WAN, équipements et le modèle OSI à travers une vraie requête web.',
                'order' => 1,
                'status' => 'published',
                'is_published' => true,
            ]
        );
        $this->lesson($m1, 1, [
            'title' => 'Bienvenue : objectifs, programme et prérequis',
            'type' => Lesson::TYPE_TEXT,
            'duration' => 10,
            'is_free_preview' => true,
            'content_html' => <<<'HTML'
                <h2>Bienvenue dans la formation !</h2>
                <p>Dans ce cours, vous allez comprendre <strong>comment fonctionnent les réseaux informatiques</strong>, de la prise Ethernet jusqu'aux services qui font tourner Internet. Aucune expérience n'est nécessaire : chaque notion est expliquée avec des schémas et des exemples concrets.</p>
                <h3>À qui s'adresse ce cours ?</h3>
                <ul>
                    <li>Étudiants et débutants qui veulent une base solide en réseaux ;</li>
                    <li>Techniciens helpdesk qui souhaitent monter en compétences ;</li>
                    <li>Préparation aux certifications (CCNA, Network+).</li>
                </ul>
                <h3>Le programme</h3>
                <ol>
                    <li><strong>Module 1</strong> — Introduction : panorama des réseaux et du modèle OSI.</li>
                    <li><strong>Module 2</strong> — Le modèle OSI et TCP/IP en profondeur.</li>
                    <li><strong>Module 3</strong> — Adressage IPv4 et subnetting (la compétence clé de tout technicien).</li>
                    <li><strong>Module 4</strong> — Les services réseau : DHCP et DNS.</li>
                    <li><strong>Examen final</strong> — 10 questions, 30 minutes, débloqué après les 4 modules.</li>
                </ol>
                <h3>Prérequis</h3>
                <p>Un simple ordinateur avec accès Internet. Pour les TP de configuration, Cisco Packet Tracer (gratuit) est un plus, mais il n'est pas obligatoire.</p>
                <blockquote>Conseil de méthode : ne passez pas au module suivant tant que le quiz du module en cours n'est pas réussi. Les notions s'enchaînent.</blockquote>
            HTML,
        ]);
        $this->lesson($m1, 2, [
            'title' => 'Vidéo : le modèle OSI expliqué avec un exemple réel',
            'type' => Lesson::TYPE_VIDEO,
            'duration' => 12,
            'is_free_preview' => true,
            'video_url' => 'https://www.youtube.com/watch?v=LANW3m7UgWs',
            'content_html' => <<<'HTML'
                <p>Dans cette vidéo animée, l'auteur suit <strong>une vraie requête web</strong> à travers les 7 couches du modèle OSI : de la saisie de l'adresse dans le navigateur jusqu'aux bits envoyés sur le câble. C'est la meilleure porte d'entrée pour comprendre l'empilement des couches.</p>
                <p><strong>À retenir pendant la visionnage :</strong></p>
                <ul>
                    <li>Chaque couche ajoute une <em>en-tête</em> aux données (encapsulation) ;</li>
                    <li>Les couches 1-2 (physique, liaison) gèrent le local : câbles, MAC, switch ;</li>
                    <li>La couche 3 (réseau) gère le routage entre réseaux grâce à l'IP ;</li>
                    <li>La couche 4 (transport) gère TCP/UDP et les ports ;</li>
                    <li>Les couches 5-7 sont gérées par les applications (HTTP, TLS…).</li>
                </ul>
            HTML,
        ]);
        $this->lesson($m1, 3, [
            'title' => 'Quiz de fin de module 1',
            'type' => Lesson::TYPE_QUIZ,
            'duration' => 8,
            'quiz' => $this->makeExam($course, 'Quiz module 1 — Introduction aux réseaux', [
                'pass_percentage' => 70,
                'duration_minutes' => 8,
                'attempts_allowed' => 3,
                'description' => 'Vérifiez que vous maîtrisez le vocabulaire de base et le modèle OSI.',
            ], $this->quizM1()),
        ]);

        // ------------------------------------------------------------------
        // Module 2 — OSI et TCP/IP en profondeur
        // ------------------------------------------------------------------
        $m2 = Module::updateOrCreate(
            ['course_id' => $course->id, 'slug' => 'modele-osi-tcp-ip'],
            [
                'title' => 'Le modèle OSI et TCP/IP en profondeur',
                'description' => 'Couche par couche : équipements, protocoles, encapsulation. Avec une fiche de révision PDF et un exercice à rendre.',
                'order' => 2,
                'status' => 'published',
                'is_published' => true,
            ]
        );
        $this->lesson($m2, 1, [
            'title' => 'Les 7 couches, du câble à l\'application',
            'type' => Lesson::TYPE_TEXT,
            'duration' => 25,
            'content_html' => $this->osiLessonHtml(),
        ]);
        $this->lesson($m2, 2, [
            'title' => 'Fiche de révision : OSI vs TCP/IP (PDF)',
            'type' => Lesson::TYPE_DOCUMENT,
            'duration' => 15,
            'document_title' => 'Fiche de révision — Modèle OSI et TCP/IP.pdf',
            'document_url' => $this->pdfRevisionOsi(),
            'attachment_paths' => [$this->pdfRevisionOsi()],
            'has_attachment' => true,
            'attachment_title' => 'Fiche à imprimer',
            'content_html' => '<p>Imprimez cette fiche et gardez-la à portée de main : elle résume les 7 couches, les protocoles de chaque couche et la correspondance avec le modèle TCP/IP à 4 couches. Un <strong>côté « mémo examen »</strong> et un <strong>côté « tableau des protocoles »</strong>.</p>',
        ]);
        $this->lesson($m2, 3, [
            'title' => 'Devoir : raconter une requête web couche par couche',
            'type' => Lesson::TYPE_ASSIGNMENT,
            'duration' => 45,
            'assignment' => Assignment::firstOrCreate(
                ['course_id' => $course->id, 'title' => 'Devoir — Une requête web, couche par couche'],
                [
                    'description' => 'Décrivez en 15 à 30 lignes le trajet complet d\'une requête https://subnetcongo.net depuis votre navigateur : couches OSI traversées, encapsulation, protocoles utilisés (DNS, TCP, TLS, HTTP), rôle du switch et du routeur. Formats acceptés : texte ou PDF.',
                    'max_points' => 20,
                    'due_at' => now()->addDays(7),
                    'type' => 'Devoir',
                ]
            ),
            'content_html' => <<<'HTML'
                <h2>Énoncé</h2>
                <p>Tapez une adresse dans un navigateur : <code>https://subnetcongo.net</code>. Votre mission : <strong>raconter ce voyage couche par couche</strong>.</p>
                <h3>Consignes</h3>
                <ol>
                    <li>Partez de la couche 7 (application : HTTP/TLS) et descendez jusqu'à la couche 1 ;</li>
                    <li>Pour chaque couche : nommez-la, indiquez les données (segment, paquet, trame, bits) et les adresses utilisées (ports, IP, MAC) ;</li>
                    <li>Expliquez le rôle du <strong>switch</strong> (couche 2) et du <strong>routeur</strong> (couche 3) ;</li>
                    <li>Mentionnez le service qui traduit le nom de domaine avant tout le reste.</li>
                </ol>
                <p><strong>Barème (20 points) :</strong> exactitude des couches (6), encapsulation (5), adresses/ports (5), clarté de la rédaction (4).</p>
            HTML,
        ]);

        // ------------------------------------------------------------------
        // Module 3 — Adressage IPv4 et subnetting
        // ------------------------------------------------------------------
        $m3 = Module::updateOrCreate(
            ['course_id' => $course->id, 'slug' => 'adressage-ipv4-subnetting'],
            [
                'title' => 'Adressage IPv4 et subnetting',
                'description' => 'La compétence n°1 des techniciens réseau : lire une adresse IP, choisir un masque, découper un réseau et calculer des plages.',
                'order' => 3,
                'status' => 'published',
                'is_published' => true,
            ]
        );
        $this->lesson($m3, 1, [
            'title' => 'Vidéo : l\'adresse IP et le masque de sous-réseau',
            'type' => Lesson::TYPE_VIDEO,
            'duration' => 18,
            'video_url' => 'https://www.youtube.com/watch?v=s_Ntt6eTn94',
            'content_html' => <<<'HTML'
                <p>Cette vidéo animée (4,3 millions de vues) explique <strong>ce qu'est une adresse IP</strong>, comment la partie réseau et la partie hôte sont séparées par le masque, et pourquoi on découpe un réseau en sous-réseaux.</p>
                <p><strong>Avant de lancer la vidéo, relisez ce rappel :</strong> une adresse IPv4 fait 32 bits, notés en 4 octets décimaux (ex. <code>192.168.10.37</code>). Le masque (<code>255.255.255.0</code> = /24) détermine quels bits identifient le réseau et lesquels identifient la machine.</p>
            HTML,
        ]);
        $this->lesson($m3, 2, [
            'title' => 'Vidéo : le subnetting en pratique (cours complet CCNA)',
            'type' => Lesson::TYPE_VIDEO,
            'duration' => 29,
            'video_url' => 'https://www.youtube.com/watch?v=bQ8sdpGQu8c',
            'content_html' => <<<'HTML'
                <p>Jérémie (Jeremy's IT Lab) présente le subnetting de façon progressive, avec exemples et quiz intégrés. C'est la référence gratuite pour préparer la partie « IP addressing » du CCNA.</p>
                <p><strong>Plan de la vidéo :</strong> nombre d'hôtes par sous-réseau, notation CIDR /31 et /32, notation CIDR en pratique, scénario de découpage, quiz final.</p>
            HTML,
        ]);
        $this->lesson($m3, 3, [
            'title' => 'Subnetting pas à pas : méthode et exemples corrigés',
            'type' => Lesson::TYPE_TEXT,
            'duration' => 30,
            'content_html' => $this->subnettingLessonHtml(),
        ]);
        $this->lesson($m3, 4, [
            'title' => 'Fiche : table de subnetting à imprimer (PDF)',
            'type' => Lesson::TYPE_DOCUMENT,
            'duration' => 10,
            'document_title' => 'Fiche — Table de subnetting (CIDR, hôtes, plages).pdf',
            'document_url' => $this->pdfSubnettingTable(),
            'attachment_paths' => [$this->pdfSubnettingTable()],
            'has_attachment' => true,
            'attachment_title' => 'Table de subnetting',
            'content_html' => '<p>La table que tout technicien doit connaître par cœur : pour chaque préfixe CIDR de /24 à /30, le masque, le nombre de sous-réseaux d\'un /24 et le nombre d\'hôtes utilisables. Imprimez-la et gardez-la sur le bureau pendant les exercices.</p>',
        ]);
        $this->lesson($m3, 5, [
            'title' => 'Quiz de fin de module 3',
            'type' => Lesson::TYPE_QUIZ,
            'duration' => 10,
            'quiz' => $this->makeExam($course, 'Quiz module 3 — Adressage et subnetting', [
                'pass_percentage' => 70,
                'duration_minutes' => 10,
                'attempts_allowed' => 3,
                'description' => 'Masques, CIDR, plages d\'adresses : à vous de jouer.',
            ], $this->quizM3()),
        ]);

        // ------------------------------------------------------------------
        // Module 4 — DHCP et DNS
        // ------------------------------------------------------------------
        $m4 = Module::updateOrCreate(
            ['course_id' => $course->id, 'slug' => 'dhcp-dns-services'],
            [
                'title' => 'Les services réseau : DHCP et DNS',
                'description' => 'Comment une machine obtient son adresse et comment les noms de domaine sont traduits en IP.',
                'order' => 4,
                'status' => 'published',
                'is_published' => true,
            ]
        );
        $this->lesson($m4, 1, [
            'title' => 'Vidéo : DHCP expliqué pas à pas',
            'type' => Lesson::TYPE_VIDEO,
            'duration' => 8,
            'video_url' => 'https://www.youtube.com/watch?v=S43CFcpOZSI',
            'content_html' => <<<'HTML'
                <p>La séquence <strong>DORA</strong> en images : DHCPDiscover, DHCPOffer, DHCPRequest, DHCPAck. Vous verrez pourquoi une machine sans DHCP prend une adresse APIPA en <code>169.254.x.x</code>, le symptôme classique d'un problème réseau.</p>
            HTML,
        ]);
        $this->lesson($m4, 2, [
            'title' => 'DNS : la résolution de noms de A à Z',
            'type' => Lesson::TYPE_TEXT,
            'duration' => 20,
            'content_html' => $this->dnsLessonHtml(),
        ]);

        // ------------------------------------------------------------------
        // Examen final
        // ------------------------------------------------------------------
        $final = $this->makeExam($course, 'Examen final — Les Fondamentaux des Réseaux Informatiques', [
            'is_final_exam' => true,
            'pass_percentage' => 60,
            'duration_minutes' => 30,
            'attempts_allowed' => 2,
            'description' => '10 questions couvrant les 4 modules. 60 % minimum pour valider la formation.',
        ], $this->finalQuestions());
        $course->forceFill([
            'has_final_exam' => true,
            'final_exam_id' => $final->id,
        ])->save();

        // ------------------------------------------------------------------
        // Inscription
        // ------------------------------------------------------------------
        $user = User::where('email', 'genesiskikimba@gmail.com')->first();
        if ($user) {
            Enrollment::updateOrCreate(
                ['user_id' => $user->id, 'course_id' => $course->id],
                ['status' => 'active', 'enrolled_at' => now(), 'progress_percent' => 0, 'revoked_at' => null, 'revoked_reason' => null]
            );
            $this->command->info("Inscription active : {$user->email} → cours #{$course->id} « {$course->title} ».");
        } else {
            $this->command->warn('Compte genesiskikimba@gmail.com introuvable — aucune inscription créée.');
        }

        $this->command->info("Cours réel seedé : #{$course->id} « {$course->title} » (modules, PDF, quiz, devoir, examen final).");
    }

    // ----------------------------------------------------------------------

    private function lesson(Module $module, int $order, array $data): Lesson
    {
        $payload = [
            'title' => $data['title'],
            'type' => $data['type'],
            'lesson_type' => $data['type'],
            'duration_minutes' => $data['duration'],
            'order' => $order,
            'is_published' => true,
            'status' => 'published',
            'is_free_preview' => $data['is_free_preview'] ?? false,
            'content' => $data['content_html'] ?? null,
            'content_html' => $data['content_html'] ?? null,
            'video_url' => $data['video_url'] ?? null,
            'video_source' => isset($data['video_url']) ? 'youtube' : null,
            'document_title' => $data['document_title'] ?? null,
            'document_url' => $data['document_url'] ?? null,
            'has_attachment' => $data['has_attachment'] ?? false,
            'attachment_title' => $data['attachment_title'] ?? null,
            'attachment_paths' => $data['attachment_paths'] ?? null,
            'quiz_id' => $data['quiz']->id ?? null,
        ];

        return Lesson::updateOrCreate(
            ['module_id' => $module->id, 'slug' => Str::slug($data['title'])],
            $payload
        );
    }

    private function makeExam(Course $course, string $title, array $attrs, array $questions): Exam
    {
        $exam = Exam::updateOrCreate(
            ['course_id' => $course->id, 'title' => $title],
            array_merge([
                'uuid' => (string) Str::uuid(),
                'type' => 'quiz',
                'status' => 'Active',
                'is_published' => true,
                'created_by' => $course->instructor_id,
                'settings' => [],
            ], $attrs)
        );

        $order = 0;
        foreach ($questions as $q) {
            $question = Question::updateOrCreate(
                ['exam_id' => $exam->id, 'question_text' => $q['question_text']],
                [
                    'uuid' => (string) Str::uuid(),
                    'type' => $q['type'],
                    'correct_answer' => $q['correct_answer'] ?? null,
                    'explanation' => $q['explanation'] ?? null,
                    'points' => 1,
                    'difficulty' => $q['difficulty'] ?? 'easy',
                    'status' => 'published',
                    'order' => $order++,
                ]
            );

            $optOrder = 0;
            foreach ($q['options'] ?? [] as $i => $opt) {
                QuestionOption::updateOrCreate(
                    ['question_id' => $question->id, 'option_text' => $opt],
                    ['order' => $optOrder++, 'is_correct' => $q['type'] === 'multiple_choice' ? ($i === $q['correct_option']) : false]
                );
            }
        }

        return $exam;
    }

    // ----------------------------------------------------------------------
    // Contenus pédagogiques
    // ----------------------------------------------------------------------

    private function osiLessonHtml(): string
    {
        return <<<'HTML'
            <h2>Les 7 couches du modèle OSI</h2>
            <p>Le modèle OSI (Open Systems Interconnection) découpe toute communication réseau en <strong>7 couches indépendantes</strong>. Chaque couche fournit un service à celle du dessus et s'appuie sur celle du dessous. Le modèle TCP/IP utilisé en pratique regroupe ces couches en 4 niveaux.</p>
            <h3>1. La couche physique</h3>
            <p>Elle transmet des <strong>bits</strong> (0 et 1) sur le support : câble cuivre (Ethernet), fibre optique, ondes radio (Wi-Fi). On y définit les voltages, les connecteurs RJ45, les débits (100 Mbit/s, 1 Gbit/s…).</p>
            <h3>2. La couche liaison de données (Data Link)</h3>
            <p>Elle forme des <strong>trames</strong> et utilise les <strong>adresses MAC</strong> (ex. <code>00:1A:2B:3C:4D:5E</code>), gravées dans les cartes réseau. C'est ici que vit le <strong>switch</strong> : il lit l'adresse MAC de destination et n'envoie la trame que vers le bon port. Les protocoles ARP et VLAN se situent à ce niveau.</p>
            <h3>3. La couche réseau</h3>
            <p>Elle forme des <strong>paquets</strong> identifiés par des <strong>adresses IP</strong>. C'est la couche du <strong>routage</strong> : le routeur lit l'IP de destination et choisit le prochain saut. Protocoles : IP, ICMP (ping), OSPF, BGP.</p>
            <h3>4. La couche transport</h3>
            <p>Elle forme des <strong>segments</strong> et identifie les applications par des <strong>numéros de port</strong> : 80/443 pour le web, 53 pour le DNS, 22 pour SSH.</p>
            <table>
                <thead><tr><th></th><th>TCP</th><th>UDP</th></tr></thead>
                <tbody>
                    <tr><td><strong>Fiabilité</strong></td><td>Accusés de réception, retransmission</td><td>Aucune garantie</td></tr>
                    <tr><td><strong>Vitesse</strong></td><td>Plus lente (contrôle)</td><td>Très rapide</td></tr>
                    <tr><td><strong>Usages</strong></td><td>Web, e-mail, transfert de fichiers</td><td>DNS, VoIP, streaming, jeux</td></tr>
                </tbody>
            </table>
            <h3>5 à 7 : session, présentation, application</h3>
            <p>La <strong>session</strong> établit et maintient le dialogue ; la <strong>présentation</strong> encode et chiffre (TLS/SSL, JPEG) ; l'<strong>application</strong> est ce que vous connaissez : HTTP, DNS, SMTP, FTP. En pratique TCP/IP fusionne ces trois couches en une seule couche « application ».</p>
            <h3>L'encapsulation : le mot-clé à retenir</h3>
            <p>En descendant la pile, chaque couche <em>ajoute</em> une en-tête : données → segment TCP → paquet IP → trame Ethernet → bits. Le récepteur fait l'inverse (désencapsulation). Retenez la suite <strong>« Données, Segment, Paquet, Trame, Bits »</strong> : elle tombe souvent aux examens.</p>
            <blockquote>Erreur classique en examen : croire que le routeur lit les adresses MAC au-delà du réseau local. Un routeur réécrit les en-têtes de trame à chaque saut — l'IP reste, la MAC change.</blockquote>
        HTML;
    }

    private function subnettingLessonHtml(): string
    {
        return <<<'HTML'
            <h2>Le subnetting pas à pas</h2>
            <p>Objectif : savoir, pour une adresse et un masque donnés, trouver <strong>l'adresse réseau, la première et la dernière adresse utilisable, et l'adresse de broadcast</strong>. C'est le calcul le plus demandé en entretien et en certification.</p>
            <h3>Étape 1 — Traduire le masque</h3>
            <p>Le masque <code>/26</code> = 26 bits à 1 = <code>255.255.255.192</code>. Le dernier octet vaut 192 = <code>11000000</code> en binaire : le « pas » (bloc) entre sous-réseaux vaut <strong>256 − 192 = 64</strong>.</p>
            <h3>Étape 2 — Lister les frontières</h3>
            <p>Pour un réseau <code>192.168.1.0/26</code>, les sous-réseaux commencent à <code>.0</code>, <code>.64</code>, <code>.128</code>, <code>.192</code>.</p>
            <h3>Étape 3 — Calculer la plage</h3>
            <p>Prenons l'hôte <code>192.168.1.100</code> dans <code>192.168.1.0/26</code> :</p>
            <table>
                <thead><tr><th>Élément</th><th>Valeur</th></tr></thead>
                <tbody>
                    <tr><td>Adresse réseau</td><td><code>192.168.1.64</code> (100 est entre 64 et 128)</td></tr>
                    <tr><td>Première adresse utilisable</td><td><code>192.168.1.65</code></td></tr>
                    <tr><td>Dernière adresse utilisable</td><td><code>192.168.1.126</code></td></tr>
                    <tr><td>Broadcast</td><td><code>192.168.1.127</code></td></tr>
                    <tr><td>Hôtes utilisables</td><td>2⁶ − 2 = <strong>62</strong></td></tr>
                </tbody>
            </table>
            <h3>Étape 4 — Choisir un masque selon un besoin d'hôtes</h3>
            <ul>
                <li>Besoin de 50 hôtes → il faut 6 bits d'hôtes (64 − 2 = 62) → masque <strong>/26</strong> ;</li>
                <li>Besoin de 100 hôtes → 7 bits (128 − 2 = 126) → <strong>/25</strong> ;</li>
                <li>Besoin de 300 hôtes → 9 bits (512 − 2 = 510) → <strong>/23</strong>.</li>
            </ul>
            <h3>Exercice corrigé</h3>
            <p>Un département compte 3 services : 200, 100 et 50 postes, à découper depuis <code>10.0.0.0/24</code>.</p>
            <ol>
                <li>Service A (200 postes) → <code>10.0.0.0/23</code>… un /23 ne tient pas dans un /24 : on prend donc une base plus grande. Avec <code>10.0.0.0/24</code> on alloue d'abord le plus gros bloc possible : <code>10.0.0.0/25</code> (126 hôtes) — insuffisant pour 200. La bonne pratique : partir d'une base <code>10.0.0.0/23</code> (510 hôtes) pour le service A ;</li>
                <li>Service B (100) → <code>10.0.2.0/25</code> (126 hôtes) ;</li>
                <li>Service C (50) → <code>10.0.2.128/26</code> (62 hôtes).</li>
            </ol>
            <blockquote>Règle d'or : toujours allouer du <strong>plus grand au plus petit</strong>, en gardant des blocs alignés sur leur propre taille (les blocs commencent sur un multiple de leur pas).</blockquote>
        HTML;
    }

    private function dnsLessonHtml(): string
    {
        return <<<'HTML'
            <h2>DNS : l'annuaire d'Internet</h2>
            <p>Sans le DNS (Domain Name System), il faudrait retenir des adresses IP pour chaque site. Le DNS traduit un <strong>nom de domaine</strong> (<code>subnetcongo.net</code>) en <strong>adresse IP</strong>.</p>
            <h3>Le trajet d'une résolution</h3>
            <ol>
                <li><strong>Le cache local</strong> : votre navigateur et votre système vérifient s'ils connaissent déjà la réponse ;</li>
                <li><strong>Le résolveur</strong> (généralement votre FAI ou 8.8.8.8/1.1.1.1) interroge la hiérarchie pour vous ;</li>
                <li>Les <strong>serveurs racine</strong> pointent vers le serveur de la zone <code>.net</code> ;</li>
                <li>Le serveur <code>.net</code> pointe vers les serveurs <strong>autoritaires</strong> de <code>subnetcongo.net</code> ;</li>
                <li>Le serveur autoritaire renvoie l'enregistrement (ex. un <code>A</code> vers <code>41.243.x.x</code>).</li>
            </ol>
            <h3>Les enregistrements à connaître</h3>
            <table>
                <thead><tr><th>Type</th><th>Rôle</th><th>Exemple</th></tr></thead>
                <tbody>
                    <tr><td><code>A</code></td><td>Nom → IPv4</td><td><code>subnetcongo.net → 41.243.10.5</code></td></tr>
                    <tr><td><code>AAAA</code></td><td>Nom → IPv6</td><td><code>… → 2001:db8::1</code></td></tr>
                    <tr><td><code>CNAME</code></td><td>Alias vers un autre nom</td><td><code>www → subnetcongo.net</code></td></tr>
                    <tr><td><code>MX</code></td><td>Serveurs de messagerie</td><td><code>10 mail.subnetcongo.net</code></td></tr>
                    <tr><td><code>TXT</code></td><td>Informations (SPF, vérifications)</td><td><code>v=spf1 include:_spf…</code></td></tr>
                </tbody>
            </table>
            <h3>Commandes utiles</h3>
            <ul>
                <li><code>nslookup subnetcongo.net</code> (Windows) — interroge un serveur DNS ;</li>
                <li><code>ipconfig /flushdns</code> — vide le cache DNS local ;</li>
                <li><code>ping</code> et <code>tracert</code> — testent la joignabilité et le chemin.</li>
            </ul>
            <blockquote>Bon à savoir : une erreur <em>« DNS server not responding »</em> avec une IP en 169.254.x.x signifie souvent deux problèmes en un : le DHCP a échoué, donc le DNS n'a jamais été configuré.</blockquote>
        HTML;
    }

    // ----------------------------------------------------------------------
    // Questions des quiz
    // ----------------------------------------------------------------------

    private function quizM1(): array
    {
        return [
            [
                'type' => 'multiple_choice',
                'question_text' => 'À quelle couche OSI appartient le switch (commutateur) ?',
                'correct_option' => 1,
                'options' => ['Couche 1 — Physique', 'Couche 2 — Liaison de données', 'Couche 3 — Réseau', 'Couche 4 — Transport'],
                'correct_answer' => 'Couche 2 — Liaison de données',
                'explanation' => 'Le switch commute des trames en lisant les adresses MAC (couche 2).',
            ],
            [
                'type' => 'multiple_choice',
                'question_text' => 'Quel équipement relie deux réseaux différents et route les paquets entre eux ?',
                'correct_option' => 2,
                'options' => ['Le hub', 'Le switch', 'Le routeur', 'Le câble Ethernet'],
                'correct_answer' => 'Le routeur',
                'explanation' => 'Le routeur opère en couche 3 et choisit le chemin grâce aux adresses IP.',
            ],
            [
                'type' => 'true_false',
                'question_text' => 'Une trame Ethernet contient l\'adresse MAC source et destination.',
                'correct_answer' => 'true',
                'explanation' => 'Les en-têtes de trame (couche 2) portent les MAC source et destination.',
            ],
        ];
    }

    private function quizM3(): array
    {
        return [
            [
                'type' => 'multiple_choice',
                'question_text' => 'Quel masque correspond au préfixe /26 ?',
                'correct_option' => 1,
                'options' => ['255.255.255.128', '255.255.255.192', '255.255.255.224', '255.255.255.240'],
                'correct_answer' => '255.255.255.192',
                'explanation' => '/26 = 26 bits à 1 → 128+64 = 192 sur le dernier octet.',
            ],
            [
                'type' => 'multiple_choice',
                'question_text' => 'Combien d\'hôtes utilisables offre un sous-réseau /28 ?',
                'correct_option' => 2,
                'options' => ['14', '16', '16-2 = 14', '30'],
                'correct_answer' => '14',
                'explanation' => 'Un /28 laisse 4 bits d\'hôtes : 2⁴ − 2 = 14 (réseau et broadcast exclus).',
            ],
            [
                'type' => 'multiple_choice',
                'question_text' => 'Pour 100 hôtes, quel est le masque minimal correct ?',
                'correct_option' => 0,
                'options' => ['/25 (126 hôtes)', '/26 (62 hôtes)', '/27 (30 hôtes)', '/24 est obligatoire'],
                'correct_answer' => '/25 (126 hôtes)',
                'explanation' => 'Il faut 7 bits d\'hôtes : 2⁷ − 2 = 126 ≥ 100 → /25.',
            ],
            [
                'type' => 'true_false',
                'question_text' => 'L\'adresse de broadcast du réseau 192.168.1.64/26 est 192.168.1.127.',
                'correct_answer' => 'true',
                'explanation' => 'Le bloc /26 suivant commence à 128, donc le broadcast est 127.',
            ],
            [
                'type' => 'short_answer',
                'question_text' => 'Quel est le « pas » (taille de bloc) entre deux sous-réseaux avec un masque 255.255.255.192 ? (répondez par un nombre)',
                'correct_answer' => '64',
                'explanation' => '256 − 192 = 64.',
            ],
        ];
    }

    private function finalQuestions(): array
    {
        return [
            [
                'type' => 'multiple_choice',
                'question_text' => 'Quelle est la bonne séquence d\'encapsulation descendante ?',
                'correct_option' => 1,
                'options' => ['Bits → trame → paquet → segment', 'Segment → paquet → trame → bits', 'Paquet → segment → trame → bits', 'Trame → segment → paquet → bits'],
                'correct_answer' => 'Segment → paquet → trame → bits',
                'explanation' => 'Données → segment (couche 4) → paquet (couche 3) → trame (couche 2) → bits (couche 1).',
            ],
            [
                'type' => 'multiple_choice',
                'question_text' => 'Quel protocole traduit une adresse IP en adresse MAC sur le réseau local ?',
                'correct_option' => 0,
                'options' => ['ARP', 'DNS', 'DHCP', 'ICMP'],
                'correct_answer' => 'ARP',
                'explanation' => 'ARP (Address Resolution Protocol) résout IP → MAC (couche 2).',
            ],
            [
                'type' => 'multiple_choice',
                'question_text' => 'Quel port utilise HTTPS par défaut ?',
                'correct_option' => 2,
                'options' => ['21', '80', '443', '8080'],
                'correct_answer' => '443',
                'explanation' => 'HTTP = 80, HTTPS = 443.',
            ],
            [
                'type' => 'multiple_choice',
                'question_text' => 'Quelle adresse est une adresse privée (RFC 1918) ?',
                'correct_option' => 3,
                'options' => ['8.8.8.8', '169.254.10.1', '41.243.10.5', '10.20.30.40'],
                'correct_answer' => '10.20.30.40',
                'explanation' => 'Les plages privées : 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16.',
            ],
            [
                'type' => 'true_false',
                'question_text' => 'TCP garantit la livraison et l\'ordre des segments.',
                'correct_answer' => 'true',
                'explanation' => 'TCP est fiable (accusés de réception) ; UDP ne garantit rien.',
            ],
            [
                'type' => 'true_false',
                'question_text' => 'Une adresse 169.254.x.x obtenue automatiquement indique généralement un échec du DHCP.',
                'correct_answer' => 'true',
                'explanation' => 'APIPA : la machine s\'auto-adresse faute de serveur DHCP joignable.',
            ],
            [
                'type' => 'multiple_choice',
                'question_text' => 'Dans la séquence DHCP DORA, que signifie le « R » de DHCPRequest ?',
                'correct_option' => 1,
                'options' => ['Le serveur répond avec une adresse', 'Le client demande officiellement l\'adresse proposée', 'Le client renouvelle son bail', 'Le serveur rejette la demande'],
                'correct_answer' => 'Le client demande officiellement l\'adresse proposée',
                'explanation' => 'Discover → Offer → Request → Ack.',
            ],
            [
                'type' => 'multiple_choice',
                'question_text' => 'Quel enregistrement DNS pointe un alias « www » vers le domaine principal ?',
                'correct_option' => 2,
                'options' => ['MX', 'A', 'CNAME', 'TXT'],
                'correct_answer' => 'CNAME',
                'explanation' => 'CNAME = alias de nom vers un autre nom.',
            ],
            [
                'type' => 'short_answer',
                'question_text' => 'Combien d\'hôtes utilisables dans un /25 ? (nombre)',
                'correct_answer' => '126',
                'explanation' => '2⁷ − 2 = 126.',
            ],
            [
                'type' => 'short_answer',
                'question_text' => 'Quelle commande Windows vide le cache DNS local ? (deux mots, format : commande /mot)',
                'correct_answer' => 'ipconfig /flushdns',
                'explanation' => 'ipconfig /flushdns purge le cache du résolveur Windows.',
            ],
        ];
    }

    // ----------------------------------------------------------------------
    // Génération des PDF (2 pages, mise en page soignée)
    // ----------------------------------------------------------------------

    private function pdfRevisionOsi(): string
    {
        return $this->buildPdf([
            'title' => 'Fiche de revision - Modele OSI et TCP/IP',
            'subtitle' => 'SubNetCongo e-Learning - Formation Reseaux',
            'sections' => [
                ['bold', 'LES 7 COUCHES (MODELE OSI)'],
                ['line', '7  Application    HTTP, DNS, SMTP, FTP         -> donnees'],
                ['line', '6  Presentation    TLS/SSL, encodage, JPEG       -> donnees'],
                ['line', '5  Session         dialogues, sessions            -> donnees'],
                ['line', '4  Transport       TCP / UDP, ports               -> segments'],
                ['line', '3  Reseau          IP, ICMP, routage              -> paquets'],
                ['line', '2  Liaison         Ethernet, MAC, ARP, switch     -> trames'],
                ['line', '1  Physique        cables, fibre, radio, bits     -> bits'],
                ['space', ''],
                ['bold', 'MODELE TCP/IP (4 COUCHES)'],
                ['line', 'Application   = OSI 5 + 6 + 7'],
                ['line', 'Transport     = OSI 4 (TCP, UDP)'],
                ['line', 'Internet      = OSI 3 (IP, ICMP)'],
                ['line', 'Acces reseau  = OSI 1 + 2 (Ethernet, MAC)'],
                ['space', ''],
                ['bold', 'PORTS A CONNAITRE'],
                ['line', 'HTTP 80    HTTPS 443    DNS 53    SSH 22'],
                ['line', 'SMTP 25    FTP 21       TELNET 23 DHCP 67/68'],
                ['space', ''],
                ['bold', 'ENCAPSULATION (A RETENIR PAR COEUR)'],
                ['line', 'Donnees -> Segment -> Paquet -> Trame -> Bits'],
                ['space', ''],
                ['bold', 'QUI FAIT QUOI ?'],
                ['line', 'Switch : couche 2, lit les adresses MAC.'],
                ['line', 'Routeur : couche 3, lit les adresses IP, reecrit les trames.'],
                ['line', 'Pare-feu : filtre les flux (ports, adresses, applications).'],
                ['line', 'Access point : pont Wi-Fi vers le reseau filaire.'],
            ],
        ], 'demo/cours-reseaux/fiche-revision-osi.pdf');
    }

    private function pdfSubnettingTable(): string
    {
        return $this->buildPdf([
            'title' => 'Table de subnetting (CIDR)',
            'subtitle' => 'A imprimer et garder sur le bureau',
            'sections' => [
                ['bold', 'PREFIXE      MASQUE            PAS   HOTES/SOUS-RESEAU (d\'un /24)'],
                ['line', '/25          255.255.255.128   128   126'],
                ['line', '/26          255.255.255.192   64    62'],
                ['line', '/27          255.255.255.224   32    30'],
                ['line', '/28          255.255.255.240   16    14'],
                ['line', '/29          255.255.255.248   8     6'],
                ['line', '/30          255.255.255.252   4     2 (liens point a point)'],
                ['space', ''],
                ['bold', 'BESOINS D\'HOTES -> MASQUE MINIMAL'],
                ['line', '2 -> /30     6 -> /29      14 -> /28'],
                ['line', '30 -> /27    62 -> /26     126 -> /25'],
                ['line', '254 -> /24   510 -> /23    1022 -> /22'],
                ['space', ''],
                ['bold', 'EXEMPLE COMPLET : 192.168.1.100 DANS 192.168.1.0/26'],
                ['line', 'Reseau                 : 192.168.1.64'],
                ['line', 'Premiere adresse       : 192.168.1.65'],
                ['line', 'Derniere adresse       : 192.168.1.126'],
                ['line', 'Broadcast              : 192.168.1.127'],
                ['line', 'Hotes utilisables      : 62'],
                ['space', ''],
                ['bold', 'METHODE EN 4 COUPS'],
                ['line', '1. Masque en binaire -> nombre de bits reseau.'],
                ['line', '2. Pas = 256 - dernier octet du masque.'],
                ['line', '3. Frontieres : multiples du pas depuis 0.'],
                ['line', '4. Plage : reseau+1 a broadcast-1 ; broadcast = frontiere - 1.'],
                ['space', ''],
                ['bold', 'RAPPELS CLASSES (HISTORIQUE)'],
                ['line', 'A : 0.0.0.0/8 - 10.x.x.x prive.   B : 128-191, 172.16/12 prive.'],
                ['line', 'C : 192-223, 192.168/16 prive.    D : multicast. E : experimental.'],
            ],
        ], 'demo/cours-reseaux/table-subnetting.pdf');
    }

    /**
     * Génère un PDF A4 de 2 pages à partir de sections typées
     * [bold|line|space, texte]. Retourne le chemin sur le disque media.
     */
    private function buildPdf(array $doc, string $path): string
    {
        if (Storage::disk('media')->exists($path)) {
            return $path;
        }

        $lines = [];
        $esc = fn (string $s) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);

        // Page 1 : titre + premières sections ; page 2 : le reste
        $title = $doc['title'];
        $subtitle = $doc['subtitle'];
        $sections = $doc['sections'];
        $half = (int) ceil(count($sections) / 2);

        foreach ([array_slice($sections, 0, $half), array_slice($sections, $half)] as $pageIdx => $pageSections) {
            $pageLines = [];
            if ($pageIdx === 0) {
                $pageLines[] = ['title', $title];
                $pageLines[] = ['sub', $subtitle];
            }
            foreach ($pageSections as $s) {
                $pageLines[] = $s;
            }
            // pied de page
            $pageLines[] = ['footer', ($pageIdx === 0 ? 'Page 1/2' : 'Page 2/2') . '  -  SubNetCongo e-Learning - usage pedagogique'];

            $y = 780;
            $stream = '';
            foreach ($pageLines as [$kind, $text]) {
                [$font, $size, $leading, $gap, $gray] = match ($kind) {
                    'title' => ['/F2', 18.0, 22, 6, '0.1 g'],
                    'sub' => ['/F1', 10.0, 14, 14, '0.35 g'],
                    'bold' => ['/F2', 11.0, 15, 4, '0.05 g'],
                    'line' => ['/F1', 10.0, 14, 1.5, '0.15 g'],
                    'space' => ['/', 0, 0, 8, null],
                    'footer' => ['/F1', 8.0, 12, 0, '0.5 g'],
                };
                $y -= ($leading + $gap);
                if ($gray !== null) {
                    $stream .= "BT {$gray} {$font} {$size} Tf 50 {$y} Td ({$esc($text)}) Tj ET\n";
                } else {
                    $y -= 8;
                }
            }

            $lines[] = trim($stream);
        }

        // Assemblage PDF 2 pages
        $pageCount = count($lines);
        $kids = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $kids[] = (5 + $i * 2).' 0 R';
        }
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.$pageCount.' >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
        foreach ($lines as $content) {
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.(count($objects) + 2).' 0 R >>';
            $objects[] = "<< /Length ".strlen($content)." >>\nstream\n{$content}\nendstream";
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[$i] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$obj}\nendobj\n";
        }
        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

        Storage::disk('media')->put($path, $pdf);

        return $path;
    }
}
