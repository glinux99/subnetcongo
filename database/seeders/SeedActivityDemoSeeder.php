<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Contenu de démonstration à attacher aux comptes SEEDÉS du site
 * (étudiants @subnetcongo.net créés par UserSeeder + admin@subnetcongo.net).
 *
 * Aucune donnée personnelle n'est envoyée en production :
 * - AUCUN compte TEST n'est créé,
 * - AUCUN email n'est envoyé (insertions directes en base, sans contrôleurs),
 * - les IP utilisées sont 127.0.0.1,
 * - les campagnes/abonnements factices (MRR inventée) ne sont PAS créés.
 *
 * Idempotent : chaque création est protégée par un test d'existence.
 */
class SeedActivityDemoSeeder extends Seeder
{
    private function student(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /** updateOrCreate maison pour le query builder (non disponible sur DB::table) */
    private function updateOrCreateRow(string $table, array $keyWhere, array $values): void
    {
        $exists = DB::table($table)->where($keyWhere)->exists();
        if ($exists) {
            DB::table($table)->where($keyWhere)->update($values + ['updated_at' => now()]);
        } else {
            DB::table($table)->insert($values + $keyWhere + ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function courseId(string $slug): ?int
    {
        return DB::table('courses')->where('slug', $slug)->value('id');
    }

    public function run(): void
    {
        try {
            $this->command->info('🌱 Seed activité de démonstration (comptes seedés @subnetcongo.net uniquement)');
        } catch (\Throwable) {
            // exécuté hors console
        }

        DB::transaction(function () {
            $this->seedCoupons();
            $this->seedCertificatesAndVerifications();
            $this->seedExamAttemptsAndResults();
            $this->seedTicketsAndMessages();
            $this->seedLiveSession();
            $this->seedAssignmentsAndSubmissions();
        });
    }

    /* ---------- Coupons ---------- */
    private function seedCoupons(): void
    {
        $coupons = [
            ['code' => 'RENTREE2026',    'description' => 'Campagne rentrée 2026 — 20% sur tous les cours', 'discount_type' => 'percent', 'discount_value' => 20, 'status' => 'active', 'is_active' => true],
            ['code' => 'STUDENT10',      'description' => 'Réduction fidélité étudiants',                   'discount_type' => 'percent', 'discount_value' => 10, 'status' => 'active', 'is_active' => true],
            ['code' => 'BLACKFRIDAY25',  'description' => 'Offre Black Friday — épuisée',                   'discount_type' => 'percent', 'discount_value' => 25, 'status' => 'active', 'is_active' => true],
            ['code' => 'PARTNER50',      'description' => 'Coupon partenaires — 50$ de remise',             'discount_type' => 'fixed',   'discount_value' => 50, 'status' => 'active', 'is_active' => true],
            ['code' => 'BIENVENUE15',    'description' => 'Offre de bienvenue nouveaux inscrits',           'discount_type' => 'percent', 'discount_value' => 15, 'status' => 'active', 'is_active' => true],
        ];

        foreach ($coupons as $coupon) {
            $this->updateOrCreateRow('coupons', ['code' => $coupon['code']], $coupon);
        }
        $this->say('✓ 5 coupons');
    }

    /* ---------- Certificats (3) + vérifications publiques ---------- */
    private function seedCertificatesAndVerifications(): void
    {
        $template = CertificateTemplate::query()->where('is_active', true)->inRandomOrder()->first()
            ?? CertificateTemplate::query()->first();
        if (!$template) {
            $this->say('… aucun template de certificat, tentative annulée');
            return;
        }

        $targets = [
            ['c.moundi@subnetcongo.net', 'cisco-ccna-200-301-le-guide-complet', 16.5, 'Active'],
            ['e.boungou@subnetcongo.net', 'mikrotik-routeros-formation-complete', 17.5, 'Active'],
            ['s.ndinga@subnetcongo.net', 'ubiquiti-unifi-architecture-reseau', 14.0, 'Active'],
        ];

        foreach ($targets as [$email, $slug, $score, $status]) {
            $user = $this->student($email);
            $courseId = $this->courseId($slug);
            if (!$user || !$courseId) {
                $this->say("… cible introuvable: $email / $slug");
                continue;
            }

            $exists = Certificate::where('user_id', $user->id)->where('course_id', $courseId)->exists();
            if ($exists) {
                $this->say("… certificat déjà présent: {$user->name} — $slug");
                continue;
            }

            $code = 'SC-' . now()->year . '-' . strtoupper(Str::random(8));
            $certificate = new Certificate();
            $certificate->uuid = (string) Str::uuid();
            $certificate->certificate_code = $code;
            $certificate->user_id = $user->id;
            $certificate->course_id = $courseId;
            $certificate->template_id = $template->id;
            $certificate->score = $score;
            $certificate->issued_at = now();
            $certificate->status = $status;
            $certificate->verification_hash = hash('sha256', "SNC-CERT-{$user->id}-{$courseId}-" . now()->timestamp . '-' . config('app.key'));
            $certificate->metadata = json_encode([
                'student_name_at_issue' => $user->name,
                'course_title_at_issue' => DB::table('courses')->where('id', $courseId)->value('title'),
                'issuer_ip' => '127.0.0.1',
            ]);
            $certificate->save();

            // Quelques vérifications fictives 127.0.0.1 (la page publique affichera un historique non vide)
            for ($i = 0; $i < 2; $i++) {
                DB::table('certificate_verifications')->updateOrInsert(
                    ['certificate_id' => $certificate->id, 'verified_at' => now()->subMinutes(42 + $i)],
                    ['ip_address' => '127.0.0.1', 'user_agent' => 'curl/8.5 (IP locale de démonstration)']
                );
            }
        }
        $this->say('✓ Certificats + vérifications');
    }

    /* ---------- Exam attempts / results pour les mêmes cursus ---------- */
    private function seedExamAttemptsAndResults(): void
    {
        $pairs = [
            ['c.moundi@subnetcongo.net', 'cisco-ccna-200-301-le-guide-complet', 70],
            ['e.boungou@subnetcongo.net', 'mikrotik-routeros-formation-complete', 85],
            ['s.ndinga@subnetcongo.net', 'ubiquiti-unifi-architecture-reseau', 60],
        ];

        foreach ($pairs as [$email, $slug, $scorePercent]) {
            $user = $this->student($email);
            $courseId = $this->courseId($slug);
            if (!$user || !$courseId) continue;

            $exam = DB::table('exams')->where('course_id', $courseId)->where('is_final_exam', true)->first();
            if (!$exam) {
                $this->say("… examen final absent pour $slug");
                continue;
            }

            $exists = DB::table('exam_attempts')->where('exam_id', $exam->id)->where('user_id', $user->id)->where('status', 'submitted')->exists();
            if ($exists) {
                $this->say("… tentative déjà présente: user {$user->id} — examen {$exam->id}");
                continue;
            }

            $now = now();
            $startedAt = $now->copy()->subDays(6);
            $completedAt = $now->copy()->subDays(5);

            $attemptId = DB::table('exam_attempts')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'exam_id' => $exam->id,
                'question_id' => null,
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'status' => 'submitted',
                'score' => $scorePercent,
                'raw_score' => null,
                'answers' => json_encode(['attempt' => true, 'source' => 'seed_demo']),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'démo (données de seeding)',
                'created_at' => $startedAt,
                'updated_at' => $completedAt,
            ]);

            DB::table('exam_results')->insert([
                'user_id' => $user->id,
                'exam_id' => $exam->id,
                'course_id' => $courseId,
                'exam_attempt_id' => $attemptId,
                'total_points' => 100,
                'score_percentage' => $scorePercent,
                'is_passed' => $scorePercent >= 60,
                'passed_at' => $scorePercent >= 60 ? $completedAt : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $this->say('✓ Tentatives et résultats d’examen');
    }

    /* ---------- Tickets support (2) + messages internes ---------- */
    private function seedTicketsAndMessages(): void
    {
        $targets = [
            ['e.boungou@subnetcongo.net', 'Paiement & Facturation', 'Test ticket support — paiement mobile money', 'pending', 'high', [
                ['Envoyez-moi la facture de la formation MikroTik svp.', false],
                ['Facture envoyée par email (test). Cordialement.', true],
            ]],
            ['admin@subnetcongo.net',       'Examens & Certificats', 'Impossible de télécharger mon certificat',   'in_progress', 'medium', [
                ['Test : le certificat génère une page blanche (démo).', false],
                ['Ticket routé au dev (test).', true],
            ]],
        ];

        foreach ($targets as $i => [$email, $categoryName, $subject, $status, $priority, $messages]) {
            $user = $this->student($email) ?? User::where('email', $email)->first();
            $categoryId = DB::table('ticket_categories')->where('name', 'like', '%' . $categoryName . '%')->value('id');
            if (!$user) {
                $this->say("… user introuvable: $email");
                continue;
            }

            $exists = DB::table('support_tickets')->where('user_id', $user->id)->where('subject', $subject)->exists();
            if ($exists) {
                $this->say("… ticket déjà présent: $subject");
                continue;
            }

            $ticketId = DB::table('support_tickets')->insertGetId([
                'ticket_number' => 'ticket-seed' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'user_id' => $user->id,
                'ticket_category_id' => $categoryId,
                'assigned_to_user_id' => User::where('email', 'admin@subnetcongo.net')->value('id'),
                'subject' => $subject,
                'description' => 'Ticket de démonstration seedée (pas une vraie demande).',
                'status' => $status,
                'priority' => $priority,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(1),
                'deleted_at' => null,
            ]);

            foreach ($messages as $j => [$body, $internal]) {
                DB::table('support_ticket_messages')->insert([
                    'support_ticket_id' => $ticketId,
                    'user_id' => $user->id,
                    'message' => $body,
                    'is_internal' => (bool) $internal,
                    'created_at' => now()->subDays(2)->addMinutes(30 * ($j + 1)),
                    'updated_at' => now()->subDays(2)->addMinutes(30 * ($j + 1)),
                ]);
            }
        }
        $this->say('✓ 2 tickets support + messages');
    }

    /* ---------- Session live terminée (démo, non visible publique) ---------- */
    private function seedLiveSession(): void
    {
        $instructor = User::where('email', 'joackimmuholu@gmail.com')->first();
        $courseId = $this->courseId('introduction-aux-reseaux-informatiques');
        if (!$instructor || !$courseId) {
            $this->say('… introuvable pour live session');
            return;
        }

        $exists = DB::table('live_sessions')->where('title', 'Session de démo seedée : réseau')->exists();
        if ($exists) {
            $this->say('… session de démo déjà présente');
            return;
        }

        $start = now()->subDays(7)->setTime(19, 0);
        $sessionId = DB::table('live_sessions')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'course_id' => $courseId,
            'instructor_id' => $instructor->id,
            'title' => 'Session de démo seedée : réseau',
            'start_at' => $start,
            'actual_start_at' => $start,
            'ended_at' => $start->copy()->addMinutes(90),
            'duration' => 90,
            'provider' => 'jitsi',
            'max_participants' => 100,
            'is_recorded' => false,
            'passcode' => null,
            'settings' => json_encode(['visibility' => 'private']),
            'meeting_id' => null,
            'meeting_url' => 'https://meet.example.private/seed-demo',
            'host_url' => null,
            'created_by' => $instructor->id,
            'metadata' => json_encode(['seed_demo' => true]),
            'status' => 'Ended',
            'created_at' => $start,
            'updated_at' => $start->copy()->addMinutes(90),
        ]);

        foreach (['e.boungou@subnetcongo.net', 's.ndinga@subnetcongo.net'] as $email) {
            $student = $this->student($email);
            if (!$student) continue;
            DB::table('live_attendances')->updateOrInsert(
                ['live_session_id' => $sessionId, 'user_id' => $student->id],
                ['uuid' => (string) Str::uuid(), 'joined_at' => $start, 'left_at' => $start->copy()->addMinutes(90), 'duration_minutes' => 90, 'status' => 'attended', 'created_at' => $start, 'updated_at' => $start]
            );
        }

        foreach (['e.boungou@subnetcongo.net', 's.ndinga@subnetcongo.net', 'joackimmuholu@gmail.com', 'admin@subnetcongo.net'] as $email) {
            $speaker = $this->student($email) ?? User::where('email', $email)->first();
            if (!$speaker) continue;
            DB::table('live_session_chat_messages')->insert([
                'live_session_id' => $sessionId,
                'user_id' => $speaker->id,
                'user_name' => $speaker->name,
                'message' => 'Message de démonstration seedée.',
                'created_at' => $start->copy()->addMinutes(15),
            ]);
        }
        $this->say('✓ Session live seedée + présences + chat');
    }

    /* ---------- Assignments (2) + submissions seedés ---------- */
    private function seedAssignmentsAndSubmissions(): void
    {
        $assignments = [
            ['cisco-ccna-200-301-le-guide-complet', 'Examen Final : Cisco CCNA 200-301 : Le Guide Complet'],
            ['mikrotik-routeros-formation-complete', 'Examen Final : MikroTik RouterOS : Formation Complète'],
        ];

        foreach ($assignments as $i => [$slug, $title]) {
            $courseId = $this->courseId($slug);
            if (!$courseId) continue;
            $exam = DB::table('exams')->where('course_id', $courseId)->where('is_final_exam', true)->first();
            if (!$exam) continue;

            $exists = DB::table('assignments')->where('course_id', $courseId)->where('title', $title)->exists();
            if ($exists) continue;

            $assignmentId = DB::table('assignments')->insertGetId([
                'course_id' => $courseId,
                'instructor_id' => null,
                'title' => $title,
                'description' => "Soumission pour l'examen : $title",
                'max_points' => 100,
                'due_at' => now()->addDays(14),
                'type' => 'Examen',
                'settings' => json_encode(['exam_id' => $exam->id]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $targets = ['c.moundi@subnetcongo.net', 'e.boungou@subnetcongo.net'];
            foreach ($targets as $email) {
                $student = $this->student($email);
                if (!$student) continue;
                DB::table('submissions')->insert([
                    'user_id' => $student->id,
                    'course_id' => $courseId,
                    'assignment_id' => $assignmentId,
                    'instructor_id' => null,
                    'student_id' => $student->id,
                    'is_graded' => true,
                    'status' => 'graded',
                    'content' => json_encode(['1' => 1, '2' => 6, '3' => 10, '4' => 14, '5' => 17, '6' => 22]),
                    'file_path' => null,
                    'submitted_at' => now()->subDays(3),
                    'grade' => 80 + $i * 10,
                    'feedback_text' => 'Félicitations, vous avez réussi !',
                    'rubric_results' => null,
                    'graded_at' => now()->subDays(3),
                    'graded_by' => User::where('email', 'admin@subnetcongo.net')->value('id'),
                    'grading_duration' => null,
                    'internal_notes' => null,
                    'plagiarism_verified' => false,
                    'created_at' => now()->subDays(3),
                    'updated_at' => now()->subDays(3),
                ]);
            }
        }
        $this->say('✓ 2 devoirs + 4 soumissions');
    }

    private function say(string $message): void
    {
        try {
            $this->command?->info($message);
        } catch (\Throwable) {
            error_log($message);
        }
    }
}
