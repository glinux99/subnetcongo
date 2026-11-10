<?php

namespace App\Listeners;

use App\Events\ExamFailed;
use App\Events\ExamPassed;
use App\Services\Email\EmailTemplateService;
use Illuminate\Support\Facades\Log;

class SendExamResultEmail
{
    public function __construct(protected EmailTemplateService $emailService)
    {
    }

    public function handle(ExamPassed|ExamFailed $event): void
    {
        $result = $event->result;
        $attempt = $event->attempt;
        $user = $result->user ?? $attempt->user;
        $exam = $result->exam ?? $attempt->exam;

        if (!$user || !$exam) {
            Log::warning('SendExamResultEmail: Missing user or exam', ['result_id' => $result->id ?? null]);
            return;
        }

        $isPassed = $event instanceof ExamPassed;
        $templateKey = $isPassed ? 'exam_passed' : 'exam_failed';

        $attemptCount = \App\Models\ExamAttempt::where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->where('status', \App\Models\ExamAttempt::STATUS_COMPLETED)
            ->count();

        $this->emailService->sendTemplatedEmail($templateKey, $user, [
            'exam_name' => $exam->title,
            'score' => $result->score_percentage ?? 0,
            'passing_score' => $exam->pass_percentage ?? 0,
            'attempt_number' => $attemptCount,
            'exam_url' => route('exams.results', $result->uuid),
            'certificate_url' => route('student.certificates'),
            'if_certificate' => $isPassed
                ? 'Si votre formation est terminée, votre certificat est disponible dans votre espace.'
                : '',
        ]);
    }
}
