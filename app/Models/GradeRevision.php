<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'student_reason',
        'status',
        'original_grade',
        'original_instructor_id',
        'arbitrator_id',
        'arbitrator_feedback',
        'new_grade',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function originalInstructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'original_instructor_id');
    }

    public function arbitrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'arbitrator_id');
    }
}
