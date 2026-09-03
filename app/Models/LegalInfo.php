<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalInfo extends Model
{
    use HasFactory;

    protected $table = 'legal_info';

    protected $fillable = [
        'company_name', 'legal_form', 'rccm', 'ifu', 'tax_id', 'nif', 'ai',
        'country', 'province', 'city', 'address_line', 'po_box',
        'website', 'capital_social', 'logo_url',
    ];
}
