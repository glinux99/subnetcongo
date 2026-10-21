<?php

namespace App\Models\Elearning;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningPath extends Model {
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'thumbnail',
        'badge_url',
        'status',
        'price',
        'category_id',
        'level',
        'created_by',
        'settings',
        'total_duration_minutes',
        'courses_count_cache',
        'seo_metadata'
    ];

    protected $casts = [
        'settings' => 'array',
         'seo_metadata' => 'array',
        'price' => 'decimal:2',
    ];

    public function courses(): BelongsToMany {
        return $this->belongsToMany(Course::class, 'learning_path_course')->withPivot('order', 'is_mandatory');
    }

    public function users(): BelongsToMany {
        return $this->belongsToMany(User::class, 'learning_path_user')->withPivot('progress_percent', 'enrolled_at', 'status', 'price_paid', 'uuid')->withTimestamps();
    }

    public function enrollments(): HasMany {
        return $this->hasMany(Enrollment::class, 'learning_path_id');
    }

    /**
     * Get the category that owns the learning path.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user who created the learning path.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cohorts(): BelongsToMany {
        return $this->belongsToMany(Cohort::class, 'cohort_learning_path');
    }
}
