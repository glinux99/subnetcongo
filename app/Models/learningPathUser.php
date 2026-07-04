<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class learningPathUser extends Model
{
    use HasFactory;

    protected $table = 'learning_path_user';

    protected $fillable = [
        'uuid',
        'learning_path_id',
        'user_id',
        'progress_percent',
        'enrolled_at',
        'completed_at',
        'status',
        'price_paid',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
        'price_paid' => 'decimal:2',
        'progress_percent' => 'integer',
    ];

    /**
     * Get the learning path associated with this enrollment.
     */
    public function learningPath(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class);
    }

    /**
     * Get the user associated with this enrollment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
