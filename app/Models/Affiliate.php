<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model {
    protected $fillable =['user_id', 'referral_code', 'commission_rate', 'total_earnings', 'unpaid_earnings'];

    public function user() { return $this->belongsTo(User::class); }
}
