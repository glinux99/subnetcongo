<?php

namespace Database\Seeders;

use App\Models\Assignment;
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
 * Seeder de démonstration du player de leçon (LOCAL UNIQUEMENT).
 *
 * Ajoute au cours n°1 (Cisco CCNA 200-301) des modules de démonstration
 * couvrant tous les types de leçons :
 *   - vidéo (YouTube + fichier local)
 *   - texte riche (HTML)
 *   - document PDF (généré) + pièces jointes
 *   - quiz lié à un examen (3 questions : QCM, vrai/faux, réponse courte)
 *   - devoir (Assignment à soumettre depuis le player)
 * et un examen final débloqué quand toutes les leçons sont terminées.
 *
 * Inscrit aussi le compte genesiskikimba@gmail.com au cours s'il existe.
 * Idempotent : ré-exécutable sans duplication (updateOrCreate / firstOrCreate).
 */
class LessonDemoSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::find(1) ?? Course::orderBy('id')->first();
        if (! $course) {
            $this->command->warn('Aucun cours trouvé — seeder ignoré.');

            return;
        }

        $nextModuleOrder = (int) ($course->modules()->max('order') ?? 0);

        // ------------------------------------------------------------------
        // Module 1 — VIDÉOS (YouTube + fichier local)
        // ------------------------------------------------------------------
        $videoModule = Module::updateOrCreate(
            ['course_id' => $course->id, 'slug' => 'demo-videos'],
            [
                'title' => 'Démo — Vidéos',
                'description' => 'Module de démonstration : leçons vidéo (YouTube et fichier).',
                'order' => $nextModuleOrder + 1,
                'status' => 'published',
                'is_published' => true,
            ]
        );
        $this->videoLesson($videoModule, 1, 'Bienvenue dans la formation (vidéo YouTube)', 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 10);
        $this->videoLesson($videoModule, 2, 'Segment réseau en action (vidéo locale)', 'https://test-videos.co.uk/vids/bigbuckbunny/mp4/h264/720/Big_Buck_Bunny_720_10s_1MB.mp4', 5);

        // ------------------------------------------------------------------
        // Module 2 — TEXTE RICHE
        // ------------------------------------------------------------------
        $textModule = Module::updateOrCreate(
            ['course_id' => $course->id, 'slug' => 'demo-texte'],
            [
                'title' => 'Démo — Texte',
                'description' => 'Module de démonstration : leçon au format texte riche.',
                'order' => $nextModuleOrder + 2,
                'status' => 'published',
                'is_published' => true,
            ]
        );
        $this->textLesson($textModule);

        // ------------------------------------------------------------------
        // Module 3 — DOCUMENT PDF + PIÈCES JOINTES
        // ------------------------------------------------------------------
        $docModule = Module::updateOrCreate(
            ['course_id' => $course->id, 'slug' => 'demo-documents'],
            [
                'title' => 'Démo — Documents',
                'description' => 'Module de démonstration : PDF consultable et pièces jointes.',
                'order' => $nextModuleOrder + 3,
                'status' => 'published',
                'is_published' => true,
            ]
        );
        $this->documentLesson($docModule);

        // ------------------------------------------------------------------
        // Module 4 — QUIZ + DEVOIR + EXAMEN FINAL
        // ------------------------------------------------------------------
        $evalModule = Module::updateOrCreate(
            ['course_id' => $course->id, 'slug' => 'demo-evaluations'],
            [
                'title' => 'Démo — Quiz & Devoir',
                'description' => 'Module de démonstration : quiz d\'évaluation et devoir à rendre.',
                'order' => $nextModuleOrder + 4,
                'status' => 'published',
                'is_published' => true,
            ]
        );

        // Quiz lié à un examen
        $quizExam = $this->makeExam($course, 'Quiz démo — Réseaux TCP/IP', [
            'is_module_quiz' => true,
            'pass_percentage' => 70,
            'duration_minutes' => 10,
            'attempts_allowed' => 3,
            'description' => 'Quiz de démonstration : QCM, vrai/faux et réponse courte.',
        ], $this->quizQuestions());
        $this->quizLesson($evalModule, $quizExam);

        // Devoir
        $assignment = Assignment::firstOrCreate(
            ['course_id' => $course->id, 'title' => 'Devoir démo — Concevoir un plan d\'adressage IP'],
            [
                'description' => 'Concevez un plan d\'adressage IPv4 pour un réseau de 3 sous-réseaux (50, 100 et 200 hôtes). '
                    .'Justifiez vos choix de masque, listez les plages d\'adresses et rendez le plan au format texte ou PDF.',
                'max_points' => 20,
                'due_at' => now()->addDays(7),
                'type' => 'Devoir',
            ]
        );
        $this->assignmentLesson($evalModule, $assignment);

        // Examen final du cours
        $finalExam = $this->makeExam($course, 'Examen Final : '.$course->title, [
            'is_final_exam' => true,
            'pass_percentage' => 60,
            'duration_minutes' => 30,
            'attempts_allowed' => 2,
            'description' => 'Examen final de démonstration couvrant l\'ensemble du cours.',
        ], $this->finalExamQuestions());
        $course->forceFill([
            'has_final_exam' => true,
            'final_exam_id' => $finalExam->id,
        ])->save();

        // ------------------------------------------------------------------
        // Inscription du compte genesiskikimba@gmail.com
        // ------------------------------------------------------------------
        $user = User::where('email', 'genesiskikimba@gmail.com')->first();
        if ($user) {
            Enrollment::updateOrCreate(
                ['user_id' => $user->id, 'course_id' => $course->id],
                ['status' => 'active', 'enrolled_at' => now(), 'progress_percent' => 0, 'revoked_at' => null, 'revoked_reason' => null]
            );
            $this->command->info("Inscription active : {$user->email} → cours « {$course->title} » (#{$course->id}).");
        } else {
            $this->command->warn('Compte genesiskikimba@gmail.com introuvable — aucune inscription créée.');
        }

        $this->command->info('LessonDemoSeeder terminé (modules démo + quiz + devoir + examen final).');
    }

    // ----------------------------------------------------------------------
    // Helpers leçons
    // ----------------------------------------------------------------------

    private function videoLesson(Module $module, int $order, string $title, string $url, int $duration): Lesson
    {
        return Lesson::updateOrCreate(
            ['module_id' => $module->id, 'slug' => Str::slug($title)],
            [
                'title' => $title,
                'type' => Lesson::TYPE_VIDEO,
                'lesson_type' => 'video',
                'video_url' => $url,
                'video_source' => str_contains($url, 'youtu') ? 'youtube' : 'custom',
                'duration_minutes' => $duration,
                'order' => $order,
                'is_published' => true,
                'status' => 'published',
                'is_free_preview' => $order === 1,
            ]
        );
    }

    private function textLesson(Module $module): Lesson
    {
        $html = <<<'HTML'
            <h2>Les couches du modèle OSI</h2>
            <p>Le modèle OSI structure les communications réseau en <strong>7 couches</strong>. Chaque couche a un rôle précis&nbsp;:</p>
            <ol>
                <li><strong>Physique</strong> — câbles, signaux, bits.</li>
                <li><strong>Liaison de données</strong> — trames, adresses MAC, commutation.</li>
                <li><strong>Réseau</strong> — paquets, adressage IP, routage.</li>
                <li><strong>Transport</strong> — segments, TCP/UDP, ports.</li>
                <li><strong>Session</strong> — établissement et gestion des sessions.</li>
                <li><strong>Présentation</strong> — encodage, chiffrement, compression.</li>
                <li><strong>Application</strong> — HTTP, DNS, SMTP…</li>
            </ol>
            <blockquote>Astuce CCNA&nbsp;: mémorisez «&nbsp;PLe-Ré-Tra-Se-Pré-Ap&nbsp;» ou la phrase classique «&nbsp;<em>Please Do Not Throw Sausage Pizza Away</em>&nbsp;».</blockquote>
            <h3>TCP ou UDP&nbsp;?</h3>
            <table>
                <thead><tr><th>Critère</th><th>TCP</th><th>UDP</th></tr></thead>
                <tbody>
                    <tr><td>Fiabilité</td><td>Accusés de réception, reprise d'erreur</td><td>Aucune garantie</td></tr>
                    <tr><td>Vitesse</td><td>Plus lent (surcharges)</td><td>Très rapide</td></tr>
                    <tr><td>Usages</td><td>Web (HTTP), e-mail (SMTP), SSH</td><td>DNS, VoIP, streaming, jeux</td></tr>
                </tbody>
            </table>
            <p>Dans la <strong>couche réseau</strong>, le routage s'appuie sur les tables de routage&nbsp;: routes statiques, protocoles IGP (OSPF, RIP, EIGRP) et BGP entre systèmes autonomes.</p>
        HTML;

        return Lesson::updateOrCreate(
            ['module_id' => $module->id, 'slug' => 'demo-texte-modele-osi-tcp-udp'],
            [
                'title' => 'Le modèle OSI : TCP/IP en pratique',
                'type' => Lesson::TYPE_TEXT,
                'lesson_type' => 'text',
                'content' => $html,
                'content_html' => $html,
                'content_text' => 'Le modèle OSI et ses 7 couches ; comparaison TCP/UDP ; bases du routage.',
                'duration_minutes' => 15,
                'order' => 1,
                'is_published' => true,
                'status' => 'published',
            ]
        );
    }

    private function documentLesson(Module $module): Lesson
    {
        // Génération d'un mini PDF valide sur le disque media (public/media)
        $pdfPath = 'demo/guide-reseau-demo.pdf';
        if (! Storage::disk('media')->exists($pdfPath)) {
            Storage::disk('media')->put($pdfPath, $this->buildDemoPdf());
        }

        $txtPath = 'demo/notes-tp-demo.txt';
        if (! Storage::disk('media')->exists($txtPath)) {
            Storage::disk('media')->put(
                $txtPath,
                "NOTES DE TP — CONFIGURATION ROUTEUR\n".
                "===================================\n\n".
                "1. Accès à la console : câble console + émulateur terminal (9600 bauds).\n".
                "2. Mode privilégié : enable\n".
                "3. Configuration globale : configure terminal\n".
                "4. Attribution d'une IP : ip address 192.168.1.1 255.255.255.0\n".
                "5. Activation de l'interface : no shutdown\n\n".
                "Commandes utiles :\n".
                "  show ip interface brief\n".
                "  show running-config\n".
                "  copy running-config startup-config\n"
            );
        }

        return Lesson::updateOrCreate(
            ['module_id' => $module->id, 'slug' => 'demo-document-guide-pdf'],
            [
                'title' => 'Guide de configuration réseau (PDF)',
                'type' => Lesson::TYPE_DOCUMENT,
                'lesson_type' => 'document',
                'document_title' => 'Guide de configuration réseau — démo.pdf',
                'document_url' => $pdfPath,
                'has_attachment' => true,
                'attachment_title' => 'Notes de TP',
                'attachment_paths' => [$txtPath],
                'duration_minutes' => 20,
                'order' => 1,
                'is_published' => true,
                'status' => 'published',
            ]
        );
    }

    private function quizLesson(Module $module, Exam $exam): Lesson
    {
        return Lesson::updateOrCreate(
            ['module_id' => $module->id, 'slug' => 'demo-quiz-tcp-ip'],
            [
                'title' => 'Quiz de validation — Réseaux TCP/IP',
                'type' => Lesson::TYPE_QUIZ,
                'lesson_type' => 'quiz',
                'quiz_id' => $exam->id,
                'duration_minutes' => 10,
                'order' => 1,
                'is_published' => true,
                'status' => 'published',
            ]
        );
    }

    private function assignmentLesson(Module $module, Assignment $assignment): Lesson
    {
        return Lesson::updateOrCreate(
            ['module_id' => $module->id, 'slug' => 'demo-devoir-plan-adressage'],
            [
                'title' => $assignment->title,
                'type' => Lesson::TYPE_ASSIGNMENT,
                'lesson_type' => 'assignment',
                'content' => '<h2>Consignes du devoir</h2><p>Concevez un plan d\'adressage IPv4 pour un réseau de <strong>3 sous-réseaux</strong> (50, 100 et 200 hôtes). '
                    .'Justifiez vos choix de masque, listez les plages d\'adresses utilisables, puis rendez le plan au format texte ou PDF.</p>',
                'duration_minutes' => 45,
                'order' => 2,
                'is_published' => true,
                'status' => 'published',
            ]
        );
    }

    // ----------------------------------------------------------------------
    // Helpers examen / questions
    // ----------------------------------------------------------------------

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
                    'points' => 1,
                    'difficulty' => 'easy',
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

    private function quizQuestions(): array
    {
        return [
            [
                'type' => 'multiple_choice',
                'question_text' => 'Quelle couche du modèle OSI gère l\'adressage IP et le routage ?',
                'correct_option' => 2,
                'options' => ['Liaison de données', 'Transport', 'Réseau', 'Application'],
                'correct_answer' => 'Réseau',
            ],
            [
                'type' => 'true_false',
                'question_text' => 'Le protocole UDP garantit la livraison des paquets.',
                'correct_answer' => 'false',
            ],
            [
                'type' => 'short_answer',
                'question_text' => 'Quel protocole traduit les noms de domaine en adresses IP ? (réponse en majuscules ou minuscules)',
                'correct_answer' => 'dns',
            ],
        ];
    }

    private function finalExamQuestions(): array
    {
        return [
            [
                'type' => 'multiple_choice',
                'question_text' => 'Quel masque de sous-réseau correspond au préfixe /26 ?',
                'correct_option' => 1,
                'options' => ['255.255.255.128', '255.255.255.192', '255.255.255.224', '255.255.255.240'],
                'correct_answer' => '255.255.255.192',
            ],
            [
                'type' => 'multiple_choice',
                'question_text' => 'Combien de sous-réseaux utilisables offre un /27 sur un réseau /24 ?',
                'correct_option' => 0,
                'options' => ['8', '16', '30', '32'],
                'correct_answer' => '8',
            ],
            [
                'type' => 'true_false',
                'question_text' => 'Une adresse 169.254.x.x indique un échec d\'obtention DHCP (APIPA).',
                'correct_answer' => 'true',
            ],
            [
                'type' => 'short_answer',
                'question_text' => 'Quelle commande Cisco affiche la synthèse des interfaces et de leurs adresses IP ? (show ip interface ...)',
                'correct_answer' => 'brief',
            ],
        ];
    }

    // ----------------------------------------------------------------------
    // Mini PDF généré (1 page, texte simple) — valide et consultable
    // ----------------------------------------------------------------------

    private function buildDemoPdf(): string
    {
        $lines = [
            'GUIDE DE CONFIGURATION RESEAU (demo)',
            'SubNetCongo e-Learning',
            '',
            '1. Plan d adressage :',
            '   - Choisissez un masque en fonction du nombre d hotes.',
            '   - Reservez la premiere et la derniere adresse.',
            '   - Documentez chaque sous-reseau (VLAN, usage, plage).',
            '',
            '2. Configuration routeur (Cisco IOS) :',
            '   enable',
            '   configure terminal',
            '   interface GigabitEthernet0/0',
            '   ip address 192.168.1.1 255.255.255.0',
            '   no shutdown',
            '',
            '3. Verification :',
            '   show ip interface brief',
            '   show ip route',
            '   ping 192.168.1.254',
        ];

        $esc = fn (string $s) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
        $y = 760;
        $text = "BT /F1 12 Tf 50 {$y} Td 14 TL\n";
        foreach ($lines as $line) {
            $text .= "({$esc($line)}) Tj T*\n";
        }
        $text .= 'ET';

        $objects = [
            "<< /Type /Catalog /Pages 2 0 R >>",
            "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>",
            "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
            "<< /Length ".strlen($text)." >>\nstream\n{$text}\nendstream",
        ];

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

        return $pdf;
    }
}
