<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Realization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RealizationController extends Controller
{
    public function index(Request $request)
    {
        $search   = trim((string) $request->input('search', ''));
        $category = $request->input('category');
        $status   = $request->input('status');
        $perPage  = min(max((int) $request->input('per_page', 15), 5), 100);

        $realizations = Realization::query()
            ->when($search, function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('title', 'like', "%{$search}%")
                      ->orWhere('client', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%")
                      ->orWhere('summary', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($status === 'published', fn ($q) => $q->where('is_published', true))
            ->when($status === 'draft', fn ($q) => $q->where('is_published', false))
            ->orderByDesc('completed_at')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $categories = Realization::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $stats = [
            'total'     => Realization::count(),
            'published' => Realization::where('is_published', true)->count(),
            'projects'  => Realization::whereNotNull('completed_at')->count(),
        ];

        return Inertia::render('Admin/Realizations/Index', [
            'realizations' => $realizations,
            'categories'   => $categories,
            'filters'      => $request->only(['search', 'category', 'status', 'per_page']),
            'stats'        => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRealization($request);

        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?? null, $validated['title']);
        $validated['is_published'] = (bool) ($validated['is_published'] ?? false);
        $validated['tags'] = array_values(array_filter($validated['tags'] ?? [], fn ($t) => is_string($t) && trim($t) !== ''));

        Realization::create($validated);

        return redirect()->route('admin.realizations.index')
            ->with('success', 'Réalisation créée avec succès.');
    }

    public function update(Request $request, Realization $realization)
    {
        $validated = $this->validateRealization($request, $realization);

        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?? null, $validated['title'], $realization->id);
        $validated['is_published'] = (bool) ($validated['is_published'] ?? false);
        $validated['tags'] = array_values(array_filter($validated['tags'] ?? [], fn ($t) => is_string($t) && trim($t) !== ''));

        $realization->update($validated);

        return redirect()->route('admin.realizations.index')
            ->with('success', 'Réalisation mise à jour.');
    }

    public function destroy(Realization $realization)
    {
        $realization->delete();

        return redirect()->route('admin.realizations.index')
            ->with('success', 'Réalisation supprimée.');
    }

    public function toggle(Realization $realization)
    {
        $realization->update(['is_published' => ! $realization->is_published]);

        return redirect()->route('admin.realizations.index')
            ->with('success', $realization->is_published
                ? 'Réalisation publiée.'
                : 'Réalisation dépubliée.');
    }

    private function validateRealization(Request $request, ?Realization $realization = null): array
    {
        return $request->validate([
            'title'        => 'required|string|max:255',
            'slug'         => [
                'nullable', 'string', 'max:255',
                Rule::unique('realizations', 'slug')->ignore($realization?->id),
            ],
            'summary'      => 'nullable|string|max:1000',
            'description'  => 'nullable|string',
            'image'        => 'nullable|string|max:500',
            'client'       => 'nullable|string|max:255',
            'location'     => 'nullable|string|max:255',
            'completed_at' => 'nullable|date',
            'category'     => 'nullable|string|max:255',
            'tags'         => 'nullable|array',
            'tags.*'       => 'string|max:100',
            'is_published' => 'boolean',
        ]);
    }

    private function uniqueSlug(?string $slug, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $title, '-');
        if ($base === '') {
            $base = 'realisation';
        }

        $candidate = $base;
        $i = 2;
        while (Realization::where('slug', $candidate)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $candidate = $base . '-' . $i;
            $i++;
        }

        return $candidate;
    }
}
