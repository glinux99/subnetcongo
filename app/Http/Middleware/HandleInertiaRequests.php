<?php

namespace App\Http\Middleware;

use App\Models\Certificate;
use App\Models\Exam;
use App\Models\ForumTopic;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $filterType = $request->input('filterType');

        $user = $request->user();

        return array_merge(parent::share($request), [
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'auth' => [
                'user' => $user,
                'roles' => $user ? $user->roles->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                ]) : [],
                'permissions' => $user ? $user->getAllPermissions()->pluck('name') : [],
                'can' => $user ? $user->getPermissionArray() : [],
            ],
            'menu_badges' => $user ? [
                'courses' => \App\Models\Course::count(),
                'certificates' => Certificate::where('user_id', $user->id)->where('status', 'Active')->count(),
                'exams_pending' => Exam::where('is_published', true)->where('status', 'Active')
                    ->whereDoesntHave('attempts', fn ($q) => $q->where('user_id', $user->id))
                    ->count(),
                'forum_pending' => ForumTopic::where('status', 'pending')->count(),
                'tickets_pending' => SupportTicket::whereIn('status', ['open', 'pending'])->count(),
                'notifications' => $user->unreadNotifications()->count(),
                'cart' => \App\Models\CartItem::where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)->orWhere('session_id', session()->getId());
                })->count(),
                'questions_pending' => \App\Models\LessonQuestion::where('status', 'open')
                    ->when(! $user->hasAnyRole(['admin', 'superadmin']), function ($q) use ($user) {
                        $q->whereIn('course_id', \App\Models\Course::where('instructor_id', $user->id)->orWhere('created_by', $user->id)->pluck('id'));
                    })->count(),
                'enrollments_pending' => $user->hasAnyRole(['admin', 'superadmin', 'finance-manager'])
                    ? \App\Models\Enrollment::where('status', 'pending')->count()
                    : 0,
            ] : [],
            'app_meta' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'locale' => app()->getLocale(),
                'version' => config('app.version', '1.0.0'),
            ],
            'identity' => fn () => $this->sharedIdentity(),
            'menuPages' => fn () => \App\Models\Page::where('is_published', true)
                ->where('show_in_menu', true)
                ->orderBy('menu_order')
                ->get(['id', 'title', 'slug', 'template'])
                ->map(fn ($p) => [
                    'label' => $p->title,
                    'url'   => '/' . ltrim($p->slug, '/'),
                    'template' => $p->template,
                ])->toArray(),
            'legal' => fn () => \Illuminate\Support\Facades\Schema::hasTable('legal_info')
                ? \App\Models\LegalInfo::first()
                : null,
            'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
            'filters' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'filterType' => $filterType,
            ],
        ]);
    }

    /**
     * Identité du site (clé/valeur → array), partagée globalement.
     */
    private function sharedIdentity(): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('web_settings')) {
            return [
                'site_name'        => config('app.name'),
                'site_tagline'     => '',
                'site_description' => '',
                'contact_email'    => '',
                'contact_phone'    => '',
                'contact_address'  => '',
                'footer_text'      => '© ' . date('Y') . ' ' . config('app.name') . '. Tous droits réservés.',
            ];
        }
        $rows = \App\Models\WebSetting::all();
        $merged = [];
        foreach (['general' => ['site_name', 'site_tagline', 'site_description', 'site_logo', 'site_favicon', 'footer_text'],
                  'contact' => ['contact_email', 'contact_phone', 'contact_address'],
                  'social'  => ['social_facebook', 'social_twitter', 'social_linkedin', 'social_youtube', 'social_instagram']] as $group => $keys) {
            $subset = $rows->where('group', $group);
            foreach ($keys as $k) {
                $r = $subset->firstWhere('key', $k);
                $merged[$k] = $r?->value ?? '';
            }
        }
        // Valeurs par défaut
        $merged['site_name']    = $merged['site_name']    ?: config('app.name');
        $merged['footer_text']  = $merged['footer_text']  ?: '© ' . date('Y') . ' ' . $merged['site_name'];
        $merged['site_tagline'] = $merged['site_tagline'] ?: "Centre d'excellence numérique en RDC";
        return $merged;
    }
}