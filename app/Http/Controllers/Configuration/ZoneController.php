<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use App\Models\Region;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ZoneController extends Controller
{
    public function index(Request $request)
    {
        $query = Zone::with('region');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('nomenclature', 'like', "%{$search}%");
            });
        }

        if ($request->filled('nomenclature')) {
            $query->where('nomenclature', 'like', "{$request->nomenclature}%");
        }

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        $zones = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $regions = Region::orderBy('designation')->get(['id', 'designation']);

        return Inertia::render('Configurations/Zones', [
            'zones' => $zones,
            'regions' => $regions,
            'filters' => $request->only(['search', 'nomenclature', 'region_id']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'nomenclature' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'region_id' => 'nullable|exists:regions,id',
            'number' => 'nullable|integer',
        ]);

        Zone::create($validated);

        return redirect()->back()->with('message', 'Zone créée avec succès.');
    }

    public function update(Request $request, Zone $zone)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'nomenclature' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'region_id' => 'nullable|exists:regions,id',
            'number' => 'nullable|integer',
        ]);

        $zone->update($validated);

        return redirect()->back()->with('message', 'Zone mise à jour avec succès.');
    }

    public function destroy(Zone $zone)
    {
        $zone->delete();
        return redirect()->back()->with('message', 'Zone supprimée avec succès.');
    }
}