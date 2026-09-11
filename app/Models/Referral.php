<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model {
    protected $fillable = [
        'affiliate_id',
        'user_id',
        'course_id',
        'order_id',
        'amount',
        'commission',
        'status',
        'clicked_at',
        'converted_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'commission' => 'decimal:2',
        'clicked_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function affiliate(): BelongsTo { return $this->belongsTo(Affiliate::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}