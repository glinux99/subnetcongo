<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\Engin;
use App\Models\Region;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnginController extends Controller
{
    public function index(Request $request)
    {
        $query = Engin::with('region');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('designation', 'like', "%{$search}%")
                  ->orWhere('immatriculation', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->whereIn('type', (array)$request->type);
        }

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        $engins = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $regions = Region::orderBy('designation')->get(['id', 'designation']);

        $enginTypes = [
            ['id' => 'camion', 'name' => 'Camion', 'prefix' => 'CAM'],
            ['id' => 'moto', 'name' => 'Moto', 'prefix' => 'MOT'],
            ['id' => 'fuso', 'name' => 'Fuso', 'prefix' => 'FUS'],
            ['id' => 'vehicule', 'name' => 'Véhicule', 'prefix' => 'VEH'],
        ];

        return Inertia::render('Configurations/Engins', [
            'engins' => $engins,
            'enginTypes' => $enginTypes,
            'regions' => $regions,
            'filters' => $request->only(['search', 'type', 'region_id']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255',
            'immatriculation' => 'nullable|string|max:50|unique:engins,immatriculation',
            'date_mise_en_service' => 'nullable|date',
            'type' => 'required|in:camion,moto,fuso,vehicule',
            'description' => 'nullable|string',
            'region_id' => 'nullable|exists:regions,id',
        ]);

        Engin::create($validated);

        return redirect()->back()->with('message', 'Engin créé avec succès.');
    }

    public function update(Request $request, Engin $engin)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255',
            'immatriculation' => 'nullable|string|max:50|unique:engins,immatriculation,' . $engin->id,
            'date_mise_en_service' => 'nullable|date',
            'type' => 'required|in:camion,moto,fuso,vehicule',
            'description' => 'nullable|string',
            'region_id' => 'nullable|exists:regions,id',
        ]);

        $engin->update($validated);

        return redirect()->back()->with('message', 'Engin mis à jour avec succès.');
    }

    public function destroy(Engin $engin)
    {
        $engin->delete();
        return redirect()->back()->with('message', 'Engin supprimé avec succès.');
    }
}