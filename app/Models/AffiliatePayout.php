<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliatePayout extends Model {
    protected $fillable = [
        'affiliate_id',
        'amount',
        'status',
        'payout_date',
        'method',
        'transaction_id',
        'notes',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payout_date' => 'date',
    ];

    public function affiliate(): BelongsTo { return $this->belongsTo(Affiliate::class); }
    public function processor(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
}