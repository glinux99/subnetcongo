<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BlogPost; // Modèle supposé
use App\Models\BlogCategory; // Modèle supposé
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BlogController extends Controller
{
    /**
     * Liste des articles de blog.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $query = BlogPost::with(['category', 'author'])
            ->when($request->input('search'), function ($q, $search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            })
            ->when($request->input('status'), function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->input('category_id'), function ($q, $catId) {
                $q->where('category_id', $catId);
            });

        $sortField = $request->input('sort_field', 'published_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $posts = $query->orderBy($sortField, $sortDirection)
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        return Inertia::render('Web/Blog', [
            'posts' => $posts,
            'categories' => BlogCategory::all(['id', 'name']),
            'filters' => $request->all(['search', 'status', 'category_id', 'sort_field', 'sort_direction']),
            'stats' => [
                'total' => BlogPost::count(),
                'published' => BlogPost::where('status', 'published')->count(),
                'draft' => BlogPost::where('status', 'draft')->count(),
            ]
        ]);
    }

    /**
     * Formulaire de création.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('Web/Blog', [
            'categories' => BlogCategory::all(['id', 'name']),
        ]);
    }

    /**
     * Enregistre un nouvel article.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_posts,slug',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'category_id' => 'nullable|exists:blog_categories,id',
            'status' => 'required|in:draft,published,archived',
            'featured_image' => 'nullable|image|max:2048',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
        ]);

        DB::beginTransaction();
        try {
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['title']);
            }

            $imagePath = null;
            if ($request->hasFile('featured_image')) {
                $imagePath = $request->file('featured_image')->store('blog/images', 'public');
            }

            $post = BlogPost::create([
                ...$validated,
                'author_id' => Auth::id(),
                'image_path' => $imagePath,
                'published_at' => $validated['status'] === 'published' ? now() : null,
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'blog_post_created',
                'auditable_type' => BlogPost::class,
                'auditable_id' => $post->id,
            ]);

            DB::commit();
            return Redirect::route('web.blog.index')->with('success', 'Article créé avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur création : ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Affiche un article.
     *
     * @param string $id
     * @return \Inertia\Response
     */
    public function show(string $id)
    {
        $post = BlogPost::with(['category', 'author'])->findOrFail($id);
        return Inertia::render('Web/Blog', ['post' => $post]);
    }

    /**
     * Formulaire d'édition.
     *
     * @param string $id
     * @return \Inertia\Response
     */
    public function edit(string $id)
    {
        $post = BlogPost::findOrFail($id);
        return Inertia::render('Web/Blog', [
            'post' => $post,
            'categories' => BlogCategory::all(['id', 'name']),
        ]);
    }

    /**
     * Met à jour un article.
     *
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $id)
    {
        $post = BlogPost::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('blog_posts')->ignore($post->id)],
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'category_id' => 'nullable|exists:blog_categories,id',
            'status' => 'required|in:draft,published,archived',
            'featured_image' => 'nullable|image|max:2048',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('featured_image')) {
                $validated['image_path'] = $request->file('featured_image')->store('blog/images', 'public');
            }

            // Mise à jour de la date de publication si passage à 'published'
            if ($post->status !== 'published' && $validated['status'] === 'published') {
                $validated['published_at'] = now();
            }

            $post->update($validated);

            AuditLog::create([
                'user_id' => Auth::id(),
                'event' => 'blog_post_updated',
                'auditable_type' => BlogPost::class,
                'auditable_id' => $post->id,
            ]);

            DB::commit();
            return Redirect::route('web.blog.index')->with('success', 'Article mis à jour.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur mise à jour : ' . $e->getMessage());
        }
    }

    /**
     * Supprime un article.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $id)
    {
        $post = BlogPost::findOrFail($id);
        $post->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'blog_post_deleted',
            'auditable_type' => BlogPost::class,
            'auditable_id' => $id,
        ]);

        return Redirect::back()->with('success', 'Article supprimé.');
    }

    /**
     * Duplique un article.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function duplicate(string $id)
    {
        $original = BlogPost::findOrFail($id);

        $clone = $original->replicate();
        $clone->title = $original->title . ' (Copie)';
        $clone->slug = $original->slug . '-copie-' . uniqid();
        $clone->status = 'draft';
        $clone->published_at = null;
        $clone->created_at = now();
        $clone->save();

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'blog_post_duplicated',
            'auditable_type' => BlogPost::class,
            'auditable_id' => $clone->id,
        ]);

        return Redirect::route('web.blog.edit', $clone->id)->with('success', 'Article dupliqué en brouillon.');
    }

    /**
     * Suppression de masse.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        $count = BlogPost::whereIn('id', $request->ids)->delete();

        return Redirect::back()->with('success', "$count articles supprimés.");
    }
}
