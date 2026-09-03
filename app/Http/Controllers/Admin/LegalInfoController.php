<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class LegalInfoController extends Controller
{
    public function index()
    {
        $legal = LegalInfo::first() ?? new LegalInfo([
            'company_name' => 'SubnetCongo',
        ]);
        return Inertia::render('Admin/LegalInfo/Edit', [
            'legal' => $legal,
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'company_name'    => 'required|string|max:255',
            'legal_form'      => 'nullable|string|max:100',
            'rccm'            => 'nullable|string|max:100',
            'ifu'             => 'nullable|string|max:50',
            'tax_id'          => 'nullable|string|max:100',
            'nif'             => 'nullable|string|max:50',
            'ai'              => 'nullable|string|max:50',
            'country'         => 'nullable|string|max:100',
            'province'        => 'nullable|string|max:100',
            'city'            => 'nullable|string|max:100',
            'address_line'    => 'nullable|string|max:500',
            'po_box'          => 'nullable|string|max:50',
            'website'         => 'nullable|string|max:255',
            'capital_social'  => 'nullable|string|max:100',
            'logo_url'        => 'nullable|string|max:500',
        ]);

        $legal = LegalInfo::first() ?? new LegalInfo();
        $legal->fill($data);
        $legal->save();

        return Redirect::route('admin.legal.index')->with('success', 'Informations légales enregistrées.');
    }
}
