<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Page;
use App\Models\Realization;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\WebSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class WebSettingController extends Controller
{
    /**
     * Charge les paramètres globaux partagés (identité site, menus, footer, etc.)
     * que toutes les pages Web utilisent.
     */
    private function sharedProps(): array
    {
        $settings = WebSetting::all()->mapWithKeys(fn ($r) => [$r->key => $r->value])->toArray();

        $identity = [
            'site_name'        => $settings['site_name']        ?? 'SubnetCongo',
            'site_tagline'     => $settings['site_tagline']     ?? 'Centre de formation et d\'excellence numérique',
            'site_description' => $settings['site_description'] ?? '',
            'site_logo'        => $settings['site_logo']        ?? null,
            'site_favicon'     => $settings['site_favicon']     ?? null,
            'contact_email'    => $settings['contact_email']    ?? 'contact@subnetcongo.cd',
            'contact_phone'    => $settings['contact_phone']    ?? '+243 000 000 000',
            'contact_address'  => $settings['contact_address']  ?? 'Kinshasa, République Démocratique du Congo',
            'social_facebook'  => $settings['social_facebook']  ?? '#',
            'social_twitter'   => $settings['social_twitter']   ?? '#',
            'social_linkedin'  => $settings['social_linkedin']  ?? '#',
            'social_youtube'   => $settings['social_youtube']   ?? '#',
            'social_instagram' => $settings['social_instagram'] ?? '#',
            'footer_text'      => $settings['footer_text']      ?? '© ' . date('Y') . ' SubnetCongo. Tous droits réservés.',
        ];

        // Catégories publiées (catégories de cours)
        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->toArray();

        // Menu principal : items configurables (si une Page a show_in_menu)
        $menuPages = Page::query()
            ->where('is_published', true)
            ->where('show_in_menu', true)
            ->orderBy('menu_order')
            ->get(['id', 'title', 'slug', 'template'])
            ->map(fn ($p) => [
                'label' => $p->title,
                'url'   => '/' . ltrim($p->slug, '/'),
                'template' => $p->template,
            ])->toArray();

        return [
            'identity'   => $identity,
            'categories' => $categories,
            'menuPages'  => $menuPages,
        ];
    }

    /**
     * Page d'accueil (Landing) — 100% backend-driven.
     */
    public function landing()
    {
        $props = $this->sharedProps();

        // Sections "About" / "FAQ" / "Services" depuis web_settings.group = 'about'
        $aboutGroup = WebSetting::where('group', 'about')->get()->pluck('value', 'key')->toArray();

        // ── SLIDES (table dédiée landing_slides) ─────────────────
        $heroSlides = \App\Models\LandingSlide::active()->get()->toArray();

        // ── TECHNOLOGIES ────────────────────────────────────────
        $technologies = \App\Models\LandingTechnology::active()->get()->toArray();

        // ── STATS ADMIN-DÉFINIES + stats agrégées temps réel ───
        $adminStats = \App\Models\LandingStat::active()->get()->toArray();
        $aggregated = [
            'students'    => User::role('student')->count(),
            'courses'     => Course::published()->count(),
            'instructors' => User::role('instructor')->count(),
            'enrollments' => Enrollment::where('status', 'active')->count(),
        ];

        // Cours publiés (catalogue)
        $publishedCourses = Course::query()
            ->with(['instructor:id,name', 'category:id,name,slug'])
            ->published()
            ->orderByDesc('published_at')
            ->limit(6)
            ->get()
            ->map(fn ($c) => [
                'id'         => $c->id,
                'title'      => $c->title,
                'slug'       => $c->slug,
                'category'   => $c->category?->name ?? '—',
                'instructor' => $c->instructor?->name ?? '—',
                'image'      => $c->image ?? $c->thumbnail ?? asset('images/placeholder-course.jpg'),
                'rating'     => (float) ($c->rating ?? 4.7),
                'duration'   => ($c->duration_hours ?? 0) . 'h',
                'level'      => $c->level ?? 'all',
                'is_live'    => (bool) ($c->has_live ?? false),
                'price'      => (float) ($c->price ?? 0),
                'is_free'    => (float) ($c->price ?? 0) === 0.0,
            ])->toArray();

        // Témoignages approuvés
        $testimonials = Testimonial::query()
            ->where('is_approved', true)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn ($t) => [
                'id'         => $t->id,
                'name'       => $t->name ?? $t->author_name ?? 'Apprenant',
                'role'       => $t->role ?? '',
                'avatar'     => $t->avatar ?? $t->author_avatar ?? 'https://i.pravatar.cc/150?u=' . $t->id,
                'content'    => $t->content ?? $t->message ?? '',
                'rating'     => (int) ($t->rating ?? 5),
            ])->toArray();

        // Réalisations (filtre is_published)
        $realizations = Realization::query()
            ->where('is_published', true)
            ->orderByDesc('created_at')
            ->limit(4)
            ->get()
            ->map(fn ($r) => [
                'id'      => $r->id,
                'title'   => $r->title,
                'image'   => $r->image ?? $r->cover_image ?? null,
                'summary' => $r->summary ?? $r->description ?? '',
            ])->toArray();

        // Blog récent
        $recentPosts = BlogPost::query()
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at'])
            ->toArray();

        return Inertia::render('Web/Landing', array_merge($props, [
            'heroSlides'   => $heroSlides,
            'technologies' => $technologies,
            'adminStats'   => $adminStats,
            'about'        => $aboutGroup,
            'courses'      => $publishedCourses,
            'testimonials' => $testimonials,
            'realizations' => $realizations,
            'recentPosts'  => $recentPosts,
            'stats'        => $aggregated,
        ]));
    }

    public function about()
    {
        $props = $this->sharedProps();
        $page = Page::where('slug', 'about')->where('is_published', true)->first()
            ?? Page::where('template', 'about')->where('is_published', true)->first();

        $about = WebSetting::where('group', 'about')->get()->pluck('value', 'key')->toArray();

        return Inertia::render('Web/About', array_merge($props, [
            'page'  => $page,
            'about' => $about,
            'team'  => User::role(['instructor', 'admin'])->limit(8)->get(['id', 'name', 'avatar', 'bio'])->toArray(),
            'stats' => [
                'students'    => User::role('student')->count(),
                'instructors' => User::role('instructor')->count(),
                'courses'     => Course::published()->count(),
                'countries'   => 1, // à enrichir
            ],
        ]));
    }

    public function academy()
    {
        $props = $this->sharedProps();

        $courses = Course::query()
            ->with(['instructor:id,name', 'category:id,name,slug'])
            ->published()
            ->when(request('category'), fn ($q, $slug) => $q->whereHas('category', fn ($q2) => $q2->where('slug', $slug)))
            ->when(request('level'), fn ($q, $l) => $q->where('level', $l))
            ->when(request('search'), fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Web/Academy', array_merge($props, [
            'courses' => $courses,
            'filters' => request()->all(),
            'levels'  => ['beginner' => 'Débutant', 'intermediate' => 'Intermédiaire', 'advanced' => 'Avancé', 'all' => 'Tous niveaux'],
        ]));
    }

    public function blog()
    {
        $props = $this->sharedProps();

        $posts = BlogPost::query()
            ->where('is_published', true)
            ->when(request('search'), fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when(request('category'), fn ($q, $slug) => $q->whereHas('category', fn ($q2) => $q2->where('slug', $slug)))
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString();

        $featured = BlogPost::where('is_published', true)->orderByDesc('published_at')->limit(3)->get(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at'])->toArray();

        return Inertia::render('Web/Blog', array_merge($props, [
            'posts'    => $posts,
            'featured' => $featured,
            'filters'  => request()->all(),
        ]));
    }

    public function contact()
    {
        $props = $this->sharedProps();
        $legal = \App\Models\LegalInfo::first();
        $props['legal'] = $legal;
        return Inertia::render('Web/Contact', $props);
    }

    public function realizations()
    {
        $props = $this->sharedProps();

        $items = Realization::query()
            ->where('is_published', true)
            ->when(request('search'), fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Web/Realizations', array_merge($props, [
            'realizations' => $items,
            'filters'      => request()->all(),
        ]));
    }

    public function testimonials()
    {
        $props = $this->sharedProps();

        $items = Testimonial::query()
            ->where('is_approved', true)
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Web/Testimonials', array_merge($props, [
            'testimonials' => $items,
        ]));
    }

    public function pages(string $slug)
    {
        $page = Page::where('slug', $slug)->where('is_published', true)->firstOrFail();
        $props = $this->sharedProps();
        return Inertia::render('Web/Page', array_merge($props, ['page' => $page]));
    }

    /**
     * Soumission du formulaire de contact → stocke dans `contact_messages`.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'phone'   => 'nullable|string|max:30',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        try {
            \App\Models\ContactMessage::create([
                'ticket_number' => 'MSG-' . strtoupper(uniqid()),
                'name'          => $validated['name'],
                'email'         => $validated['email'],
                'phone'         => $validated['phone'] ?? null,
                'subject'       => $validated['subject'],
                'message'       => $validated['message'],
                'status'        => 'new',
                'ip_address'    => $request->ip(),
                'user_agent'    => substr((string) $request->userAgent(), 0, 500),
                'source'        => 'web_contact_form',
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Contact form submit failed', ['err' => $e->getMessage()]);
        }

        return Redirect::route('contact')->with('success', 'Votre message a bien été envoyé. Nous vous répondrons sous 48h.');
    }
}
