<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'student_id',
        'file_path',
        'file_name',
        'student_comment',
        'score',
        'feedback',
        'is_graded',
        'submitted_at',
        'graded_at',
    ];

    protected $casts = [
        'is_graded'    => 'boolean',
        'score'        => 'decimal:2',
        'submitted_at' => 'datetime',
        'graded_at'    => 'datetime',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
