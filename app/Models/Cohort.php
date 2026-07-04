<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cohort extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'instructor_id',
        'start_date',
        'end_date',
        'max_students',
        'is_private',
        'auto_enrollment'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_private' => 'boolean',
        'auto_enrollment' => 'boolean',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
    public function assistants()
{
    // Remplacez 'cohort_assistant' par le vrai nom de votre table pivot si elle existe
    return $this->belongsToMany(User::class, 'cohort_assistant', 'cohort_id', 'user_id');
}
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'cohort_course')
            ->withPivot('order', 'is_mandatory')
            ->withTimestamps();
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'cohort_user', 'cohort_id', 'user_id')
            ->withTimestamps();
    }


public function documents() { return $this->hasMany(Document::class); }
public function liveSessions() { return $this->hasMany(LiveSession::class); }
public function prerequisites() { return $this->belongsToMany(Course::class, 'cohort_prerequisites'); }
public function auditLogs() { return $this->morphMany(AuditLog::class, 'auditable'); }
}
