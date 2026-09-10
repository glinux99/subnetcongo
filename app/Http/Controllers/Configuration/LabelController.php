<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\Label;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LabelController extends Controller
{
    public function index(Request $request)
    {
        $query = Label::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('designation', 'like', "%{$search}%");
            });
        }

        $labels = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Configurations/Labels', [
            'labels' => $labels,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|size:6',
            'characteristics' => 'nullable|array',
        ]);

        Label::create($validated);

        return redirect()->back()->with('message', 'Label créé avec succès.');
    }

    public function update(Request $request, Label $label)
    {
        $validated = $request->validate([
            'designation' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|size:6',
            'characteristics' => 'nullable|array',
        ]);

        $label->update($validated);

        return redirect()->back()->with('message', 'Label mis à jour avec succès.');
    }

    public function destroy(Label $label)
    {
        $label->delete();
        return redirect()->back()->with('message', 'Label supprimé avec succès.');
    }
}