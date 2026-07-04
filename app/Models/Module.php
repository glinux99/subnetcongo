<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'course_id',
        'uuid',
        'slug',
        'title',
        'description',
        'status',
        'order',
        'is_locked',
        'estimated_duration',
        'thumbnail_path',
        'seo_metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'order' => 'integer',
        'is_locked' => 'boolean',
        'estimated_duration' => 'integer',
        'seo_metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the course that owns the module.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the lessons for the module.
     */
    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    /**
     * Scope to get modules for a specific course in order.
     */
    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId)->orderBy('order');
    }

    /**
     * Scope to get published modules.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Calculate total duration of the module including all lessons.
     */
    public function getTotalDurationAttribute()
    {
        return $this->duration_minutes + $this->lessons->sum('duration_minutes');
    }

    /**
     * Get progress for a specific user.
     */
    public function getUserProgress(User $user)
    {
        return $this->lessons->reduce(function ($carry, $lesson) use ($user) {
            $progress = $lesson->userProgress()->where('user_id', $user->id)->first();
            return $carry + ($progress ? $progress->progress_percent : 0);
        }, 0) / max($this->lessons->count(), 1);
    }
}
