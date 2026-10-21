<?php

namespace App\Http\Controllers\Elearning;

/**
 * ======================================================================================
 * SUBNETCONGO LMS - CORE EVALUATION & QUESTION BANK ENGINE v11.5.1
 * ======================================================================================
 * Contrôleur orchestrant le cycle de vie des items atomiques d'évaluation.
 * ADAPTÉ POUR LA NOUVELLE UI "WORKSPACE BUILDER" (SPA - Single Page Save).
 *
 * @package App\Http\Controllers
 * @author  SubnetCongo Framework Division
 */

use App\Models\Elearning\{Question, Category, QuestionVersion};
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Exception;
use App\Http\Controllers\Controller;

class QuestionController extends Controller
{
    /**
     * --- DICTIONNAIRE DES TYPES DE QUESTIONS ---
     */
    public const TYPE_MCQ          = 'multiple_choice';
    public const TYPE_TRUE_FALSE   = 'true_false';
    public const TYPE_SHORT_ANSWER = 'short_answer';
    public const TYPE_ESSAY        = 'essay';
    public const TYPE_MATCHING     = 'matching';
    public const TYPE_CODING       = 'coding';

    /**
     * --- ÉTATS DE CYCLE DE VIE ---
     */
    public const STATUS_DRAFT      = 'draft';
    public const STATUS_PUBLISHED  = 'published';
    public const STATUS_REVIEW     = 'under_review';
    public const STATUS_ARCHIVED   = 'archived';

    /**
     * --- NIVEAUX DE DIFFICULTÉ ---
     */
    public const DIFF_BEGINNER     = 'beginner';
    public const DIFF_INTERMEDIATE = 'intermediate';
    public const DIFF_ADVANCED     = 'advanced';
    public const DIFF_EXPERT       = 'expert';

    protected array $typeMetadata = [];

    public function __construct()
    {
        $this->typeMetadata = [
            self::TYPE_MCQ          => ['label' => 'Choix Multiples (QCM)', 'icon' => 'pi-list', 'color' => 'bg-indigo-500'],
            self::TYPE_TRUE_FALSE   => ['label' => 'Vrai / Faux', 'icon' => 'pi-check-circle', 'color' => 'bg-emerald-500'],
            self::TYPE_SHORT_ANSWER => ['label' => 'Réponse Courte', 'icon' => 'pi-pencil', 'color' => 'bg-amber-500'],
            self::TYPE_MATCHING     => ['label' => 'Appariement', 'icon' => 'pi-th-large', 'color' => 'bg-blue-500'],
            self::TYPE_CODING       => ['label' => 'Code / Programmation', 'icon' => 'pi-code', 'color' => 'bg-slate-700'],
        ];
    }

    /**
     * --------------------------------------------------------------------------
     * 1. INDEX ET RECHERCHE AVANCÉE (DATA GRID)
     * --------------------------------------------------------------------------
     */
    public function index(Request $request)
    {
        $query = Question::with(['category:id,name', 'options'])->withCount(['versions', 'attempts']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('question_text', 'like', "%{$search}%")
                  ->orWhere('explanation', 'like', "%{$search}%")
                  ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $query->when($request->type, fn($q, $v) => $q->where('type', $v));
        $query->when($request->category_id, fn($q, $v) => $q->where('category_id', $v));
        $query->when($request->status, fn($q, $v) => $q->where('status', $v));
        $query->when($request->difficulty, fn($q, $v) => $q->where('difficulty', $v));

        $questions = $query->latest()->paginate($request->input('per_page', 15))->withQueryString();

        $statistics = Cache::remember('bank_stats_v11', 300, function() {
            return [
                'total_items'     => Question::count(),
                'active_items'    => Question::where('status', self::STATUS_PUBLISHED)->count(),
                'avg_difficulty'  => round(Question::avg('points') ?? 1.0, 1),
                'needs_review'    => Question::where('status', self::STATUS_REVIEW)->count(),
                'difficulty_distribution' => [
                    Question::where('difficulty', self::DIFF_BEGINNER)->count(),
                    Question::where('difficulty', self::DIFF_INTERMEDIATE)->count(),
                    Question::where('difficulty', self::DIFF_ADVANCED)->count(),
                    Question::where('difficulty', self::DIFF_EXPERT)->count(),
                ]
            ];
        });

        return Inertia::render('Questions', [
            'questions'  => $questions,
            'filters'    => $request->only(['search', 'type', 'category_id', 'difficulty', 'status']),
            'statistics' => $statistics,
            'meta'       => [
                'categories'      => Category::select('id', 'name')->orderBy('name')->get(),
                'questionTypes'   => collect($this->typeMetadata)->mapWithKeys(fn($v, $k) => [$k => $v['label']])->toArray(),
                'difficulties'    => $this->getDifficultyLevels(),
                'statuses'        => $this->getStatusLevels(),
                'typeDefinitions' => $this->typeMetadata
            ]
        ]);
    }

    /**
     * --------------------------------------------------------------------------
     * 2. LOGIQUE DE CRÉATION (STORE)
     * --------------------------------------------------------------------------
     */
    public function store(Request $request)
    {
        $validated = $this->performStrictValidation($request);

        DB::beginTransaction();
        try {
            // Traitement de l'image avec le disque 'media'
            if ($request->hasFile('image')) {
                $validated['image_path'] = $request->file('image')->store('questions/media', 'media');
            }

            // Normalisation de correct_answer pour vrai/faux (si le front envoie un booléen)
            $correctAnswer = $validated['correct_answer'] ?? null;
            if ($validated['type'] === self::TYPE_TRUE_FALSE) {
                $correctAnswer = filter_var($correctAnswer, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
            }

            $questionData = [
                'uuid'           => (string) Str::uuid(),
                'question_text'  => $validated['question_text'],
                'type'           => $validated['type'],
                'category_id'    => $validated['category_id'] ?? null,
                'points'         => $validated['points'],
                'difficulty'     => $validated['difficulty'],
                'status'         => $validated['status'],
                'explanation'    => $validated['explanation'] ?? '',
                'pairs'          => $validated['pairs'] ?? [], // Array natif
                'metadata'       => $validated['metadata'] ?? [], // Array natif
                'image_path'     => $validated['image_path'] ?? null,
                'created_by'     => Auth::id() ?? 1,
            ];

            if ($validated['type'] !== self::TYPE_MCQ) {
                $questionData['correct_answer'] = $correctAnswer;
            }

            $question = Question::create($questionData);

            // Gérer les options pour les QCM
            if ($validated['type'] === self::TYPE_MCQ && !empty($validated['options'])) {
                $correctOptionId = null;
                foreach ($validated['options'] as $optionData) {
                    $option = $question->options()->create([
                        'option_text' => $optionData['option_text'],
                        'is_correct'  => $optionData['is_correct'],
                    ]);
                    if ($optionData['is_correct']) {
                        $correctOptionId = $option->id;
                    }
                }
                // Pour un QCM à choix unique, on stocke l'ID de la bonne réponse
                if ($correctOptionId) {
                    $question->correct_answer = $correctOptionId;
                    $question->save();
                }
            }
            $summary = trim($request->input('change_summary', ''));
            if (empty($summary)) {
                $summary = 'Création initiale de l\'item.';
            }
            $this->createRevision($question, $summary);
            $this->logActivity('QUESTION_CREATED', $question);

            DB::commit();
            return back()->with('success', 'L\'item a été ajouté à la banque.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("SNC_QUESTION_STORE_ERR: " . $e->getMessage());
            return back()->with('error', 'Erreur critique lors de la création : ' . $e->getMessage())->withInput();
        }
    }

    /**
     * --------------------------------------------------------------------------
     * 3. LOGIQUE DE MISE À JOUR (UPDATE)
     * --------------------------------------------------------------------------
     */
    public function update(Request $request, Question $question)
    {
        $validated = $this->performStrictValidation($request);

        DB::beginTransaction();
        try {
            // 1. Récupération du texte (Gestion de 'text' vs 'question_text')
            $questionText = $validated['question_text'] ?? $request->input('text');

            // 2. Gestion de la réponse correcte (Normalisation True/False)

            $correctAnswer = $validated['correct_answer'] ?? null;
            if ($validated['type'] === self::TYPE_TRUE_FALSE) {
                if ($request->has('correct')) {
                    $correctAnswer = filter_var($request->input('correct'), FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
                } else {
                    $correctAnswer = filter_var($correctAnswer, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
                }
            }

            // 3. Gérer le remplacement d'image (disque 'media')
            if ($request->hasFile('image')) {
                if ($question->image_path) {
                    Storage::disk('media')->delete($question->image_path);
                }
                $validated['image_path'] = $request->file('image')->store('questions/media', 'media');
            }

            $questionData = [
                'question_text'  => $questionText,
                'type'           => $validated['type'],
                'category_id'    => $validated['category_id'] ?? $question->category_id,
                'points'         => $validated['points'],
                'difficulty'     => $validated['difficulty'],
                'status'         => $validated['status'],
                'explanation'    => $validated['explanation'] ?? '',
                'pairs'          => $validated['pairs'] ?? [],
                'metadata'       => $validated['metadata'] ?? [],
                'image_path'     => $validated['image_path'] ?? $question->image_path,
            ];

            if ($validated['type'] !== self::TYPE_MCQ) {
                $questionData['correct_answer'] = $correctAnswer;
            } else {
                $questionData['correct_answer'] = null; // Sera défini après la synchro des options
            }

            $question->update($questionData);

            // 4. Gérer la synchronisation des options pour les QCM
            if ($validated['type'] === self::TYPE_MCQ) {
                $incomingOptions = $validated['options'] ?? [];
                $incomingOptionIds = collect($incomingOptions)->pluck('id')->filter();

                // Supprimer les options qui ne sont plus présentes
                $question->options()->whereNotIn('id', $incomingOptionIds)->delete();

                $correctOptionId = null;
                foreach ($incomingOptions as $optionData) {
                    $option = $question->options()->updateOrCreate(
                        ['id' => $optionData['id'] ?? null],
                        ['option_text' => $optionData['option_text'], 'is_correct' => $optionData['is_correct']]
                    );
                    if ($optionData['is_correct']) {
                        $correctOptionId = $option->id;
                    }
                }
                $question->update(['correct_answer' => $correctOptionId]);
            } else {
                // Si le type de question a changé et n'est plus QCM, supprimer les anciennes options
                $question->options()->delete();
            }

            $summary = $request->input('change_summary');
            if (!is_string($summary) || trim($summary) === '') {
                $summary = "Mise à jour système (" . now()->format('d/m/Y H:i') . ")";
            }
            $this->createRevision($question, $summary);
            $this->logActivity('QUESTION_UPDATED', $question);

            DB::commit();
            return back()->with('success', 'L\'item a été mis à jour avec succès.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("SNC_QUESTION_UPDATE_ERR: " . $e->getMessage());
            return back()->with('error', 'Erreur critique lors de la mise à jour.');
        }
    }

    /**
     * --------------------------------------------------------------------------
     * 4. SUPPRESSION / ARCHIVAGE (DESTROY)
     * --------------------------------------------------------------------------
     */
    public function destroy(Question $question)
    {
        if ($question->attempts()->exists() || DB::table('exam_question')->where('question_id', $question->id)->exists()) {
            $question->update(['status' => self::STATUS_ARCHIVED]);
            $this->logActivity('QUESTION_ARCHIVED', $question);
            return back()->with('success', 'Cet item est utilisé. Il a été archivé pour préserver l\'historique.');
        }

        DB::beginTransaction();
        try {
            // Suppression physique de l'image (disque 'media')
            if ($question->image_path) {
                Storage::disk('media')->delete($question->image_path);
            }

            $question->options()->delete();
            $question->versions()->delete();
            $question->delete();

            $this->logActivity('QUESTION_DELETED', null);

            DB::commit();
            return back()->with('success', 'L\'item a été purgé définitivement.');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de la suppression.');
        }
    }

    /**
     * --------------------------------------------------------------------------
     * 5. DUPLICATION (CLONAGE)
     * --------------------------------------------------------------------------
     */
    public function duplicate(Question $question)
    {
        DB::beginTransaction();
        try {
            $clone = $question->replicate();
            $clone->question_text = $clone->question_text . ' (Copie)';
            $clone->uuid          = (string) Str::uuid();
            $clone->status        = self::STATUS_DRAFT;
            $clone->created_by    = Auth::id() ?? 1;
            $clone->created_at    = now();
            $clone->correct_answer = null; // Réinitialiser, sera défini après le clonage des options
            $clone->save();

            // Dupliquer les options si c'est un QCM
            if ($question->type === self::TYPE_MCQ && $question->options()->exists()) {
                $originalCorrectAnswerId = $question->correct_answer;
                $newCorrectAnswerId = null;

                foreach ($question->options as $option) {
                    $newOption = $option->replicate();
                    $newOption->question_id = $clone->id;
                    $newOption->save();

                    if ($option->id == $originalCorrectAnswerId) {
                        $newCorrectAnswerId = $option->id;
                    }
                }

                if ($newCorrectAnswerId) {
                    $clone->update(['correct_answer' => $newCorrectAnswerId]);
                }
            }

            // Copie de l'image physique sur le disque 'media'
            if ($question->image_path && Storage::disk('media')->exists($question->image_path)) {
                $ext = pathinfo($question->image_path, PATHINFO_EXTENSION);
                $newPath = 'questions/media/clone_' . Str::random(10) . '.' . $ext;
                Storage::disk('media')->copy($question->image_path, $newPath);
                $clone->update(['image_path' => $newPath]);
            }

            $this->logActivity('QUESTION_DUPLICATED', $clone);

            DB::commit();
            return back()->with('success', 'L\'item a été dupliqué avec succès.');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de la duplication.');
        }
    }

    /**
     * --------------------------------------------------------------------------
     * 6. VALIDATION ET OUTILS PRIVÉS
     * --------------------------------------------------------------------------
     */

    private function performStrictValidation(Request $request): array
    {
        $rules = [
            'question_text'  => 'required|string|min:2',
            'type'           => ['required', Rule::in(array_keys($this->typeMetadata))],
            'category_id'    => 'nullable|exists:categories,id',
            'points'         => 'required|numeric|min:0.1',
            'difficulty'     => ['required', Rule::in(array_keys($this->getDifficultyLevels()))],
            'status'         => ['required', Rule::in(array_keys($this->getStatusLevels()))],
            'explanation'    => 'nullable|string',
            'options'        => 'nullable|array',
            'pairs'          => 'nullable|array',
            'metadata'       => 'nullable|array',
            'image'          => 'nullable|image|max:2048',
        ];

        // Validation conditionnelle basée sur le type
        if ($request->type === self::TYPE_MCQ) {
            $rules['options'] = 'required|array|min:2';
            $rules['options.*.option_text'] = 'required|string';
            $rules['options.*.is_correct'] = 'required|boolean';
        }

        if ($request->type === self::TYPE_TRUE_FALSE) {
            // Tolère "true", "false", 1, 0, true, false
            $rules['correct_answer'] = 'required';
        } else {
            $rules['correct_answer'] = 'nullable|string';
        }

        if ($request->type === self::TYPE_MATCHING) {
            $rules['pairs'] = 'required|array|min:2';
            $rules['pairs.*.key'] = 'required|string';
            $rules['pairs.*.value'] = 'required|string';
        }

        return Validator::make($request->all(), $rules)->validate();
    }

    private function createRevision(Question $question, ?string $summary = null)
    {
        if (empty($summary)) {
            $summary = 'Version sans commentaire spécifié';
        }

        $versionNumber = $question->versions()->count() + 1;
        // Charger les options pour qu'elles soient incluses dans le snapshot JSON
        $question->load('options');

        QuestionVersion::create([
            'question_id'    => $question->id,
            'version_number' => $versionNumber,
            'data_snapshot'  => $question->toArray(),
            'change_summary' => $summary,
            'author_id'      => Auth::id() ?? 1,
            'created_at'     => now()
        ]);

        if ($versionNumber > 10) {
            $question->versions()->orderBy('created_at')->first()->delete();
        }
    }

    private function logActivity(string $event, $model)
    {
        AuditLog::create([
            'user_id'        => Auth::id() ?? 1,
            'event'          => $event,
            'auditable_type' => $model ? get_class($model) : 'Question',
            'auditable_id'   => $model ? $model->id : 0,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent()
        ]);
    }

    public function audit(Question $question)
    {
        $versions = $question->versions()->with('author:id,name')->latest()->get()->map(function($v) {
            return [
                'status' => 'Version v' . $v->version_number . ' : ' . ($v->change_summary ?: 'Modification sans commentaire'),
                'date' => $v->created_at->diffForHumans(),
                'icon' => 'pi pi-history',
                'color' => '#6366F1',
                'user' => $v->author->name ?? 'Système'
            ];
        });

        $logs = AuditLog::where('auditable_type', Question::class)
            ->where('auditable_id', $question->id)
            ->with('user:id,name')
            ->latest()
            ->get()
            ->map(function($l) {
                return [
                    'status' => 'Action Système : ' . $l->event,
                    'date' => $l->created_at->diffForHumans(),
                    'icon' => 'pi pi-info-circle',
                    'color' => '#94a3b8',
                    'user' => $l->user->name ?? 'Système'
                ];
            });

        return response()->json($versions->concat($logs)->sortByDesc('date')->values());
    }

    private function getDifficultyLevels(): array
    {
        return [
            self::DIFF_BEGINNER     => 'Débutant',
            self::DIFF_INTERMEDIATE => 'Intermédiaire',
            self::DIFF_ADVANCED     => 'Avancé',
            self::DIFF_EXPERT       => 'Expert'
        ];
    }

    private function getStatusLevels(): array
    {
        return [
            self::STATUS_DRAFT     => 'Brouillon',
            self::STATUS_PUBLISHED => 'Publié (Actif)',
            self::STATUS_REVIEW    => 'À réviser',
            self::STATUS_ARCHIVED  => 'Archivé'
        ];
    }
}
