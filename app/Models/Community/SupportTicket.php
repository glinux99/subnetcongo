<?php

namespace App\Models\Community;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'support_tickets';

    protected $fillable = [
        'ticket_number',
        'user_id',
        'ticket_category_id',
        'assigned_to_user_id',
        'subject',
        'description',
        'message',
        'status',
        'priority',
        'category',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    public function scopeOpen($q)     { return $q->whereIn('status', ['open', 'new']); }
    public function scopeClosed($q)   { return $q->whereIn('status', ['closed', 'resolved']); }
    public function scopePending($q)  { return $q->where('status', 'pending'); }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'open', 'new'     => 'Ouvert',
            'pending'         => 'En attente',
            'in_progress'     => 'En cours',
            'resolved'        => 'Résolu',
            'closed'          => 'Fermé',
            default           => ucfirst((string) $this->status),
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'rose',
            'high'   => 'orange',
            'medium' => 'amber',
            'low'    => 'emerald',
            default  => 'slate',
        };
    }
}
