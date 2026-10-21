<?php

namespace App\Models\Elearning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\User;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Question types.
     * Question types.
     */
    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    public const TYPE_TRUE_FALSE = 'true_false';
    public const TYPE_SHORT_ANSWER = 'short_answer';
    public const TYPE_MATCHING = 'matching';
    public const TYPE_ESSAY = 'essay';

    /**
     * Difficulty levels.
     */
    public const DIFFICULTY_EASY = 'easy';
    public const DIFFICULTY_MEDIUM = 'medium';
    public const DIFFICULTY_HARD = 'hard';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'exam_id',
        'category_id',
        'created_by',
        'uuid',
        'question_text',
        'type',
        'image_path',
        'correct_answer',
        'explanation',
        'points',
        'difficulty',
        'status',
        'metadata',
        'is_bank',
        'parent_id',
        'order',
        'success_rate',
    ];
    protected $appends = ['correct'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'metadata' => 'array',
        'points' => 'decimal:2',
        'success_rate' => 'decimal:2',
        'is_bank' => 'boolean',
        'order' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($question) {
            if (empty($question->uuid)) {
                $question->uuid = (string) Str::uuid();
            }

            // if (isset($question->options) && is_array($question->options)) {
            //     $question->options = json_encode($question->options);
            // }
        });
    }

    /**
     * Get the options for multiple choice questions.
     */
    // public function options(): HasMany
    // {
    //     return $this->hasMany(QuestionOption::class);
    // }

    /**
     * Get the exam that owns the question.
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the versions of this question.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(QuestionVersion::class);
    }

    /**
     * Get the options for multiple choice questions (QuestionOption model).
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class, 'question_id');
    }

    /**
     * Get the attempts for this question.
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /**
     * Get the category for the question.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the creator of the question.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the tags for the question.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Get the parent question (for variants).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'parent_id');
    }

    /**
     * Get child questions (variants).
     */
    public function children(): HasMany
    {
        return $this->hasMany(Question::class, 'parent_id');
    }

    /**
     * Scope to get questions for a specific exam.
     */
    public function scopeForExam($query, $examId)
    {
        return $query->where('exam_id', $examId)->orderBy('order');
    }

    /**
     * Scope to get questions from the question bank.
     */
    public function scopeBank($query)
    {
        return $query->where('is_bank', true);
    }

    /**
     * Scope to get questions by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get questions by difficulty.
     */
    public function scopeOfDifficulty($query, $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    /**
     * Scope to get published questions.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Check if question is multiple choice.
     */
    public function isMultipleChoice(): bool
    {
        return $this->type === self::TYPE_MULTIPLE_CHOICE;
    }

    /**
     * Check if question is true/false.
     */
    public function isTrueFalse(): bool
    {
        return $this->type === self::TYPE_TRUE_FALSE;
    }

    /**
     * Check if question is short answer.
     */
    public function isShortAnswer(): bool
    {
        return $this->type === self::TYPE_SHORT_ANSWER;
    }

    /**
     * Check if question is essay type.
     */
    public function isEssay(): bool
    {
        return $this->type === self::TYPE_ESSAY;
    }

    /**
     * Check if question is matching type.
     */
    public function isMatching(): bool
    {
        return $this->type === self::TYPE_MATCHING;
    }

    /**
     * Get the correct answer(s).
     */
    public function getCorrectAnswersAttribute()
    {
        if ($this->isMultipleChoice()) {
            return $this->options()->where('is_correct', true)->pluck('option_text')->toArray();
        }

        return $this->correct_answer ? [$this->correct_answer] : [];
    }

    /**
     * Alias pour correct_answer utilisé par le frontend.
     */
    public function getCorrectAttribute()
    {
        if ($this->type === self::TYPE_TRUE_FALSE) {
            return filter_var($this->correct_answer, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) $this->correct_answer;
    }

    /**
     * Calculate success rate based on attempts.
     */
    public function calculateSuccessRate()
    {
        $totalAttempts = $this->attempts()->count();
        if ($totalAttempts === 0) {
            return 0;
        }

        $correctAttempts = $this->attempts()->where('is_correct', true)->count();
        return round(($correctAttempts / $totalAttempts) * 100, 2);
    }

    /**
     * Get average points earned for this question.
     */
    public function getAveragePointsAttribute()
    {
        return $this->attempts()->avg('points_earned') ?? 0;
    }

    /**
     * Check if question has an image.
     */
    public function hasImage(): bool
    {
        return !empty($this->image_path);
    }
//     public function exam()
// {
//     return $this->belongsTo(Exam::class);
// }

// public function options()
// {
//     return $this->hasMany(QuestionOption::class)->orderBy('order');
// }
}
