<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BlogPostController extends Controller
{
    public function index(Request $request)
    {
        $search     = trim((string) $request->input('search', ''));
        $status     = $request->input('status');
        $categoryId = $request->input('category_id');
        $perPage    = min(max((int) $request->input('per_page', 15), 5), 100);

        $posts = BlogPost::query()
            ->with('category')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('title', 'like', "%{$search}%")
                      ->orWhere('excerpt', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['draft', 'published'], true), fn ($q) => $q->where('status', $status))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $categories = BlogCategory::orderBy('name')->get();

        $stats = [
            'total'     => BlogPost::count(),
            'published' => BlogPost::where(fn ($q) => $q->where('is_published', true)->orWhere('status', 'published'))->count(),
            'drafts'    => BlogPost::where(fn ($q) => $q->where('is_published', false)->where('status', '!=', 'published'))->count(),
        ];

        return Inertia::render('Admin/Blog/Index', [
            'posts'      => $posts,
            'categories' => $categories,
            'filters'    => $request->only(['search', 'status', 'category_id', 'per_page']),
            'stats'      => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePost($request);

        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?? null, $validated['title']);
        $validated = $this->syncPublication($validated);
        $validated['author_id'] = $validated['author_id'] ?? $request->user()?->id;

        BlogPost::create($validated);

        return redirect()->route('admin.blog.index')
            ->with('success', 'Article créé avec succès.');
    }

    public function update(Request $request, BlogPost $post)
    {
        $validated = $this->validatePost($request, $post);

        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?? null, $validated['title'], $post->id);
        $validated = $this->syncPublication($validated, $post);

        $post->update($validated);

        return redirect()->route('admin.blog.index')
            ->with('success', 'Article mis à jour.');
    }

    public function destroy(BlogPost $post)
    {
        $post->delete();

        return redirect()->route('admin.blog.index')
            ->with('success', 'Article supprimé.');
    }

    public function toggle(BlogPost $post)
    {
        $post->is_published = ! $post->is_published;
        $post->status = $post->is_published ? 'published' : 'draft';
        if ($post->is_published && ! $post->published_at) {
            $post->published_at = now();
        }
        $post->save();

        return redirect()->route('admin.blog.index')
            ->with('success', $post->is_published
                ? 'Article publié.'
                : 'Article repassé en brouillon.');
    }

    private function validatePost(Request $request, ?BlogPost $post = null): array
    {
        return $request->validate([
            'title'          => 'required|string|max:255',
            'slug'           => [
                'nullable', 'string', 'max:255',
                Rule::unique('blog_posts', 'slug')->ignore($post?->id),
            ],
            'excerpt'        => 'nullable|string|max:1000',
            'content'        => 'nullable|string',
            'thumbnail'      => 'nullable|string|max:500',
            'featured_image' => 'nullable|string|max:500',
            'category_id'    => 'nullable|integer|exists:blog_categories,id',
            'status'         => 'nullable|in:draft,published',
            'is_published'   => 'boolean',
            'published_at'   => 'nullable|date',
        ]);
    }

    private function syncPublication(array $validated, ?BlogPost $post = null): array
    {
        $isPublished = (bool) ($validated['is_published'] ?? false);

        if ($isPublished) {
            $validated['status'] = 'published';
            $validated['published_at'] = $validated['published_at'] ?? $post?->published_at ?? now();
        } elseif (($validated['status'] ?? null) === 'published') {
            $validated['is_published'] = true;
            $validated['published_at'] = $validated['published_at'] ?? $post?->published_at ?? now();
        } else {
            $validated['status'] = 'draft';
        }

        return $validated;
    }

    private function uniqueSlug(?string $slug, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $title, '-');
        if ($base === '') {
            $base = 'article';
        }

        $candidate = $base;
        $i = 2;
        while (BlogPost::where('slug', $candidate)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $candidate = $base . '-' . $i;
            $i++;
        }

        return $candidate;
    }
}
