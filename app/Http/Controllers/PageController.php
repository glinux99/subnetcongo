<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
// use Inertia\Inertia; // Décommentez si vous utilisez Inertia.js

class PageController extends Controller
{
    /**
     * Affiche une page dynamique basée sur son slug.
     * Gère les vues dans les sous-dossiers via la colonne 'view_path'.
     */
    public function show($slug = '/')
    {
        // Si le slug est vide ou '/', on cherche la page d'accueil
        $searchSlug = ($slug === '/' || $slug === null) ? 'home' : $slug;

        $page = Page::where('slug', $searchSlug)
            ->where('is_published', true)
            ->firstOrFail();

        // Si vous utilisez Inertia.js :
        // return Inertia::render($page->view_path, [
        //     'page' => $page,
        //     'seo' => [
        //         'title' => $page->meta_title ?? $page->title,
        //         'description' => $page->meta_description,
        //     ]
        // ]);

        // Si vous utilisez Blade standard :
        return view($page->view_path, compact('page'));
    }

    /**
     * ADMIN: Liste des pages
     */
    public function index()
    {
        $pages = Page::latest()->get();
        return response()->json($pages); // Ou retourner une vue Admin
    }

    /**
     * ADMIN: Créer une page
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'view_path' => 'required|string', // Ex: "About/Team"
            'content' => 'nullable',
            'is_published' => 'boolean',
        ]);

        // Génération automatique du slug si non fourni
        $validated['slug'] = $request->input('slug')
            ? Str::slug($request->input('slug'))
            : Str::slug($validated['title']);

        $page = Page::create($validated);

        return response()->json(['message' => 'Page créée', 'page' => $page]);
    }

    /**
     * ADMIN: Mettre à jour une page
     */
    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:pages,slug,' . $page->id,
            'view_path' => 'sometimes|string',
            'content' => 'nullable',
            'is_published' => 'boolean',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
        ]);

        if (isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        $page->update($validated);

        return response()->json(['message' => 'Page mise à jour', 'page' => $page]);
    }

    public function destroy(Page $page)
    {
        $page->delete();
        return response()->json(['message' => 'Page supprimée']);
    }
}
