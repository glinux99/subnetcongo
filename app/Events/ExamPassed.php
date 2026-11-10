<?php

namespace App\Events;

use App\Models\ExamAttempt;
use App\Models\ExamResult;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExamPassed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ExamResult $result,
        public ExamAttempt $attempt,
    ) {}
}
