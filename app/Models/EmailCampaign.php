<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'from_name',
        'from_email',
        'subject',
        'body',
        'target_audience',
        'audience_group_id',
        'extra_emails',
        'excluded_emails',
        'status',
        'user_id',
        'total_recipients',
        'sent_count',
        'opens_count',
        'clicks_count',
        'bounces_count',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'total_recipients' => 'integer',
        'sent_count' => 'integer',
        'opens_count' => 'integer',
        'clicks_count' => 'integer',
        'bounces_count' => 'integer',
        'extra_emails' => 'array',
        'excluded_emails' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function audienceGroup(): BelongsTo
    {
        return $this->belongsTo(EmailAudienceGroup::class, 'audience_group_id');
    }

    /**
     * Liste normalisée des destinataires :
     * base (rôle ou groupe) + emails supplémentaires − emails exclus.
     * Retourne une collection d'objets {name, email}.
     */
    public function recipients()
    {
        // Base : utilisateurs par rôle
        $base = match ($this->target_audience) {
            'students' => User::role('student')->whereNotNull('email')->where('email', '!=', ''),
            'instructors' => User::role('instructor')->whereNotNull('email')->where('email', '!=', ''),
            'group' => null,
            default => User::whereNotNull('email')->where('email', '!=', ''),
        };

        $recipients = collect();

        if ($this->target_audience === 'group' && $this->audienceGroup) {
            $recipients = $this->audienceGroup->members
                ->map(fn ($m) => (object) ['name' => $m->name ?: $m->email, 'email' => $m->email]);
        } elseif ($base) {
            $recipients = $base->get(['name', 'email'])
                ->map(fn ($u) => (object) ['name' => $u->name, 'email' => $u->email]);
        }

        // Emails supplémentaires (hors base)
        foreach ((array) $this->extra_emails as $email) {
            $email = strtolower(trim((string) $email));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $recipients->push((object) ['name' => $email, 'email' => $email]);
            }
        }

        // Emails exclus
        $excluded = collect((array) $this->excluded_emails)
            ->map(fn ($e) => strtolower(trim((string) $e)));

        return $recipients
            ->reject(fn ($r) => $excluded->contains(strtolower($r->email)))
            ->unique(fn ($r) => strtolower($r->email))
            ->values();
    }
}
