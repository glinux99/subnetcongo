<?php

namespace App\Events;

use App\Models\LiveSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LiveSessionReminder
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public LiveSession $liveSession
    ) {}
}
