<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'is_graded',
        'course_id',
        'assignment_id',
        'status',
        'content',
        'file_path',
        'submitted_at',
        'grade',
        'feedback_text',
        'rubric_results',
        'graded_at',
        'graded_by',
        'grading_duration',
        'internal_notes',
        'plagiarism_verified',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'rubric_results' => 'array',
        'plagiarism_verified' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(GradeRevision::class);
    }

    public function previousAttempts(): HasMany
    {
        return $this->hasMany(Submission::class, 'assignment_id', 'assignment_id')
            ->where('user_id', $this->user_id)
            ->where('id', '!=', $this->id);
    }
}
