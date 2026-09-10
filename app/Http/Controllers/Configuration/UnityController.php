<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\Unity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UnityController extends Controller
{
    public function index(Request $request)
    {
        $query = Unity::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('designation', 'like', "%{$search}%")
                  ->orWhere('abreviation', 'like', "%{$search}%");
            });
        }

        $unities = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Configurations/Unities', [
            'unities' => $unities,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255',
            'abreviation' => 'required|string|max:10|unique:unities,abreviation',
        ]);

        Unity::create($validated);

        return redirect()->back()->with('message', 'Unité créée avec succès.');
    }

    public function update(Request $request, Unity $unity)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255',
            'abreviation' => 'required|string|max:10|unique:unities,abreviation,' . $unity->id,
        ]);

        $unity->update($validated);

        return redirect()->back()->with('message', 'Unité mise à jour avec succès.');
    }

    public function destroy(Unity $unity)
    {
        $unity->delete();
        return redirect()->back()->with('message', 'Unité supprimée avec succès.');
    }
}