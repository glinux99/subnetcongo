<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Widget;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class WidgetController extends Controller
{
    /**
     * Liste des widgets par zone.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $widgets = Widget::orderBy('position')->get()->groupBy('zone');

        return Inertia::render('Web/Widgets/Index', [
            'widgets' => $widgets,
            'zones' => ['sidebar_left', 'sidebar_right', 'footer_1', 'footer_2', 'footer_3', 'homepage_top'],
            'types' => ['html', 'menu', 'recent_posts', 'newsletter', 'image'],
        ]);
    }

    /**
     * Ajoute un widget.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'type' => 'required|in:html,menu,recent_posts,newsletter,image',
            'zone' => 'required|string',
            'content' => 'nullable|array', // JSON content
            'is_active' => 'boolean',
        ]);

        // Calcul de la position (dernier + 1)
        $maxPosition = Widget::where('zone', $validated['zone'])->max('position') ?? 0;

        $widget = Widget::create([
            ...$validated,
            'position' => $maxPosition + 1,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'widget_created',
            'auditable_type' => Widget::class,
            'auditable_id' => $widget->id,
        ]);

        return Redirect::back()->with('success', 'Widget ajouté.');
    }

    /**
     * Met à jour un widget.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $id)
    {
        $widget = Widget::findOrFail($id);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $widget->update($validated);

        return Redirect::back()->with('success', 'Widget mis à jour.');
    }

    /**
     * Réorganise les widgets (Drag & Drop).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'widgets' => 'required|array',
            'widgets.*.id' => 'required|exists:widgets,id',
            'widgets.*.position' => 'required|integer',
            'widgets.*.zone' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->widgets as $item) {
                Widget::where('id', $item['id'])->update([
                    'position' => $item['position'],
                    'zone' => $item['zone']
                ]);
            }

            DB::commit();
            return Redirect::back()->with('success', 'Ordre des widgets enregistré.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur réorganisation : ' . $e->getMessage());
        }
    }

    /**
     * Supprime un widget.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $id)
    {
        $widget = Widget::findOrFail($id);
        $widget->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'widget_deleted',
            'auditable_type' => Widget::class,
            'auditable_id' => $id,
        ]);

        return Redirect::back()->with('success', 'Widget supprimé.');
    }
}
