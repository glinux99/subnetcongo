<?php

namespace App\Http\Controllers;

/**
 * ======================================================================================
 * SUBNETCONGO LMS - EXAM & EVALUATION CORE ENGINE v11.0.0 (Enterprise Edition)
 * ======================================================================================
 */

use App\Models\{Exam, Course, Question, ExamAttempt, ExamResult, AuditLog, Category};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log, Cache, Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Exception;

class ExamController extends Controller
{
    public const TYPE_MCQ      = 'multiple_choice';
    public const TYPE_BOOLEAN  = 'true_false';
    public const TYPE_TEXT     = 'short_answer';

    public const STATUS_DRAFT     = 'Draft';
    public const STATUS_ACTIVE    = 'Active';
    public const STATUS_ARCHIVED  = 'Archived';

    public function index(Request $request)
    {
        // Chargement optimisé avec relations
        $query = Exam::with(['course:id,title', 'category:id,name', 'questions.options']);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%")
                  ->orWhereHas('course', fn($q) => $q->where('title', 'like', "%{$search}%"));
        }

        // Filtres
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('course_id')) $query->where('course_id', $request->course_id);

        $exams = $query->latest()->paginate($request->input('per_page', 10))->withQueryString();

        $exams->getCollection()->transform(function ($exam) {
            $exam->avg_score = Cache::remember("exam_avg_score_{$exam->id}", 300, function() use ($exam) {
                return round(ExamAttempt::where('exam_id', $exam->id)->avg('score') ?? 0, 1);
            });
            $exam->questions_count = $exam->questions->count();
            return $exam;
        });

        return Inertia::render('Exams', [
            'exams' => $exams,
            'courses' => Course::select('id', 'title')->get(),
            'categories' => Category::select('id', 'name')->get(),
            'stats_overview' => $this->getStatsOverview(),
            'filters' => $request->only(['search', 'status', 'course_id']),
            'global_question_bank' => $this->getGlobalQuestionBank(),
        ]);
    }

    public function store(Request $request)
    {
        return $this->saveExam($request, new Exam());
    }

    public function update(Request $request, Exam $exam)
    {
        return $this->saveExam($request, $exam);
    }

    private function saveExam(Request $request, Exam $exam)
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'course_id'        => 'nullable|exists:courses,id',
            'category_id'      => 'nullable|exists:categories,id',
            'type'             => ['required', Rule::in(['Quiz', 'Certification', 'Placement'])],
            'status'           => ['required', Rule::in([self::STATUS_DRAFT, self::STATUS_ACTIVE, self::STATUS_ARCHIVED])],
            'passing_score'    => 'required|numeric|min:0|max:100',
            'duration_minutes' => 'required|integer|min:1',
            'attempts_allowed' => 'required|integer|min:0',
            'description'      => 'nullable|string',
            'settings'         => 'nullable|array',
            'questions'        => 'nullable|array',
            'is_published'     => 'nullable|boolean',
            'password'         => 'nullable|string|max:255',
            'available_from'   => 'nullable|date',
            'available_until'  => 'nullable|date|after_or_equal:available_from',
        ]);

        DB::beginTransaction();
        try {
            $isNew = !$exam->exists;
            $settings = $validated['settings'] ?? [];

            $exam->fill([
                'uuid'             => $isNew ? (string) Str::uuid() : $exam->uuid,
                'title'            => $validated['title'],
                'course_id'        => $validated['course_id'],
                'category_id'      => $validated['category_id'],
                'type'             => $validated['type'],
                'status'           => $validated['status'],
                'pass_percentage'  => $validated['passing_score'],
                'duration_minutes' => $validated['duration_minutes'],
                'description'      => $validated['description'],
                'settings'         => $settings,
                'created_by'       => $isNew ? (Auth::id() ?? 1) : $exam->created_by,
                'attempts_allowed' => $validated['attempts_allowed'],
                'is_published'     => $validated['is_published'] ?? ($validated['status'] === self::STATUS_ACTIVE),
                'password'         => $validated['password'],
                'available_from'   => $validated['available_from'],
                'available_until'  => $validated['available_until'],
                'time_limit_minutes' => $validated['duration_minutes'],

                'randomize_questions'      => $settings['shuffle_questions'] ?? false,
                'randomize_options'        => $settings['shuffle_options'] ?? false,
                'show_results_immediately' => $settings['show_results_instantly'] ?? false,
                'show_correct_answers'     => $settings['allow_review'] ?? false,
                'require_lockdown_browser' => ($settings['proctoring_mode'] ?? 'none') !== 'none',
            ]);
            $exam->save();

            if ($request->has('questions')) {
                $this->syncQuestions($exam, $request->input('questions'));
                $exam->update(['total_points' => $exam->questions()->sum('points')]);
            }

            $this->logActivity($isNew ? 'EXAM_CREATED' : 'EXAM_UPDATED', $exam);
            DB::commit();

            return back()->with('success', 'L\'évaluation a été enregistrée avec succès.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("SNC_EXAM_SAVE_ERR: " . $e->getMessage());
            return back()->with('error', 'Erreur critique : ' . $e->getMessage());
        }
    }

    private function syncQuestions(Exam $exam, array $questions)
    {
        $keepQuestionIds = [];

        foreach ($questions as $index => $qData) {
            $qText = $qData['text'] ?? $qData['question_text'] ?? null;
            if (empty($qText)) continue;

            // Gestion des IDs : Ignorer les IDs temporaires du frontend (ex: new_123)
            $rawId = $qData['id'] ?? null;
            $questionId = (is_numeric($rawId)) ? $rawId : null;

            $type = $qData['type'] ?? self::TYPE_MCQ;
            $points = $qData['points'] ?? 1;

            $data = [
                'question_text'  => $qText,
                'type'           => $type,
                'points'         => floatval($points),
                'explanation'    => $qData['explanation'] ?? null,
                'order'          => $index,
                'correct_answer' => $qData['correct_answer'] ?? null,
            ];

            if (!$questionId) {
                $data['uuid'] = (string) Str::uuid();
                $data['created_by'] = Auth::id() ?? 1;
            }

            // Gestion de l'image
            if (isset($qData['image']) && $qData['image'] instanceof \Illuminate\Http\UploadedFile) {
                if ($questionId) {
                    $oldQ = $exam->questions()->find($questionId);
                    if ($oldQ && $oldQ->image_path) Storage::disk('media')->delete($oldQ->image_path);
                }
                $data['image_path'] = $qData['image']->store('exams/questions', 'media');
            }

            $question = $exam->questions()->updateOrCreate(['id' => $questionId], $data);
            $keepQuestionIds[] = $question->id;

            // Synchronisation des options (uniquement pour MCQ)
            if ($type === self::TYPE_MCQ) {
                $incomingOptions = $qData['options'] ?? [];
                $optionIds = [];

                foreach ($incomingOptions as $optData) {
                    $optId = (isset($optData['id']) && is_numeric($optData['id'])) ? $optData['id'] : null;
                    $option = $question->options()->updateOrCreate(
                        ['id' => $optId],
                        [
                            'option_text' => $optData['option_text'],
                            'is_correct'  => filter_var($optData['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        ]
                    );
                    $optionIds[] = $option->id;

                    // Si c'est l'option correcte, on peut mettre à jour correct_answer de la question
                    if ($option->is_correct) {
                        $question->update(['correct_answer' => $option->id]);
                    }
                }
                // Nettoyage des options supprimées
                $question->options()->whereNotIn('id', $optionIds)->delete();
            } else {
                // Pour True/False ou Text, on nettoie les options
                $question->options()->delete();
                // Pour True/False, on s'assure que correct_answer est bien 'true' ou 'false'
                if ($type === self::TYPE_BOOLEAN && isset($qData['correct'])) {
                    $question->update(['correct_answer' => $qData['correct'] ? 'true' : 'false']);
                }
            }
        }

        // Supprimer les questions qui ne sont plus dans la liste envoyée
        $exam->questions()->whereNotIn('id', $keepQuestionIds)->delete();
    }

    public function destroy(Exam $exam)
    {
        if ($exam->attempts()->exists()) {
            return back()->with('error', 'Action impossible : cet examen possède déjà des tentatives de passage.');
        }

        DB::transaction(function () use ($exam) {
            foreach($exam->questions as $question) {
                if ($question->image_path) Storage::disk('media')->delete($question->image_path);
                $question->options()->delete();
            }
            $exam->questions()->delete();
            $exam->delete();
        });

        return back()->with('success', 'Évaluation supprimée.');
    }

    protected function logActivity(string $event, $model)
    {
        AuditLog::create([
            'user_id'        => Auth::id() ?? 1,
            'event'          => $event,
            'auditable_type' => get_class($model),
            'auditable_id'   => $model->id,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent()
        ]);
    }

    protected function getStatsOverview(): array
    {
        return [
            'total_exams'    => Exam::count(),
            'active_exams'   => Exam::where('status', self::STATUS_ACTIVE)->count(),
            'total_attempts' => ExamAttempt::count(),
            'avg_pass_rate'  => round(ExamResult::where('is_passed', true)->count() / max(ExamResult::count(), 1) * 100, 1),
        ];
    }

    protected function getGlobalQuestionBank()
    {
        return Question::latest()->take(50)->get();
    }
}
