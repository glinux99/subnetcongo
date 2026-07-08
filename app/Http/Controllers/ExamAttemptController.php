<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use App\Models\ExamAttempt;
use App\Models\Submission;
use App\Models\ExamResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class ExamAttemptController extends Controller
{
    /**
 * Liste de tous les examens disponibles pour l'apprenant
 */
public function index()
{
    $user = Auth::user();

    $exams = Exam::published()
        ->with(['course', 'category'])
        ->with(['results' => function($q) use ($user) {
            $q->where('user_id', $user->id)->orderBy('score_percentage', 'desc');
        }])
        ->withCount(['attempts' => function($q) use ($user) {
            $q->where('user_id', $user->id);
        }])
        ->get()
        ->map(function($exam) use ($user) {
            // On injecte des métadonnées pour l'UI
            $exam->best_score = $exam->results->first()?->score_percentage;
            $exam->is_passed = $exam->results->where('is_passed', true)->count() > 0;

            // Vérifier s'il y a une tentative en cours pour le bouton "Reprendre"
            $exam->active_attempt_uuid = ExamAttempt::where('user_id', $user->id)
                ->where('exam_id', $exam->id)
                ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
                ->value('uuid');

            $exam->remaining_attempts = $exam->attempts_allowed === 0
                ? 'Illimité'
                : max(0, $exam->attempts_allowed - $exam->attempts_count);

            return $exam;
        });

    return  Inertia::render('ExamsIndex', [
        'exams' => $exams
    ]);
}
    /**
     * Affiche l'interface de l'examen (Intro ou Test en cours)
     */
    public function show(Exam $exam)
    {
        // 1. Vérifications de sécurité
        if (!$exam->is_published || $exam->status !== Exam::STATUS_ACTIVE) {
            return redirect()->back()->with('error', 'Cet examen n\'est pas disponible.');
        }

        $user = Auth::user();

        // 2. Vérifier s'il y a une tentative en cours
        $attempt = ExamAttempt::where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
            ->first();

        // 3. Sinon, vérifier le nombre de tentatives autorisées
        if (!$attempt && !$exam->userHasAttemptsRemaining($user)) {
            return redirect()->back()->with('error', 'Vous avez atteint la limite de tentatives pour cet examen.');
        }

        // 4. Charger les questions (sans les réponses correctes pour la sécurité)
        $questions = Question::where('exam_id', $exam->id)
            ->with(['options' => function($query) {
                $query->select('id', 'question_id', 'option_text', 'order');
                // Eager load all potential fields needed for processing
                $query->select('id', 'question_id', 'option_text', 'order', 'is_match_source', 'match_id');
            }])
            ->orderBy('order')
            ->get();


        // 5. Post-traitement des questions pour les types complexes (Matching, Ordering)
        $questions->transform(function ($question) use ($exam) {
            if ($question->type === 'matching') {
                $left = $question->options->where('is_match_source', true)->values();
                $right = $question->options->where('is_match_source', false)->values();

                // Préparer la structure 'pairs' attendue par le frontend
                $question->pairs = [
                    'left' => $left,
                    'right' => $right->shuffle(), // Mélanger les options de droite
                ];
                unset($question->options); // Nettoyer pour ne pas envoyer de données redondantes
            }

            if ($question->type === 'ordering' && $exam->randomize_options) {
                $question->options = $question->options->shuffle()->values();
            }
            return $question;
        });

        // Si pas de tentative en cours, on en créera une au clic sur "Commencer" (via la méthode start)
        return Inertia::render('ExamsResponses', [
            'exam' => $exam,
            'attempt' => $attempt,
            'questions' => $questions,
            'user' => ['name' => $user->name, 'avatar' => $user->profile_photo_url],
        ]);
    }

    /**
     * Initialise une nouvelle tentative
     */
    public function start(Exam $exam)
    {
        $user = Auth::user();

        // Sécurité : Vérifier si une tentative est déjà en cours pour cet examen
        $existingAttempt = ExamAttempt::where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
            ->first();

        if ($existingAttempt) {
            return redirect()->route('exams.take', $exam->id)->with('info', 'Vous avez déjà une tentative en cours.');
        }
        if (!$exam->userHasAttemptsRemaining($user)) {
            return response()->json(['message' => 'Limite atteinte'], 403);
            return redirect()->route('exams.learner.index')->with('error', 'Vous avez atteint la limite de tentatives pour cet examen.');
        }

        $attempt = ExamAttempt::create([
            'user_id' => $user->id,
            'exam_id' => $exam->id,
            'started_at' => now(),
            'status' => ExamAttempt::STATUS_IN_PROGRESS,
            'time_left' => $exam->duration_minutes * 60, // Initialiser le temps restant
            'answers' => [],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->route('exams.take', $exam->id);
    }

    /**
     * Sauvegarde intermédiaire des réponses (Sync auto)
     */
    public function updateAnswers(Request $request, $uuid)
    {
        $attempt = ExamAttempt::where('uuid', $uuid)->firstOrFail();

        if ($attempt->status !== ExamAttempt::STATUS_IN_PROGRESS) {
            return response()->json(['message' => 'Tentative déjà close'], 422);
        }

        $attempt->update([
            'answers' => $request->answers,
            'time_spent_seconds' => $request->time_spent ?? $attempt->time_spent_seconds,
            'time_spent_seconds' => $request->input('time_spent', $attempt->time_spent_seconds),
            'time_left' => $request->input('time_left', $attempt->time_left),
            // Fusionner les métadonnées de proctoring
            'proctoring_data' => array_merge($attempt->proctoring_data ?? [], $request->input('metadata', [])),
        ]);

        return response()->json(['status' => 'success']);
    }

    /**
     * Enregistre un incident de triche (Proctoring)
     */
    public function flag(Request $request, $uuid)
    {
        $attempt = ExamAttempt::where('uuid', $uuid)->firstOrFail();

        $proctoring = $attempt->proctoring_data ?? [];
        $proctoring[] = [
            'event' => $request->reason,
            'time' => now()->toDateTimeString(),
            'ip' => $request->ip()
        ];

        $attempt->update(['proctoring_data' => $proctoring]);

        return response()->json(['status' => 'flagged']);
    }

    /**
     * Soumission finale et Correction automatique
     */
    public function submit(Request $request, $uuid)
    {
        $attempt = ExamAttempt::where('uuid', $uuid)->with('exam.questions.options')->firstOrFail();

        // Amélioration : Gérer le cas où la tentative a déjà été soumise.
        if ($attempt->status !== ExamAttempt::STATUS_IN_PROGRESS) {
            return redirect()->route('exams.index')->with('info', 'Cette tentative a déjà été soumise.');
        }

        return DB::transaction(function () use ($attempt, $request) {
            $exam = $attempt->exam;
            $userAnswers = $request->input('answers', $attempt->answers ?? []);

            $totalPointsPossible = 0;
            $pointsEarned = 0;
            $hasManualGrading = false; // Flag pour les questions nécessitant une correction manuelle
           //  return $exam->questions;
            // --- ALGORITHME DE CORRECTION AMÉLIORÉ ---
            foreach ($exam->questions as $question) {
                $totalPointsPossible += $question->points;
                $userAnswer = $userAnswers[$question->id] ?? null;

                if ($userAnswer === null) {
                    continue; // Pas de réponse, pas de points.
                }

                switch ($question->type) {
                    case 'multiple_choice':

                        $correctOptionId = collect($question->options)->where('is_correct', true)->value('id');
                        if ($userAnswer == $correctOptionId) {
                            $pointsEarned += $question->points;
                        }
                        break;

                    case 'multiple_choice_multiple':
                        // Notation "tout ou rien" : toutes les bonnes options doivent être cochées, et aucune mauvaise.
                        $correctOptionIds = collect($question->options)->where('is_correct', true)->pluck('id')->map('intval')->sort()->values()->all() ?? [];
                        $userAnswerIds = is_array($userAnswer) ? collect($userAnswer)->map('intval')->sort()->values()->all() : [];
                        if ($correctOptionIds === $userAnswerIds) {
                            $pointsEarned += $question->points;
                        }
                        break;

                    case 'true_false':
                        // La réponse de l'utilisateur (booléen) est comparée à l'attribut 'correct' de la question.
                        if (filter_var($userAnswer, FILTER_VALIDATE_BOOLEAN) === filter_var($question->correct_answer, FILTER_VALIDATE_BOOLEAN)) {
                            $pointsEarned += $question->points;
                        }
                        break;

                    case 'short_answer':
                        // Comparaison insensible à la casse et aux espaces.
                        if (is_string($userAnswer) && trim(strtolower($userAnswer)) === trim(strtolower($question->correct_answer))) {
                            $pointsEarned += $question->points;
                        }
                        break;

                    case 'ordering':
                        // L'utilisateur doit ordonner les IDs d'options dans le bon ordre.
                        $correctOrderIds = collect($question->options)->sortBy('order')->pluck('id')->all() ?? [];
                        $userOrderIds = is_array($userAnswer) ? collect($userAnswer)->map(fn($item) => is_array($item) ? $item['id'] : $item)->all() : [];
                        if ($correctOrderIds == $userOrderIds) { // Loose comparison for type juggling
                            $pointsEarned += $question->points;
                        }
                        break;

                    case 'matching':
                        // Hypothèse : chaque option "gauche" a un attribut 'match_id' contenant l'ID de la bonne option "droite".
                        // et un flag 'is_match_source' pour les identifier.
                        $leftOptions = collect($question->options)->where('is_match_source', true);
                        $isAllCorrect = true;

                        if (!$leftOptions || !is_array($userAnswer) || $leftOptions->isEmpty() || count($userAnswer) !== $leftOptions->count()) {
                            $isAllCorrect = false;
                        } else {
                            foreach ($leftOptions as $leftOption) {
                                if (!isset($userAnswer[$leftOption->id]) || (int)$userAnswer[$leftOption->id] !== (int)$leftOption->match_id) {
                                    $isAllCorrect = false;
                                    break;
                                }
                            }
                        }

                        if ($isAllCorrect) {
                            $pointsEarned += $question->points;
                        }
                        break;

                    case 'essay':
                        // Ce type nécessite une correction manuelle.
                        $hasManualGrading = true;
                        break;
                }
            }

            // Calcul du pourcentage
            $scorePercentage = ($totalPointsPossible > 0) ? round(($pointsEarned / $totalPointsPossible) * 100, 2) : 0;
            $isPassed = $scorePercentage >= $exam->pass_percentage;
            $finalStatus = $hasManualGrading ? ExamAttempt::STATUS_REVIEW : ExamAttempt::STATUS_COMPLETED;

            // Fusion des données de proctoring
            $proctoringData = $attempt->proctoring_data ?? [];
            $requestLogs = $request->input('logs', []);
            $mergedLogs = array_merge($proctoringData, $requestLogs);
            if ($request->has('final_score_proctoring')) {
                $mergedLogs['final_proctoring_score'] = $request->input('final_score_proctoring');
            }

            // 1. Marquer la tentative comme complétée
            $attempt->update([
                'status' => $finalStatus,
                'completed_at' => now(),
                'raw_score' => $pointsEarned,
                'answers' => $userAnswers,
                'time_spent_seconds' => $request->input('time_spent'),
                'proctoring_data' => $mergedLogs,
                'proctoring_data' => $mergedLogs, // Enregistre les logs de proctoring
                'time_left' => 0, // Le temps est écoulé
            ]);

            // 2. Créer le résultat officiel
            $result = ExamResult::create([
                'user_id' => $attempt->user_id,
                'exam_id' => $exam->id,
                'course_id' => $exam->course_id,
                'exam_attempt_id' => $attempt->id,
                'total_points' => $pointsEarned,
                'score_percentage' => $scorePercentage,
                'is_passed' => $hasManualGrading ? false : $isPassed, // On ne peut pas réussir tant que la correction manuelle n'est pas faite
                'passed_at' => !$hasManualGrading && $isPassed ? now() : null,
                'feedback' => $hasManualGrading ? "Votre examen a été soumis et est en attente de correction pour les questions ouvertes." : ($isPassed ? "Félicitations, vous avez réussi !" : "Vous n'avez pas atteint le seuil de réussite."),
            ]);

            // NOUVEAU : Créer une soumission pour le relevé de notes (StudentReportController)
            Submission::create([
                'user_id' => $attempt->user_id,
                'course_id' => $exam->course_id,
                'assignment_id' => $exam->id, // L'examen est l'équivalent du devoir ici
                'status' => $hasManualGrading ? 'pending_review' : 'graded',
                'content' => json_encode($userAnswers),
                'submitted_at' => $attempt->completed_at,
                'is_graded' => !$hasManualGrading,
                'grade' => $scorePercentage,
                'feedback_text' => $result->feedback,
                'graded_at' => $hasManualGrading ? null : now(),
                'graded_by' => $hasManualGrading ? null : 1, // ID de l'utilisateur "Système" pour l'auto-correction
            ]);

            return redirect()->route('exams.results', $result->id);
        });
    }

    /**
     * Affiche le résultat après l'examen
     */
    public function results(ExamResult $result)
    {
        $result->load(['exam', 'attempt']);

        // Sécurité : Seul le propriétaire ou un admin peut voir le résultat
        if ($result->user_id !== Auth::id()) {
            abort(403);
        }

        return Inertia::render('ExamResult', [
            'result' => $result
        ]);
    }
}
