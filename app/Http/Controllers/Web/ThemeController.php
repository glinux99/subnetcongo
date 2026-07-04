<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Theme; // Modèle pour stocker les infos des thèmes
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ThemeController extends Controller
{
    /**
     * Liste les thèmes installés.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $themes = Theme::all();

        // Scan du dossier themes pour détecter les nouveaux (optionnel)
        // $this->scanThemesDirectory();

        return Inertia::render('Web/Themes/Index', [
            'themes' => $themes,
            'active_theme' => Theme::where('is_active', true)->first(),
        ]);
    }

    /**
     * Active un thème spécifique.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function activate(string $id)
    {
        $theme = Theme::findOrFail($id);

        DB::beginTransaction();
        try {
            // Désactiver tous les thèmes
            Theme::query()->update(['is_active' => false]);

            // Activer le thème choisi
            $theme->update(['is_active' => true]);

            // Vider le cache de vue pour appliquer les changements
            // Artisan::call('view:clear');

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'theme_activated',
                'auditable_type' => Theme::class,
                'auditable_id' => $theme->id,
                'new_values' => json_encode(['name' => $theme->name]),
            ]);

            DB::commit();
            return Redirect::back()->with('success', "Thème {$theme->name} activé.");
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur activation : ' . $e->getMessage());
        }
    }

    /**
     * Met à jour la configuration du thème (couleurs, logos, etc.).
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSettings(Request $request, string $id)
    {
        $theme = Theme::findOrFail($id);

        $validated = $request->validate([
            'config' => 'required|array', // JSON des réglages
            'config.primary_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'config.font_family' => 'nullable|string',
            'config.logo_url' => 'nullable|url',
        ]);

        $theme->update(['config' => array_merge($theme->config ?? [], $validated['config'])]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'theme_settings_updated',
            'auditable_type' => Theme::class,
            'auditable_id' => $theme->id,
        ]);

        return Redirect::back()->with('success', 'Configuration du thème enregistrée.');
    }

    /**
     * Upload d'un nouveau thème (fichier ZIP).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'theme_package' => 'required|file|mimes:zip|max:10240', // 10MB max
        ]);

        try {
            $file = $request->file('theme_package');
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            // Logique d'extraction (simplifiée)
            // $zip = new ZipArchive;
            // $zip->extractTo(resource_path('views/themes/' . $filename));

            // Enregistrement en base
            $theme = Theme::create([
                'name' => ucfirst($filename),
                'path' => $filename,
                'version' => '1.0.0',
                'is_active' => false,
                'config' => [],
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'theme_installed',
                'auditable_type' => Theme::class,
                'auditable_id' => $theme->id,
            ]);

            return Redirect::route('web.themes.index')->with('success', 'Thème installé avec succès.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Erreur installation : ' . $e->getMessage());
        }
    }

    /**
     * Supprime un thème.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $id)
    {
        $theme = Theme::findOrFail($id);

        if ($theme->is_active) {
            return Redirect::back()->with('error', 'Impossible de supprimer le thème actif.');
        }

        // Suppression des fichiers (logique à implémenter selon structure)
        // File::deleteDirectory(resource_path('views/themes/' . $theme->path));

        $theme->delete();

        return Redirect::back()->with('success', 'Thème supprimé.');
    }
}
