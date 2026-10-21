<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = ['session_id', 'user_id', 'course_id', 'price', 'coupon_code', 'discount_amount'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
