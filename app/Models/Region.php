<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'designation',
        'code',
        'type_centrale',
        'puissance_centrale',
        'description',
        'status',
    ];

    protected $casts = [
        'puissance_centrale' => 'integer',
    ];
}