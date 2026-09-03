<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number', 'name', 'email', 'phone', 'subject', 'message',
        'status', 'ip_address', 'user_agent', 'source',
        'replied_by', 'replied_at', 'reply_message',
    ];

    protected $casts = [
        'replied_at' => 'datetime',
    ];

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'new'      => 'rose',
            'read'     => 'amber',
            'replied'  => 'emerald',
            'archived' => 'secondary',
            'spam'     => 'secondary',
            default    => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'new'      => 'Nouveau',
            'read'     => 'Lu',
            'replied'  => 'Répondu',
            'archived' => 'Archivé',
            'spam'     => 'Spam',
            default    => ucfirst((string) $this->status),
        };
    }
}
