<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

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
        return $q->where(function ($w) {
            $w->where('is_active', true)->orWhere('status', 'active');
        })->where(function ($w) {
            $w->whereNull('starts_at')->orWhere('starts_at', '<=', now());
        })->where(function ($w) {
            $w->whereNull('expires_at')->orWhere('expires_at', '>=', now());
        });
    }

    public function scopeUsable($q)
    {
        return $this->scopeActive($q);
    }

    public function isValidForAmount(float $amount): bool
    {
        if ($this->min_amount && $amount < (float) $this->min_amount) return false;
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        return true;
    }

    public function computeDiscount(float $amount): float
    {
        if (! $this->isValidForAmount($amount)) return 0.0;
        $value = (float) $this->discount_value;
        return $this->discount_type === 'percent'
            ? round($amount * ($value / 100), 2)
            : min($value, $amount);
    }
}
