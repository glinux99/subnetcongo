<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'price',
        'currency',
        'interval',
        'duration_months',
        'features',
        'course_ids',
        'is_active',
    ];

    protected $casts = [
        'price' => 'integer',
        'duration_months' => 'integer',
        'features' => 'array',
        'course_ids' => 'array',
        'is_active' => 'boolean',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_code', 'code');
    }

    /** Prix normalisé en cents par mois (pour le MRR). */
    public function monthlyPriceCents(): int
    {
        return $this->interval === 'year'
            ? intdiv($this->price, 12)
            : $this->price;
    }
}
