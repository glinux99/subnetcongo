<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TerminalSession extends Model
{
    protected $fillable = [
        'user_id',
        'command',
        'full_command',
        'title',
        'status',
        'exit_code',
        'process_id',
        'log_path',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];
}
