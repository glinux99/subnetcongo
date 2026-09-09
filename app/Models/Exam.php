<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Exam extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Exam types.
     */
    public const TYPE_QUIZ = 'Quiz';
    public const TYPE_CERTIFICATION = 'Certification';
    public const TYPE_PLACEMENT = 'Placement';

    /**
     * Exam statuses.
     */
    public const STATUS_DRAFT = 'Draft';
    public const STATUS_ACTIVE = 'Active';
    public const STATUS_ARCHIVED = 'Archived';
    public const STATUS_SUSPENDED = 'Suspended';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'course_id',
        'module_id',
        'uuid',
        'category_id',
        'created_by',
        'title',
        'description',
        'type',
        'status',
        'pass_percentage',
        'duration_minutes',
        'attempts_allowed',
        'settings',
        'is_published',
        'randomize_questions',
        'randomize_options',
        'show_correct_answers',
        'show_results_immediately',
        'require_lockdown_browser',
        'password',
        'available_from',
        'available_until',
        'time_limit_minutes',
        'is_module_quiz',
        'is_final_exam',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'settings' => 'array',
        'is_published' => 'boolean',
        'randomize_questions' => 'boolean',
        'randomize_options' => 'boolean',
        'show_correct_answers' => 'boolean',
        'show_results_immediately' => 'boolean',
        'require_lockdown_browser' => 'boolean',
        'pass_percentage' => 'decimal:2',
        'duration_minutes' => 'integer',
        'time_limit_minutes' => 'integer',
        'attempts_allowed' => 'integer',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'deleted_at' => 'datetime',
        'is_module_quiz' => 'boolean',
        'is_final_exam' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($exam) {
            if (empty($exam->uuid)) {
                $exam->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the course that owns the exam.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the category for the exam.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the creator of the exam.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the questions for the exam.
     */
    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    /**
     * Get all options for all questions in this exam.
     */
    public function options()
    {
        return $this->hasManyThrough(QuestionOption::class, Question::class);
    }

    /**
     * Get the attempts for the exam.
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /**
     * Get the results for the exam.
     */
    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    /**
     * Scope to get exams for a specific course.
     */
    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * Scope to get published exams.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get active exams.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get exams by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get available exams (within date range).
     */
    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('available_from')
              ->orWhere('available_from', '<=', now());
        })->where(function ($q) {
            $q->whereNull('available_until')
              ->orWhere('available_until', '>=', now());
        });
    }

    /**
     * Check if exam is currently available.
     */
    public function isAvailable(): bool
    {
        if ($this->available_from && $this->available_from->isFuture()) {
            return false;
        }
        if ($this->available_until && $this->available_until->isPast()) {
            return false;
        }
        return true;
    }

    /**
     * Check if exam is a quiz.
     */
    public function isQuiz(): bool
    {
        return $this->type === self::TYPE_QUIZ;
    }

    /**
     * Check if exam is a certification.
     */
    public function isCertification(): bool
    {
        return $this->type === self::TYPE_CERTIFICATION;
    }

    /**
     * Get total points for the exam.
     */
    public function getTotalPointsAttribute()
    {
        return $this->questions->sum('points');
    }

    /**
     * Get average score across all attempts.
     */
    public function getAverageScoreAttribute()
    {
        return $this->results()->avg('score_percentage') ?? 0;
    }

    /**
     * Get pass rate percentage.
     */
    public function getPassRateAttribute()
    {
        $total = $this->results()->count();
        if ($total === 0) {
            return 0;
        }
        $passed = $this->results()->where('is_passed', true)->count();
        return round(($passed / $total) * 100, 1);
    }

    /**
     * Get attempts remaining for a user.
     */
    public function getAttemptsRemainingFor(User $user): int
    {
        if ($this->attempts_allowed === 0) {
            return -1; // Unlimited
        }

        $attemptsUsed = $this->attempts()->where('user_id', $user->id)->count();
        return max(0, $this->attempts_allowed - $attemptsUsed);
    }

    /**
     * Check if user has remaining attempts.
     */
    public function userHasAttemptsRemaining(User $user): bool
    {
        $remaining = $this->getAttemptsRemainingFor($user);
        return $remaining > 0 || $remaining === -1;
    }

    /**
     * Get user's best result.
     */
    public function getBestResultFor(User $user)
    {
        return $this->results()
            ->where('user_id', $user->id)
            ->orderBy('score_percentage', 'desc')
            ->first();
    }

    /**
     * Check if user passed the exam.
     */
    public function userPassed(User $user): bool
    {
        $bestResult = $this->getBestResultFor($user);
        return $bestResult && (float) $bestResult->score_percentage >= (float) $this->pass_percentage;
    }
}
