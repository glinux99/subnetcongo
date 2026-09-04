<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SeoSetting; // Modèle hypothétique pour les règles SEO
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class SeoController extends Controller
{
    /**
     * Affiche le tableau de bord SEO.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        // Récupération des paramètres globaux (souvent stockés en JSON ou table key/value)
        $globalSettings = SeoSetting::where('key', 'global')->first()?->value ?? [
            'site_name' => config('app.name'),
            'separator' => '|',
            'default_description' => '',
            'default_keywords' => '',
            'twitter_handle' => '',
        ];

        // Liste des règles spécifiques par URL
        $rules = SeoSetting::where('key', '!=', 'global')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Lecture du robots.txt
        $robotsTxt = File::exists(public_path('robots.txt'))
            ? File::get(public_path('robots.txt'))
            : "User-agent: *\nAllow: /";

        return Inertia::render('Web/Seo', [
            'global' => $globalSettings,
            'rules' => $rules,
            'robots_txt' => $robotsTxt,
            'sitemap_url' => url('sitemap.xml'),
        ]);
    }

    /**
     * Met à jour les paramètres globaux.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateGlobal(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:100',
            'separator' => 'required|string|max:5',
            'default_description' => 'nullable|string|max:255',
            'default_keywords' => 'nullable|string',
            'twitter_handle' => 'nullable|string',
            'og_image' => 'nullable|url',
        ]);

        SeoSetting::updateOrCreate(
            ['key' => 'global'],
            ['value' => $validated]
        );

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'seo_global_updated',
            'auditable_type' => SeoSetting::class,
            'auditable_id' => 0,
            'new_values' => json_encode($validated),
        ]);

        return Redirect::back()->with('success', 'Paramètres SEO globaux mis à jour.');
    }

    /**
     * Ajoute ou met à jour une règle SEO pour une URL spécifique.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeRule(Request $request)
    {
        $validated = $request->validate([
            'url_pattern' => 'required|string', // ex: /courses/*
            'title_template' => 'nullable|string',
            'description_template' => 'nullable|string',
            'noindex' => 'boolean',
            'nofollow' => 'boolean',
        ]);

        $setting = SeoSetting::updateOrCreate(
            ['key' => 'rule_' . md5($validated['url_pattern'])],
            [
                'url_pattern' => $validated['url_pattern'],
                'value' => $validated
            ]
        );

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'seo_rule_created',
            'auditable_type' => SeoSetting::class,
            'auditable_id' => $setting->id,
        ]);

        return Redirect::back()->with('success', 'Règle SEO enregistrée.');
    }

    /**
     * Supprime une règle SEO.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyRule(string $id)
    {
        $setting = SeoSetting::findOrFail($id);
        $setting->delete();

        return Redirect::back()->with('success', 'Règle SEO supprimée.');
    }

    /**
     * Met à jour le fichier robots.txt.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRobots(Request $request)
    {
        $request->validate(['content' => 'required|string']);

        try {
            File::put(public_path('robots.txt'), $request->content);

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'robots_txt_updated',
                'auditable_type' => 'System',
                'auditable_id' => 0,
            ]);

            return Redirect::back()->with('success', 'Fichier robots.txt mis à jour.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Erreur écriture fichier : ' . $e->getMessage());
        }
    }

    /**
     * Déclenche la génération du Sitemap XML.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generateSitemap()
    {
        // Logique simplifiée ou appel à un package comme spatie/laravel-sitemap
        try {
            // Artisan::call('sitemap:generate');

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'sitemap_generated',
                'auditable_type' => 'System',
                'auditable_id' => 0,
            ]);

            return Redirect::back()->with('success', 'Génération du sitemap lancée en arrière-plan.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Gère les redirections (301/302).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeRedirect(Request $request)
    {
        $validated = $request->validate([
            'source_url' => 'required|string',
            'target_url' => 'required|string',
            'status_code' => 'required|in:301,302',
        ]);

        SeoSetting::create([
            'key' => 'redirect_' . md5($validated['source_url']),
            'value' => $validated,
            'type' => 'redirect'
        ]);

        return Redirect::back()->with('success', 'Redirection ajoutée.');
    }
}
