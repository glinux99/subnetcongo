<?php

namespace App\Http\Controllers\Elearning;

use App\Models\Elearning\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    /**
     * Sauvegarde une nouvelle catégorie depuis le modal (Inertia)
     */
    public function store(Request $request)
    {
        // 1. Validation de la requête
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'slug' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ], [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.unique' => 'Cette catégorie existe déjà dans le catalogue.',
        ]);

        Category::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'parent_id' => $request->parent_id,
            'description' => $request->description,
            'is_active' => $request->input('is_active', true),
        ]);

        // 3. Redirection Inertia (Retourne sur la page actuelle)
        // La méthode router.reload({ only: ['categories'] }) côté Vue
        // interceptera cette redirection et mettra à jour le dropdown.
        return Redirect::back()->with('success', 'Catégorie créée avec succès.');
    }
}
