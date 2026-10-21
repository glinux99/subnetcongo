<?php

namespace App\Models\Elearning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'exam_id',
        'course_id',
        'exam_attempt_id',
        'total_points',
        'score_percentage',
        'is_passed',
        'passed_at',
        'feedback',
        'graded_by',
        'grade_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_passed' => 'boolean',
        'score_percentage' => 'decimal:2',
        'total_points' => 'decimal:2',
        'passed_at' => 'datetime',
    ];

    /**
     * Get the user for this result.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the exam for this result.
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the course for this result.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the attempt for this result.
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    /**
     * Get the grader (if manually graded).
     */
    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /**
     * Scope to get results for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get results for a specific exam.
     */
    public function scopeForExam($query, $examId)
    {
        return $query->where('exam_id', $examId);
    }

    /**
     * Scope to get passed results.
     */
    public function scopePassed($query)
    {
        return $query->where('is_passed', true);
    }

    /**
     * Scope to get failed results.
     */
    public function scopeFailed($query)
    {
        return $query->where('is_passed', false);
    }

    /**
     * Scope to get best results per user per exam.
     */
    public function scopeBestResults($query)
    {
        return $query->selectRaw('*, ROW_NUMBER() OVER (PARTITION BY user_id, exam_id ORDER BY score_percentage DESC) as rn')
            ->fromSub(function ($q) {
                return $q->from('exam_results');
            }, 'er')
            ->where('rn', 1);
    }

    /**
     * Check if result is passed.
     */
    public function isPassed(): bool
    {
        return $this->is_passed;
    }

    /**
     * Get formatted score percentage.
     */
    public function getFormattedScoreAttribute(): string
    {
        return number_format($this->score_percentage, 1) . '%';
    }

    /**
     * Get grade letter based on percentage.
     */
    public function getGradeLetterAttribute(): string
    {
        $percentage = $this->score_percentage;

        if ($percentage >= 95) return 'A+';
        if ($percentage >= 90) return 'A';
        if ($percentage >= 85) return 'B+';
        if ($percentage >= 80) return 'B';
        if ($percentage >= 75) return 'C+';
        if ($percentage >= 70) return 'C';
        if ($percentage >= 65) return 'D+';
        if ($percentage >= 60) return 'D';
        return 'F';
    }

    /**
     * Check if result is passing based on exam threshold.
     */
    public function meetsPassingThreshold(): bool
    {
        return $this->exam && $this->score_percentage >= $this->exam->pass_percentage;
    }

    /**
     * Get points earned.
     */
    public function getPointsEarnedAttribute(): float
    {
        if (!$this->exam) {
            return 0;
        }

        $totalPoints = $this->exam->total_points ?? 0;
        return ($this->score_percentage / 100) * $totalPoints;
    }
}
