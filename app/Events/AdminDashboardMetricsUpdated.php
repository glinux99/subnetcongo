<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class AdminDashboardMetricsUpdated implements ShouldBroadcastNow
{
    public function __construct(public readonly array $infra)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.dashboard.metrics')];
    }

    public function broadcastAs(): string
    {
        return 'admin-dashboard.metrics-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'infra' => $this->infra,
            'server_time' => now()->toIso8601String(),
        ];
    }
}
