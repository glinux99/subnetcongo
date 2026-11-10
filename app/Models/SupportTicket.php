<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    /** Route URL sans ID (lien partageable) */
    public function getRouteKeyName()
    {
        return 'ticket_number';
    }
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

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'support_ticket_id')->latest();
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
            'resolved'        => 'RÃ©solu',
            'closed'          => 'FermÃ©',
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
