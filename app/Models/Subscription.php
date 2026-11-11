<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'plan_code',
        'plan_name',
        'plan_price',
        'currency',
        'interval',
        'quantity',
        'stripe_id',
        'stripe_status',
        'stripe_price',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'status',
        'total_paid',
    ];

    protected $casts = [
        'plan_price' => 'integer',
        'total_paid' => 'integer',
        'quantity' => 'integer',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Relation via le code (photographie du plan au moment de la souscription). */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_code', 'code');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /** Prix mensuel normalisé en cents (abonnements annuels -> /12). */
    public function monthlyPriceCents(): int
    {
        $price = (int) ($this->plan_price ?? 0);

        return $this->interval === 'year' ? intdiv($price, 12) : $price;
    }
}
