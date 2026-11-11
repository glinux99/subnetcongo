<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailAudienceGroup extends Model
{
    protected $fillable = ['name', 'description', 'created_by'];

    public function members(): HasMany
    {
        return $this->hasMany(EmailAudienceGroupMember::class, 'group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(EmailCampaign::class, 'audience_group_id');
    }
}
