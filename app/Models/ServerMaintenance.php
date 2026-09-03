<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerMaintenance extends Model
{
    use HasFactory;

    protected $table = 'server_maintenances';

    protected $fillable = [
        'title',
        'description',
        'type',          // security, performance, update, backup, database
        'severity',      // low, medium, high, critical
        'status',        // pending, running, completed, failed, cancelled
        'scheduled_at',
        'started_at',
        'completed_at',
        'duration_minutes',
        'affected_services',
        'downtime_estimate',
        'notes',
        'created_by',
        'executed_by',
    ];

    protected $casts = [
        'scheduled_at'   => 'datetime',
        'started_at'     => 'datetime',
        'completed_at'   => 'datetime',
        'affected_services' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'low'      => 'info',
            'medium'   => 'warning',
            'high'     => 'orange',
            'critical' => 'danger',
            default    => 'secondary',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'warning',
            'running'   => 'info',
            'completed' => 'success',
            'failed'    => 'danger',
            'cancelled' => 'secondary',
            default     => 'secondary',
        };
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'pending')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at', 'asc');
    }
}
