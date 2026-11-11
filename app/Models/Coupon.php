<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    /** Route URL sans ID (lien partageable) */
    public function getRouteKeyName()
    {
        return 'code';
    }

    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'min_amount',
        'usage_limit',
        'max_uses_per_user',
        'used_count',
        'status',
        'is_active',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'discount_value'    => 'decimal:2',
        'min_amount'        => 'decimal:2',
        'usage_limit'       => 'integer',
        'used_count'        => 'integer',
        'max_uses_per_user' => 'integer',
        'is_active'         => 'boolean',
        'starts_at'         => 'datetime',
        'expires_at'        => 'datetime',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->where(function ($w) {
            $w->where('status', 'active');
        })->where(function ($w) {
            $w->whereNull('starts_at')->orWhere('starts_at', '<=', now());
        })->where(function ($w) {
            $w->whereNull('expires_at')->orWhere('expires_at', '>=', now());
        });
    }

    public function scopeUsable($q)
    {
        return $this->scopeActive($q)
            ->where(function ($w) {
                $w->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit');
            });
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isValidForAmount(float $amount): bool
    {
        if (! $this->is_active || $this->status !== 'active') return false;
        if ($this->min_amount !== null && $amount < (float) $this->min_amount) return false;
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->isExpired()) return false;
        return true;
    }

    public function computeDiscount(float $amount): float
    {
        if (! $this->isValidForAmount($amount)) return 0.0;

        $value = (float) $this->discount_value;

        return match ($this->discount_type) {
            'percent' => round($amount * min($value, 100) / 100, 2),
            'fixed'   => min($value, $amount),
            default   => 0.0,
        };
    }
}
