<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForumTopic extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'forum_category_id',
        'course_id',
        'title',
        'slug',
        'content',
        'status',
        'attachment_path',
        'attachment_name',
        'is_pinned',
        'is_locked',
        'is_resolved',
        'views_count',
        'replies_count'
    ];

    /**
     * Incrémente le compteur de vues de manière sécurisée.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Scope pour les sujets actifs uniquement.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Relation avec le cours (si le sujet est lié à un cours spécifique).
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'forum_category_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'forum_topic_id');
    }

    public function upvotes()
    {
        return $this->belongsToMany(User::class, 'forum_upvotes', 'forum_topic_id', 'user_id');
    }

    public function upvoters()
    {
        return $this->belongsToMany(User::class, 'forum_upvotes', 'forum_topic_id', 'user_id');
    }
}
