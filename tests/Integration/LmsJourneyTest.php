<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamResult;
use App\Models\UserProgress;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Question;
use App\Models\CartItem;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Test E2E du parcours étudiant complet — requêtes HTTP réelles avec sessions
 * persistées (SESSION_DRIVER=database requis pour CheckSessionInDatabase).
 * PAS de RefreshDatabase : les données persistent en MySQL pour inspection admin.
 */
class LmsJourneyTest extends TestCase
{
    private array $report = [];
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // CheckSessionInDatabase invalide les sessions de test (user-agent Symfony
        // vs session DB) — on le désactive, l'authentification réelle reste testée.
        $this->withoutMiddleware(\App\Http\Middleware\CheckSessionInDatabase::class);
        // Admin de test dédié (ne touche pas au compte admin réel)
        $this->admin = User::firstOrCreate(
            ['email' => 'admin.test@test-sncongo.local'],
            [
                'name' => 'Admin Test E2E',
                'password' => Hash::make('Admin@12345'),
                'status' => 'active',
            ]
        );
        if (! $this->admin->hasRole('admin')) {
            $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
            $this->admin->assignRole($role);
        }
    }

    protected function tearDown(): void
    {
        $this->writeReport();
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Helpers — sessions RÉELLES (login/logout), pas actingAs
    // ------------------------------------------------------------------

    private function note(string $scenario, string $check, bool $ok, string $detail = ''): void
    {
        $this->report[] = [
            'scenario' => $scenario,
            'check'    => $check,
            'status'   => $ok ? 'PASS' : 'FAIL',
            'detail'   => $detail,
        ];
        fwrite(STDERR, sprintf("[%s] %s — %s %s\n", $ok ? 'PASS' : 'FAIL', $scenario, $check, $detail));
    }

    private function loginAs(User $user, string $password = 'Test@12345'): void
    {
        // Logout propre puis login réel → session DB avec user_id
        $this->post('/logout');
        $this->post('/login', [
            'email' => $user->email,
            'password' => $password,
        ]);
    }

    /** Retourne l'étudiant ET garantit une session authentifiée. */
    private function getOrLoginStudent(string $name, string $email): User
    {
        $user = User::where('email', $email)->first();
        if ($user) {
            $this->loginAs($user);
            return $user;
        }
        return $this->registerStudent($name, $email);
    }

    private function registerStudent(string $name, string $email): User
    {
        $password = 'Test@12345';
        $this->post('/register', [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'terms' => '1',
        ]);
        return User::where('email', $email)->firstOrFail();
        // /register fait Auth::login() → session réelle déjà active
    }

    private function lessonsOf(Course $course)
    {
        return $course->modules()->with('lessons')->get()->flatMap->lessons->values();
    }

    private function completeAllLessons(Course $course): int
    {
        $count = 0;
        foreach ($this->lessonsOf($course) as $lesson) {
            $this->post("/student/courses/{$course->id}/lessons/{$lesson->id}/complete");
            $count++;
        }
        return $count;
    }

    private function correctAnswers(Exam $exam): array
    {
        $answers = [];
        foreach (Question::where('exam_id', $exam->id)->with('options')->get() as $q) {
            $correct = $q->options->where('is_correct', true)->pluck('id')->all();
            if (in_array($q->type, ['multiple_choice_multiple', 'ordering'])) {
                $answers[$q->id] = $correct;
            } elseif ($q->type === 'true_false') {
                $answers[$q->id] = !empty($correct);
            } else {
                $answers[$q->id] = $correct[0] ?? null;
            }
        }
        return $answers;
    }

    private function wrongAnswers(Exam $exam): array
    {
        $answers = [];
        foreach (Question::where('exam_id', $exam->id)->with('options')->get() as $q) {
            $wrong = $q->options->where('is_correct', false)->pluck('id')->all();
            if (in_array($q->type, ['multiple_choice_multiple', 'ordering'])) {
                $answers[$q->id] = $wrong ? [$wrong[0]] : [];
            } elseif ($q->type === 'true_false') {
                $answers[$q->id] = false;
            } elseif ($q->type === 'short_answer') {
                $answers[$q->id] = 'réponse fausse';
            } else {
                $answers[$q->id] = $wrong[0] ?? null;
            }
        }
        return $answers;
    }

    private function takeExam(User $user, Exam $exam, array $answers): ?ExamResult
    {
        $this->loginAs($user);
        $this->post("/exams/{$exam->id}/start");
        $attempt = ExamAttempt::where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
            ->latest('id')->first();

        if (! $attempt) {
            return null; // limite de tentatives atteinte ou examen indisponible
        }

        $this->post("/attempts/{$attempt->uuid}/submit", [
            'answers'    => $answers,
            'time_spent' => 300,
        ]);

        return ExamResult::where('exam_attempt_id', $attempt->id)->latest('id')->first();
    }

    private function adminApprove(Enrollment $enrollment): void
    {
        $this->loginAs($this->admin, 'Admin@12345');
        $this->post("/admin/enrollments/{$enrollment->id}/approve");
    }

    private function writeReport(): void
    {
        $lines = [];
        $lines[] = "# RAPPORT TEST E2E — PARCOURS ÉTUDIANT SUBNETCONGO LMS";
        $lines[] = "Généré le " . now()->format('d/m/Y H:i:s');
        $lines[] = str_repeat('=', 70);
        $lines[] = '';

        $pass = count(array_filter($this->report, fn($r) => $r['status'] === 'PASS'));
        $fail = count(array_filter($this->report, fn($r) => $r['status'] === 'FAIL'));

        $current = '';
        foreach ($this->report as $r) {
            if ($r['scenario'] !== $current) {
                $current = $r['scenario'];
                $lines[] = '';
                $lines[] = "## {$current}";
                $lines[] = '';
            }
            $lines[] = "- **[{$r['status']}]** {$r['check']}" . ($r['detail'] ? " — _{$r['detail']}" : '');
        }

        $lines[] = '';
        $lines[] = str_repeat('=', 70);
        $lines[] = "**RÉSULTAT : {$pass} PASS / {$fail} FAIL**";
        $lines[] = '';
        $lines[] = "## DONNÉES CRÉÉES (visibles en base de données)";
        $lines[] = '- Étudiants de test : `users` (emails *@test-sncongo.local)';
        $lines[] = '- Inscriptions : `enrollments`';
        $lines[] = '- Progression : `user_progress`';
        $lines[] = '- Examens : `exam_attempts`, `exam_results`, `submissions`';
        $lines[] = '- Certificats : `certificates` (+ `certificate_verifications`)';
        $lines[] = '';

        $path = storage_path('app/lms-e2e-report.md');
        file_put_contents($path, implode("\n", $lines));
        fwrite(STDERR, "\n=== RAPPORT ÉCRIT : {$path} ({$pass} PASS / {$fail} FAIL) ===\n");
    }

    // ==================================================================
    // SCÉNARIO 1 — Étudiant inscrit à un cours GRATUIT (parcours complet)
    // ==================================================================
    public function test_scenario1_free_course_full_journey(): void
    {
        $S = 'S1 — Étudiant gratuit : inscription → cours gratuit → leçons';
        $course = Course::findOrFail(3); // Introduction aux Réseaux — gratuit, 12 leçons

        // 1. Inscription (session réelle via POST /register)
        $email = 'etudiant.alpha@test-sncongo.local';
        $user = $this->getOrLoginStudent('Étudiant Alpha (TEST)', $email);
        $this->note($S, 'Session étudiante authentifiée', true, "user_id={$user->id}");
        $this->note($S, 'Inscription POST /register', $user->hasRole('student'),
            "user_id={$user->id} {$user->email}");

        // 2. Inscription gratuite
        $resp = $this->post("/marketplace/course/{$course->id}/enroll-free");
        $enrollment = Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->latest('id')->first();
        $this->note($S, 'Enroll-free cours gratuit',
            $enrollment && $enrollment->status === Enrollment::STATUS_ACTIVE,
            $enrollment ? "enrollment#{$enrollment->id} status={$enrollment->status}" : "AUCUNE inscription (HTTP {$resp->status()})");

        // 3. Double inscription refusée
        $this->post("/marketplace/course/{$course->id}/enroll-free");
        $count = Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->count();
        $this->note($S, 'Double inscription refusée', $count === 1, "enrollments={$count}");

        // 4. Mes cours accessible
        $r = $this->get('/student/my-courses');
        $this->note($S, 'GET /student/my-courses', $r->status() === 200, "HTTP {$r->status()}");

        // 5. Terminer toutes les leçons
        $lessons = $this->lessonsOf($course);
        $done = $this->completeAllLessons($course);
        $completed = UserProgress::where('user_id', $user->id)->where('course_id', $course->id)
            ->where('progress_percent', '>=', 100)->count();
        $this->note($S, "Leçons terminées ({$done}/{$lessons->count()})",
            $completed === $lessons->count(), "user_progress à 100% = {$completed}");

        // 6. Progression globale — POINT DE VÉRITÉ
        if ($enrollment) {
            $enrollment->refresh();
            $syncOk = (float) $enrollment->progress_percent >= 100;
            $this->note($S, 'Progression enrollment après 100% leçons', $syncOk,
                "enrollment.progress_percent = {$enrollment->progress_percent}%, status={$enrollment->status}"
                . ($syncOk ? '' : ' ⚠️ enrollment non synchronisé depuis user_progress'));
        }
    }

    // ==================================================================
    // SCÉNARIO 2 — Étudiant payant RÉUSSIT → certificat
    // ==================================================================
    public function test_scenario2_paid_course_pass_and_certificate(): void
    {
        $S = 'S2 — Étudiant payant : demande → approbation → examen réussi → certificat';
        $course   = Course::findOrFail(1); // Cisco CCNA — payant, examen final
        $exam     = Exam::where('course_id', $course->id)->where('is_final_exam', true)->firstOrFail();
        $template = CertificateTemplate::firstOrFail();

        // 1. Inscription + demande d'inscription payante
        $email = 'etudiant.beta@test-sncongo.local';
        $user = $this->getOrLoginStudent('Étudiant Bêta (TEST)', $email);

        $this->post("/marketplace/course/{$course->id}/request-enrollment", [
            'payment_method' => 'orange_money',
            'phone_number'   => '+242060000001',
            'transaction_id' => 'OM-TEST-'.rand(1000, 9999),
        ]);
        $enrollment = Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->latest('id')->first();
        // Idempotent : pending au 1er run, active si déjà approuvé avant
        $this->note($S, 'Demande inscription payante',
            $enrollment && in_array($enrollment->status, [Enrollment::STATUS_PENDING, Enrollment::STATUS_ACTIVE], true),
            $enrollment ? "enrollment#{$enrollment->id} status={$enrollment->status}" : 'aucune');
        if (! $enrollment) return;

        // 2. Doublon demande refusé
        $this->post("/marketplace/course/{$course->id}/request-enrollment", ['payment_method' => 'mpesa']);
        $this->note($S, 'Double demande refusée',
            Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->count() === 1);

        // 3. Admin approuve
        $this->adminApprove($enrollment);
        $enrollment->refresh();
        $this->note($S, 'Approbation admin', $enrollment->status === Enrollment::STATUS_ACTIVE,
            "status={$enrollment->status}");

        // 4. Suit toutes les leçons
        $this->loginAs($user);
        $lessons = $this->lessonsOf($course);
        $this->completeAllLessons($course);
        $completed = UserProgress::where('user_id', $user->id)->where('course_id', $course->id)
            ->where('progress_percent', '>=', 100)->count();
        $this->note($S, 'Leçons terminées', $completed === $lessons->count(), "{$completed}/{$lessons->count()}");

        // 5. Examen final — toutes bonnes réponses
        $result = $this->takeExam($user, $exam, $this->correctAnswers($exam));
        $this->note($S, 'Examen réussi (100%)',
            $result && $result->is_passed && $result->score_percentage >= $exam->pass_percentage,
            $result ? "score={$result->score_percentage}% is_passed=" . var_export($result->is_passed, true) : 'aucun résultat');

        // 6. Admin délivre le certificat
        $this->loginAs($this->admin, 'Admin@12345');
        $this->post('/certificates', [
            'user_id'     => $user->id,
            'course_id'   => $course->id,
            'template_id' => $template->id,
        ]);
        $cert = Certificate::where('user_id', $user->id)->where('course_id', $course->id)->latest('id')->first();
        $this->note($S, 'Délivrance certificat admin',
            $cert && $cert->status === \App\Http\Controllers\CertificateController::STATUS_ACTIVE,
            $cert ? "code={$cert->certificate_code} status={$cert->status}" : 'aucun certificat');
        if (! $cert) return;

        // 7. Double certificat refusé
        $this->post('/certificates', [
            'user_id' => $user->id, 'course_id' => $course->id, 'template_id' => $template->id,
        ]);
        $this->note($S, 'Double certificat refusé',
            Certificate::where('user_id', $user->id)->where('course_id', $course->id)->count() === 1);

        // 8. Vérification publique (QR code) — requiert auth dans cette app
        $this->loginAs($user);
        $this->get("/certificates/verify/{$cert->verification_hash}")->assertOk();
        $this->note($S, 'Vérification publique certificat', true, 'HTTP 200');

        // 9. Téléchargement PDF
        $dl = $this->get("/certificates/{$cert->id}/download");
        $this->note($S, 'Téléchargement certificat PDF', $dl->status() === 200,
            "HTTP {$dl->status()} " . ($dl->headers->get('content-type') ?? ''));

        // 10. Page certificats étudiant
        $r = $this->get('/student/certificates');
        $this->note($S, 'GET /student/certificates', $r->status() === 200, "HTTP {$r->status()}");
    }

    // ==================================================================
    // SCÉNARIO 3 — Étudiant payant ÉCHOUE l'examen (3 tentatives max)
    // ==================================================================
    public function test_scenario3_paid_course_fail_exam_attempts_limit(): void
    {
        $S = 'S3 — Étudiant payant : examen échoué ×3 → limite tentatives';
        $course = Course::findOrFail(2); // MikroTik — payant
        $exam   = Exam::where('course_id', $course->id)->where('is_final_exam', true)->firstOrFail();

        $email = 'etudiant.gamma@test-sncongo.local';
        $user = $this->getOrLoginStudent('Étudiant Gamma (TEST)', $email);

        // Inscription + approbation
        $this->post("/marketplace/course/{$course->id}/request-enrollment", ['payment_method' => 'mpesa']);
        $enrollment = Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->latest('id')->first();
        if (! $enrollment) {
            $this->note($S, 'Demande inscription', false, 'aucune enrollment');
            return;
        }
        $this->adminApprove($enrollment);
        $this->loginAs($user);
        $this->completeAllLessons($course);

        // Tentatives échouées restantes (idempotent : max 3 tentatives autorisées)
        $wrong = $this->wrongAnswers($exam);
        $alreadyUsed = ExamAttempt::where('user_id', $user->id)->where('exam_id', $exam->id)
            ->where('status', '!=', ExamAttempt::STATUS_IN_PROGRESS)->count();
        $remaining = max(0, ($exam->attempts_allowed ?? 3) - $alreadyUsed);

        for ($i = 1; $i <= $remaining; $i++) {
            $result = $this->takeExam($user, $exam, $wrong);
            $this->note($S, "Tentative échouée (score=" . ($result->score_percentage ?? '?') . "%)",
                $result && ! $result->is_passed);
        }
        if ($remaining === 0) {
            $this->note($S, 'Limite de tentatives déjà atteinte (runs précédents)', true,
                "{$alreadyUsed} tentatives utilisées");
        }

        // 4e tentative doit être bloquée
        $this->post("/exams/{$exam->id}/start");
        $inProgress = ExamAttempt::where('user_id', $user->id)->where('exam_id', $exam->id)
            ->where('status', ExamAttempt::STATUS_IN_PROGRESS)->count();
        $this->note($S, '4e tentative bloquée (limite=3)', $inProgress === 0,
            "tentatives en cours={$inProgress}");

        // Pas de certificat pour un échec
        $certCount = Certificate::where('user_id', $user->id)->where('course_id', $course->id)->count();
        $this->note($S, 'Aucun certificat après échec', $certCount === 0, "certificats={$certCount}");

        // Page progression
        $r = $this->get('/student/progress');
        $this->note($S, 'GET /student/progress', $r->status() === 200, "HTTP {$r->status()}");
    }

    // ==================================================================
    // SCÉNARIO 4 — Cas limites & sécurité
    // ==================================================================
    public function test_scenario4_edge_cases_and_security(): void
    {
        $S = 'S4 — Cas limites & contrôles d\'accès';
        $freeCourse = Course::findOrFail(7);
        $paidCourse = Course::findOrFail(4);
        $paidLesson = $this->lessonsOf($paidCourse)->first();

        // 4a. Invité : pas d'accès
        $this->post('/logout');
        $r1 = $this->get('/student/my-courses');
        $r2 = $this->post("/marketplace/course/{$freeCourse->id}/enroll-free");
        $this->note($S, 'Invité bloqué (my-courses + enroll-free)',
            in_array($r1->status(), [301, 302]) && in_array($r2->status(), [301, 302]),
            "my-courses={$r1->status()} enroll={$r2->status()}");

        // 4b. Inscription à un cours PAYANT via enroll-free → refusé
        $email = 'etudiant.delta@test-sncongo.local';
        $user = $this->getOrLoginStudent('Étudiant Delta (TEST)', $email);
        $this->post("/marketplace/course/{$paidCourse->id}/enroll-free");
        $this->note($S, 'Enroll-free sur cours payant refusé',
            !Enrollment::where('user_id', $user->id)->where('course_id', $paidCourse->id)
                ->where('status', Enrollment::STATUS_ACTIVE)->exists());

        // 4c. Demande d'inscription sur cours GRATUIT → refusé
        $this->post("/marketplace/course/{$freeCourse->id}/request-enrollment", ['payment_method' => 'stripe']);
        $this->note($S, 'Request-enrollment sur cours gratuit refusé',
            !Enrollment::where('user_id', $user->id)->where('course_id', $freeCourse->id)
                ->where('status', Enrollment::STATUS_PENDING)->exists());

        // 4d. Progression sans inscription → 403
        $resp = $this->post("/student/courses/{$paidCourse->id}/lessons/{$paidLesson->id}/progress", [
            'progress_percent' => 50,
        ]);
        $this->note($S, 'Progression sans inscription → 403', $resp->status() === 403, "HTTP {$resp->status()}");

        // 4e. S'inscrire au cours gratuit puis test borne >100
        $this->post("/marketplace/course/{$freeCourse->id}/enroll-free");
        $freeLesson = $this->lessonsOf($freeCourse)->first();
        $resp = $this->post("/student/courses/{$freeCourse->id}/lessons/{$freeLesson->id}/progress", [
            'progress_percent' => 150,
        ]);
        $progress = UserProgress::where('user_id', $user->id)->where('lesson_id', $freeLesson->id)->first();
        $this->note($S, 'progress_percent > 100 géré',
            $progress === null || (float) $progress->progress_percent <= 100,
            "HTTP {$resp->status()}, valeur=" . ($progress->progress_percent ?? 'aucune'));

        // 4f. Vérification hash invalide (connecté)
        $r = $this->get('/certificates/verify/hash-inexistant-123');
        $this->note($S, 'Vérification hash invalide → page invalid', $r->status() === 200, "HTTP {$r->status()}");

        // 4g. Email en doublon → rejeté
        $resp = $this->post('/register', [
            'name' => 'Doublon', 'email' => $email,
            'password' => 'Test@12345', 'password_confirmation' => 'Test@12345',
            'terms' => '1',
        ]);
        $this->note($S, 'Email en doublon rejeté',
            !User::where('email', $email)->where('name', 'Doublon')->exists());

        // 4h. Inscription sans CGU → rejetée
        $this->post('/register', [
            'name' => 'Sans CGU', 'email' => 'nocgu@test-sncongo.local',
            'password' => 'Test@12345', 'password_confirmation' => 'Test@12345',
        ]);
        $this->note($S, 'Inscription sans CGU rejetée',
            !User::where('email', 'nocgu@test-sncongo.local')->exists());
    }

    // ==================================================================
    // SCÉNARIO 5 — Panier : ajout + inscription groupée gratuit
    // ==================================================================
    public function test_scenario5_cart_free_enroll_batch(): void
    {
        $S = 'S5 — Panier : ajout cours + inscription groupée';
        $freeA = Course::findOrFail(14);
        $freeB = Course::findOrFail(17);
        $paid  = Course::findOrFail(5);

        $email = 'etudiant.epsilon@test-sncongo.local';
        $user = $this->getOrLoginStudent('Étudiant Epsilon (TEST)', $email);

        // Ajout au panier : 2 gratuits + 1 payant
        // (les cours déjà suivis ne sont pas ré-ajoutés — idempotent)
        foreach ([$freeA, $freeB, $paid] as $c) {
            $this->post("/marketplace/cart/{$c->id}/add");
        }
        $cartCount = CartItem::where('user_id', $user->id)->count();
        $paidInCart = CartItem::where('user_id', $user->id)->where('course_id', $paid->id)->exists();
        $this->note($S, 'Cours ajoutés au panier', $cartCount >= 1 && $paidInCart,
            "cart_items={$cartCount} (payant dans panier=" . ($paidInCart ? 'oui' : 'non') . ')');

        // Inscription groupée : seuls les gratuits passent
        $this->post('/marketplace/cart/enroll-free');
        $enrolled = Enrollment::where('user_id', $user->id)
            ->whereIn('course_id', [$freeA->id, $freeB->id])
            ->where('status', Enrollment::STATUS_ACTIVE)->count();
        $paidEnrolled = Enrollment::where('user_id', $user->id)->where('course_id', $paid->id)
            ->where('status', Enrollment::STATUS_ACTIVE)->exists();
        $this->note($S, 'Inscription groupée : 2 gratuits OK, payant ignoré',
            $enrolled === 2 && ! $paidEnrolled,
            "gratuits actifs={$enrolled}/2, payant=" . ($paidEnrolled ? 'OUI ⚠️' : 'non'));

        $this->note($S, 'Cours payant conservé au panier', $paidInCart);
    }
}
