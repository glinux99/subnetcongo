<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'nomenclature',
        'description',
        'region_id',
        'number',
    ];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}