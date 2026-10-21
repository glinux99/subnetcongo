<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Affiliate extends Model {
    protected $fillable = [
        'user_id', 
        'referral_code', 
        'commission_rate', 
        'total_earnings', 
        'unpaid_earnings',
        'clicks',
        'conversions',
        'status',
        'payment_email',
        'tier',
        'notes'
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'unpaid_earnings' => 'decimal:2',
        'clicks' => 'integer',
        'conversions' => 'integer',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    
    public function referrals(): HasMany { return $this->hasMany(Referral::class); }
    
    public function payouts(): HasMany { return $this->hasMany(AffiliatePayout::class); }

    public function getBalanceAttribute(): float {
        return (float) $this->unpaid_earnings;
    }

    public function getConversionRateAttribute(): float {
        if ($this->clicks === 0) return 0;
        return round(($this->conversions / $this->clicks) * 100, 2);
    }

    public function getTierLabelAttribute(): string {
        return match($this->tier) {
            'bronze' => 'Bronze (10%)',
            'silver' => 'Silver (15%)',
            'gold' => 'Gold (25%)',
            'platinum' => 'Platinum (40%)',
            default => 'Bronze (10%)'
        };
    }
}
