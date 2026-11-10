<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GreetingMessage extends Model
{
    protected $fillable = [
        'audience',
        'period',
        'message',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const AUDIENCE_ALL = 'all';
    public const AUDIENCE_STUDENT = 'student';
    public const AUDIENCE_INSTRUCTOR = 'instructor';
    public const AUDIENCE_ADMIN = 'admin';
    public const AUDIENCE_STAFF = 'staff';

    public const PERIOD_ALL = 'all';
    public const PERIOD_MORNING = 'morning';
    public const PERIOD_AFTERNOON = 'afternoon';
    public const PERIOD_EVENING = 'evening';
}
