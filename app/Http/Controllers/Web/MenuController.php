<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu; // Modèle supposé
use App\Models\MenuItem; // Modèle supposé
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    /**
     * Liste des menus.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $menus = Menu::withCount('items')->orderBy('name')->get();

        return Inertia::render('Web/Menus/Index', [
            'menus' => $menus,
            'locations' => ['header', 'footer', 'sidebar', 'mobile'], // Emplacements disponibles
        ]);
    }

    /**
     * Formulaire de création.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('Web/Menus/Create', [
            'locations' => ['header', 'footer', 'sidebar', 'mobile'],
        ]);
    }

    /**
     * Enregistre un nouveau menu.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:menus,name',
            'location' => 'nullable|string|in:header,footer,sidebar,mobile',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Si un emplacement est choisi, désactiver les autres menus à cet emplacement
            if (!empty($validated['location'])) {
                Menu::where('location', $validated['location'])->update(['location' => null]);
            }

            $menu = Menu::create($validated);

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'menu_created',
                'auditable_type' => Menu::class,
                'auditable_id' => $menu->id,
            ]);

            DB::commit();
            return Redirect::route('web.menus.edit', $menu->id)->with('success', 'Menu créé. Ajoutez maintenant des éléments.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur création : ' . $e->getMessage());
        }
    }

    /**
     * Formulaire d'édition (Builder de menu).
     *
     * @param string $id
     * @return \Inertia\Response
     */
    public function edit(string $id)
    {
        $menu = Menu::with(['items' => function ($q) {
            $q->orderBy('order');
        }])->findOrFail($id);

        return Inertia::render('Web/Menus/Edit', [
            'menu' => $menu,
            'locations' => ['header', 'footer', 'sidebar', 'mobile'],
            // Pages disponibles pour ajout rapide
            'available_pages' => \App\Models\Page::where('status', 'published')->select('id', 'title', 'slug')->get(),
        ]);
    }

    /**
     * Met à jour les propriétés du menu.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $id)
    {
        $menu = Menu::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('menus')->ignore($menu->id)],
            'location' => 'nullable|string|in:header,footer,sidebar,mobile',
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            if (!empty($validated['location']) && $validated['location'] !== $menu->location) {
                Menu::where('location', $validated['location'])->update(['location' => null]);
            }

            $menu->update($validated);

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'menu_updated',
                'auditable_type' => Menu::class,
                'auditable_id' => $menu->id,
            ]);

            DB::commit();
            return Redirect::back()->with('success', 'Configuration du menu mise à jour.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Met à jour la structure (items) du menu.
     * Reçoit généralement un tableau JSON imbriqué ou plat avec 'order' et 'parent_id'.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStructure(Request $request, string $id)
    {
        $menu = Menu::findOrFail($id);

        $request->validate([
            'items' => 'required|array',
            'items.*.title' => 'required|string',
            'items.*.url' => 'nullable|string',
            'items.*.target' => 'nullable|string',
            'items.*.order' => 'required|integer',
            'items.*.parent_id' => 'nullable|integer',
        ]);

        DB::beginTransaction();
        try {
            // Stratégie simple : supprimer et recréer (ou synchroniser si IDs présents)
            $menu->items()->delete();

            $menu->items()->createMany($request->input('items'));

            DB::commit();
            return Redirect::back()->with('success', 'Structure du menu enregistrée.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur sauvegarde structure : ' . $e->getMessage());
        }
    }

    /**
     * Supprime un menu.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $id)
    {
        $menu = Menu::findOrFail($id);
        $menu->delete();

        return Redirect::route('web.menus.index')->with('success', 'Menu supprimé.');
    }
}
