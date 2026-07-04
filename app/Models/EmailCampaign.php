<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EmailCampaign extends Model {
    protected $fillable = ['subject', 'body', 'target_audience', 'status', 'sent_at'];
}
