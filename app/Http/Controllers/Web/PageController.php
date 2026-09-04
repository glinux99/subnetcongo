<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Page;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Carbon\Carbon;

class PageController extends Controller
{
    /**
     * Constructeur pour les permissions.
     */
    public function __construct()
    {
        // $this->middleware('can:manage-pages');
    }

    /**
     * Affiche la liste des pages avec filtrage et recherche.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $query = Page::query()
            ->when($request->input('search'), function ($q, $search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            })
            ->when($request->input('status'), function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->input('author_id'), function ($q, $authorId) {
                $q->where('author_id', $authorId);
            });

        $sortField = $request->input('sort_field', 'updated_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $pages = $query->orderBy($sortField, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Web/Pages', [
            'pages' => $pages,
            'filters' => $request->all(['search', 'status', 'sort_field', 'sort_direction']),
            'stats' => [
                'total' => Page::count(),
                'published' => Page::where('status', 'published')->count(),
                'draft' => Page::where('status', 'draft')->count(),
            ]
        ]);
    }

    /**
     * Formulaire de création de page.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('Web/Pages', [
            'templates' => ['default', 'landing', 'contact', 'legal'],
        ]);
    }

    /**
     * Enregistre une nouvelle page.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug',
            'content' => 'required|string',
            'status' => 'required|in:published,draft,archived',
            'template' => 'required|string',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'is_homepage' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            // Génération automatique du slug si vide
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['title']);
            }

            // Gestion de la page d'accueil unique
            if ($request->boolean('is_homepage')) {
                Page::where('is_homepage', true)->update(['is_homepage' => false]);
            }

            $page = Page::create([
                ...$validated,
                'author_id' => Auth::id(),
                'published_at' => $validated['status'] === 'published' ? now() : null,
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'page_created',
                'auditable_type' => Page::class,
                'auditable_id' => $page->id,
                'new_values' => json_encode($page->toArray()),
            ]);

            DB::commit();

            return Redirect::route('web.pages.index')
                ->with('success', 'Page créée avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur : ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Affiche l'aperçu ou les détails d'une page.
     *
     * @param string $id
     * @return \Inertia\Response
     */
    public function show(string $id)
    {
        $page = Page::with('author')->findOrFail($id);

        return Inertia::render('Web/Pages', [
            'page' => $page,
            'revisions' => $page->revisions()->latest()->take(5)->get() // Supposant un modèle Revision
        ]);
    }

    /**
     * Formulaire d'édition.
     *
     * @param string $id
     * @return \Inertia\Response
     */
    public function edit(string $id)
    {
        $page = Page::findOrFail($id);

        return Inertia::render('Web/Pages', [
            'page' => $page,
            'templates' => ['default', 'landing', 'contact', 'legal'],
        ]);
    }

    /**
     * Met à jour une page existante.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $id)
    {
        $page = Page::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('pages')->ignore($page->id)],
            'content' => 'required|string',
            'status' => 'required|in:published,draft,archived',
            'template' => 'required|string',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'is_homepage' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            $oldValues = $page->toArray();

            if ($request->boolean('is_homepage') && !$page->is_homepage) {
                Page::where('is_homepage', true)->update(['is_homepage' => false]);
            }

            // Mise à jour de la date de publication si changement de statut
            if ($page->status !== 'published' && $validated['status'] === 'published') {
                $validated['published_at'] = now();
            }

            $page->update($validated);

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'page_updated',
                'auditable_type' => Page::class,
                'auditable_id' => $page->id,
                'old_values' => json_encode($oldValues),
                'new_values' => json_encode($page->fresh()->toArray()),
            ]);

            DB::commit();

            return Redirect::route('web.pages.index')
                ->with('success', 'Page mise à jour.');

        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Supprime une page.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $id)
    {
        $page = Page::findOrFail($id);

        if ($page->is_homepage) {
            return Redirect::back()->with('error', 'Impossible de supprimer la page d\'accueil active.');
        }

        try {
            $page->delete();

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'page_deleted',
                'auditable_type' => Page::class,
                'auditable_id' => $id,
            ]);

            return Redirect::back()->with('success', 'Page supprimée.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Duplique une page existante.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function duplicate(string $id)
    {
        $original = Page::findOrFail($id);

        DB::beginTransaction();
        try {
            $clone = $original->replicate();
            $clone->title = $original->title . ' (Copie)';
            $clone->slug = $original->slug . '-copie-' . uniqid();
            $clone->status = 'draft';
            $clone->is_homepage = false;
            $clone->created_at = now();
            $clone->updated_at = now();
            $clone->save();

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'page_duplicated',
                'auditable_type' => Page::class,
                'auditable_id' => $clone->id,
                'new_values' => json_encode(['source_id' => $original->id]),
            ]);

            DB::commit();
            return Redirect::route('web.pages.edit', $clone->id)->with('success', 'Page dupliquée en brouillon.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur duplication : ' . $e->getMessage());
        }
    }

    /**
     * Upload d'image pour l'éditeur WYSIWYG.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request)
    {
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('pages/content', 'public');
            return response()->json(['location' => asset('storage/' . $path)]);
        }
        return response()->json(['error' => 'Aucun fichier.'], 400);
    }
}
