<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
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
            'tva_rate'        => 'nullable|numeric|min:0|max:100',
            'vat_number'      => 'nullable|string|max:50',
            'currency'        => 'nullable|string|in:CDF,USD,EUR',
            'country'         => 'nullable|string|max:100',
            'province'        => 'nullable|string|max:100',
            'city'            => 'nullable|string|max:100',
            'address_line'    => 'nullable|string|max:500',
            'po_box'          => 'nullable|string|max:50',
            'website'         => 'nullable|string|max:255',
            'capital_social'  => 'nullable|string|max:100',
            'logo_url'        => 'nullable|string|max:500',
            'logo'            => 'nullable|image|max:2048|mimes:png,jpg,jpeg,webp,svg',
        ]);

        // Logo uploadé : stocké sur le disque media (public_path('media')),
        // l'URL publique est enregistrée dans logo_url.
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('legal', 'media');
            $data['logo_url'] = Storage::disk('media')->url($path);
        }

        unset($data['logo']);

        $legal = LegalInfo::first() ?? new LegalInfo();
        $legal->fill($data);
        $legal->save();

        return Redirect::route('admin.legal.index')->with('success', 'Informations légales enregistrées.');
    }
}
