<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegionController extends Controller
{
    public function index(Request $request)
    {
        $query = Region::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('designation', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type_centrale')) {
            $query->whereIn('type_centrale', (array)$request->type_centrale);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('puissance_min') || $request->filled('puissance_max')) {
            $query->whereBetween('puissance_centrale', [
                $request->puissance_min ?? 0,
                $request->puissance_max ?? 999999,
            ]);
        }

        $regions = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Configurations/Regions', [
            'regions' => $regions,
            'filters' => $request->only(['search', 'type_centrale', 'status', 'puissance_min', 'puissance_max']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:regions,code',
            'type_centrale' => 'required|in:solaire,eolien,hydro,thermique,hybride',
            'puissance_centrale' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive,maintenance',
        ]);

        Region::create($validated);

        return redirect()->back()->with('message', 'Région créée avec succès.');
    }

    public function update(Request $request, Region $region)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:regions,code,' . $region->id,
            'type_centrale' => 'required|in:solaire,eolien,hydro,thermique,hybride',
            'puissance_centrale' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive,maintenance',
        ]);

        $region->update($validated);

        return redirect()->back()->with('message', 'Région mise à jour avec succès.');
    }

    public function destroy(Region $region)
    {
        $region->delete();
        return redirect()->back()->with('message', 'Région supprimée avec succès.');
    }
}