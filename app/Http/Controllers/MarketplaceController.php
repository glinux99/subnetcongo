<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Setting;
use App\Models\WebSetting;
use App\Models\Wishlist;
use App\Notifications\CourseEnrolledNotification;
use App\Services\CountriesService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MarketplaceController extends Controller
{
    public function __construct(protected PaymentService $paymentService) {}

    /**
     * Catalogue public (accessible sans auth, enrichi si loggué).
     */
    public function index(Request $request)
    {
        $query = Course::with(['instructor:id,name', 'category:id,name'])
            ->where('status', Course::STATUS_PUBLISHED);

        // Filtres
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('short_info', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($cat = $request->input('category')) {
            $query->where('category_id', $cat);
        }
        if ($level = $request->input('level')) {
            $query->where('level', $level);
        }
        if ($priceRange = $request->input('price_range')) {
            if ($priceRange === 'free') $query->where('price', 0);
            elseif ($priceRange === 'paid') $query->where('price', '>', 0);
        }
        if ($lang = $request->input('language')) {
            $query->where('language', $lang);
        }

        // Tri
        $sort = $request->input('sort', 'popular');
        $query->when($sort === 'newest', fn ($q) => $q->latest())
              ->when($sort === 'price_low', fn ($q) => $q->orderBy('price', 'asc'))
              ->when($sort === 'price_high', fn ($q) => $q->orderBy('price', 'desc'))
              ->when($sort === 'popular', fn ($q) => $q->withCount('enrollments')->orderByDesc('enrollments_count'));

        $totalFiltered = (clone $query)->count();
        $freeCount = (clone $query)->where('price', 0)->count();
        $paidCount = (clone $query)->where('price', '>', 0)->count();

        $courses = $query->paginate(12)->withQueryString();

        $pageRatingStats = $this->reviewStats($courses->pluck('id')->all());
        $courses->getCollection()->transform(function ($course) use ($pageRatingStats) {
            $stat = $pageRatingStats[$course->getKey()] ?? null;
            $course->setAttribute('reviews_avg_rating', $stat['avg_rating'] ?? null);
            $course->setAttribute('reviews_count', $stat['count'] ?? 0);
            return $course;
        });
        $globalRating = $this->globalReviewStats();

        // Enrichissement pour utilisateurs connectés
        $enrolledIds = [];
        $wishlistIds = [];
        $user = Auth::user();
        if ($user) {
            $enrolledIds = Enrollment::where('user_id', $user->id)
                ->pluck('course_id')->all();
            $wishlistIds = Wishlist::where('user_id', $user->id)
                ->pluck('course_id')->all();
        }

        return Inertia::render('Marketplace/Index', [
            'courses' => $courses,
            'filters' => $request->only(['search', 'category', 'level', 'price_range', 'language', 'sort']),
            'enrolledIds' => $enrolledIds,
            'wishlistIds' => $wishlistIds,
            'cartCount' => $this->getCartCount($request),
            'categories' => \App\Models\Category::where('parent_id', null)->orderBy('name')->get(['id', 'name']),
            'stats' => [
                'total_published' => $totalFiltered,
                'free' => $freeCount,
                'paid' => $paidCount,
                'avg_rating' => $globalRating['avg_rating'],
                'reviews_count' => $globalRating['count'],
            ],
        ]);
    }

    /**
     * Page détail d'un cours.
     */
    public function show(Request $request, Course $course)
    {
        try {
            $course->load([
            'instructor:id,name,bio,avatar',
            'category:id,name',
            'modules' => fn ($q) => $q->withCount('lessons')->orderBy('order'),
            'modules.lessons' => fn ($q) => $q->orderBy('order'),
        ]);

        $reviews = $this->flatApprovedReviews($course->id);
        $reviewAgg = $this->reviewAggregate($course->id);

        $lessonsCount = (int) $course->lessons()->count();
        $enrollmentsCount = (int) $course->enrollments()->count();
        $durationHours = round(((int) ($course->duration_minutes ?? 0)) / 60, 1);
        $avgRating = $reviewAgg['count'] > 0 ? round($reviewAgg['avg_rating'], 1) : null;

        $course->setAttribute('enrollments_count', $enrollmentsCount);
        $course->setAttribute('lessons_count', $lessonsCount);
        $course->setAttribute('duration_hours', $durationHours);
        $course->setAttribute('short_description', $course->short_info ?: $course->title);
        $course->append('effective_price');

        $stats = [
            'students' => $enrollmentsCount,
            'rating' => $avgRating,
            'reviews_count' => $reviewAgg['count'],
            'lessons_count' => $lessonsCount,
            'duration_hours' => $durationHours,
        ];

        $isEnrolled = false;
        $enrollmentStatus = null;
        $inWishlist = false;
        if ($user = Auth::user()) {
            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->orderByDesc('id')
                ->first();
            $isEnrolled = $enrollment && in_array($enrollment->status, [Enrollment::STATUS_ACTIVE, Enrollment::STATUS_COMPLETED], true);
            $enrollmentStatus = $enrollment?->status;
            $inWishlist = Wishlist::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->exists();
        }

        // Cours similaires (même catégorie)
        $related = Course::with(['instructor:id,name'])
            ->where('status', Course::STATUS_PUBLISHED)
            ->where('id', '!=', $course->id)
            ->when($course->category_id, fn ($q) => $q->where('category_id', $course->category_id))
            ->limit(4)->get()
            ->each->append('effective_price');

        return Inertia::render('Marketplace/Show', [
            'course' => $course,
            'stats' => $stats,
            'isEnrolled' => $isEnrolled,
            'enrollmentStatus' => $enrollmentStatus,
            'inWishlist' => $inWishlist,
            'isInWishlist' => $inWishlist,
            'relatedCourses' => $related,
            'reviews' => $reviews,
            'cartCount' => $this->getCartCount($request),
            'liveSession' => (function () use ($course) {
                $session = \App\Models\LiveSession::where('course_id', $course->id)
                    ->whereIn('status', ['Live', 'Scheduled'])
                    ->orderByRaw("FIELD(status, 'Live', 'Scheduled')")
                    ->orderBy('start_at')
                    ->first(['id', 'title', 'status', 'start_at', 'duration']);

                if ($session) {
                    $session->share_url = $session->shareUrl();
                }

                return $session;
            })(),
        ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Panier — avec détection automatique du pays (IP / préférence user / session).
     * Sépare les articles GRATUITS (inscription directe) et PAYANTS (paiement / validation admin).
     */
    public function cart(Request $request)
    {
        $user = $request->user();
        $countryCode = $this->resolveCountryCode($request, $user);
        $country     = CountriesService::find($countryCode) ?? CountriesService::find('CD');

        [$items, $usdSubtotal, $coupon, $couponDiscount, $usdTax, $usdTotal, $taxRate] = $this->resolveCartTotals($request);

        // On annote chaque item (prix converti, estGratuit, etc.)
        $free  = [];
        $paid  = [];
        foreach ($items as $item) {
            $course = $item->course;
            $priceUsd  = (float) ($item->price - $item->discount_amount);
            $converted = CountriesService::convertFromUsd($priceUsd, $country['code'] ?? 'CD');
            $itemArray = $item->toArray();
            $itemArray['price_usd']        = $priceUsd;
            $itemArray['price_local']      = $converted['amount'];
            $itemArray['currency']         = $converted['currency'];
            $itemArray['currency_symbol']  = $converted['symbol'];
            $itemArray['is_free']          = $priceUsd <= 0;
            $isFree = $priceUsd <= 0;
            if ($isFree) {
                $free[] = $itemArray;
            } else {
                $paid[] = $itemArray;
            }
        }

        $localTotal = CountriesService::convertFromUsd($usdTotal, $country['code'] ?? 'CD');
        $localSub   = CountriesService::convertFromUsd($usdSubtotal, $country['code'] ?? 'CD');
        $localTax   = CountriesService::convertFromUsd($usdTax, $country['code'] ?? 'CD');
        $localCouponDiscount = CountriesService::convertFromUsd($couponDiscount, $country['code'] ?? 'CD');
        $localDiscount = CountriesService::convertFromUsd((float) $items->sum('discount_amount') + $couponDiscount, $country['code'] ?? 'CD');

        return Inertia::render('Marketplace/Cart', [
            'cart' => [
                'items'     => $items,
                'free'      => $free,
                'paid'      => $paid,
                'subtotal'  => $usdSubtotal,
                'subtotal_local' => $localSub['amount'],
                'tax'       => $usdTax,
                'tax_local' => $localTax['amount'],
                'tax_rate'  => $taxRate,
                'discount'  => (float) $items->sum('discount_amount') + $couponDiscount,
                'discount_local' => $localDiscount['amount'],
                'coupon_code' => $coupon?->code,
                'coupon_discount' => $couponDiscount,
                'coupon_discount_local' => $localCouponDiscount['amount'],
                'total'     => $usdTotal,
                'total_local'    => $localTotal['amount'],
                'currency'  => $localTotal['currency'],
                'symbol'    => $localTotal['symbol'],
                'count'     => $items->count(),
            ],
            'coupon'    => $coupon ? ['code' => $coupon->code, 'description' => $coupon->description, 'discount_type' => $coupon->discount_type, 'discount_value' => (float) $coupon->discount_value] : null,
            'items'     => $items,
            'subtotal'  => $usdSubtotal,
            'cartCount' => $items->count(),
            'country'   => $country,
            'countries' => CountriesService::options(),
        ]);
    }

    /**
     * Détermine le code pays de l'utilisateur (session > user > IP).
     */
    private function resolveCountryCode(Request $request, $user): string
    {
        if ($session = $request->session()->get('country_code')) {
            return strtoupper($session);
        }
        if ($user && ! empty($user->country_code)) {
            return strtoupper($user->country_code);
        }
        // Fallback : géolocalisation IP via header CDN (Cloudflare) ou défaut RDC
        $headers = [
            'CF-IPCountry',
            'HTTP_CF_IPCOUNTRY',
            'X-AppEngine-Country',
        ];
        foreach ($headers as $h) {
            if ($val = $request->header($h)) {
                return strtoupper(substr($val, 0, 2));
            }
        }
        return 'CD';
    }

    /**
     * Permet à l'utilisateur de changer son pays (sauvegarde en session).
     */
    public function setCountry(Request $request)
    {
        $data = $request->validate(['country_code' => 'required|string|size:2']);
        $request->session()->put('country_code', strtoupper($data['country_code']));
        if ($user = $request->user()) {
            $user->forceFill(['country_code' => strtoupper($data['country_code'])])->save();
        }
        return back()->with('success', 'Pays mis à jour');
    }

    /**
     * Inscription immédiate à tous les cours GRATUITS du panier (sans payer).
     */
    public function enrollFreeCart(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $items = $this->getCartItems($request)->load('course');
        $enrolled = 0;
        $skipped  = 0;
        foreach ($items as $item) {
            $course = $item->course;
            if (! $course) { $skipped++; continue; }
            $price = (float) ($course->effective_price ?? $course->price ?? 0);
            if ($price > 0) { $skipped++; continue; } // pas gratuit -> on ignore
            if ($user->isEnrolledIn($course) || $user->hasPendingEnrollment($course)) { $skipped++; continue; }

            $enrollment = null;
            DB::transaction(function () use ($user, $course, &$enrollment) {
                $enrollment = $user->enrollIn($course, [], Enrollment::STATUS_ACTIVE);
                \App\Models\UserProgress::firstOrCreate(
                    ['user_id' => $user->id, 'course_id' => $course->id, 'enrollment_id' => $enrollment->id],
                    ['progress_percent' => 0, 'last_accessed_at' => now()]
                );
            });
            try {
                $user->notify(new CourseEnrolledNotification(
                    courseTitle: $course->title,
                    courseId: $course->id,
                    courseImage: $course->thumbnail_path,
                ));
                if ($enrollment) {
                    \App\Events\EnrollmentConfirmed::dispatch($enrollment);
                }
            } catch (\Throwable $e) {
                // notification best-effort
            }
            // Retirer du panier
            $item->delete();
            $enrolled++;
        }

        if ($enrolled === 0) {
            return back()->with('error', 'Aucun cours gratuit à inscrire (ou déjà inscrit).');
        }
        $msg = "{$enrolled} cours gratuit(s) ajouté(s) à votre compte.";
        if ($skipped > 0) $msg .= " ({$skipped} élément(s) ignoré(s) — payants ou déjà inscrits).";
        return back()->with('success', $msg);
    }

    /**
     * Ajouter au panier.
     */
    public function addToCart(Request $request, Course $course)
    {
        $user = $request->user();
        if ($user) {
            // Empêcher d'ajouter un cours déjà en cours / validé
            $existing = Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->whereIn('status', [
                    Enrollment::STATUS_ACTIVE,
                    Enrollment::STATUS_COMPLETED,
                    Enrollment::STATUS_PENDING,
                ])
                ->exists();
            if ($existing) {
                return back()->with('error', 'Vous avez déjà une inscription en cours pour ce cours.');
            }
        }
        $this->upsertCartItem($request, $course);
        return back()->with('success', 'Cours ajouté au panier');
    }

    /**
     * Inscription directe à un cours GRATUIT (active immédiatement).
     */
    public function enrollFree(Request $request, Course $course)
    {
        $user = $request->user();
        abort_unless($user, 401, 'Vous devez être connecté pour vous inscrire.');

        if ((float) ($course->effective_price ?? $course->price ?? 0) > 0) {
            return back()->with('error', 'Ce cours n\'est pas gratuit. Ajoutez-le au panier pour passer commande.');
        }

        if ($user->isEnrolledIn($course)) {
            return back()->with('error', 'Vous êtes déjà inscrit à ce cours.');
        }
        if ($user->hasPendingEnrollment($course)) {
            return back()->with('error', 'Une demande d\'inscription est déjà en attente pour ce cours.');
        }

        $enrollment = null;
        DB::transaction(function () use ($user, $course, &$enrollment) {
            $enrollment = $user->enrollIn($course, [], Enrollment::STATUS_ACTIVE);
            // Initialise la progression à 0
            \App\Models\UserProgress::firstOrCreate(
                [
                    'user_id'       => $user->id,
                    'course_id'     => $course->id,
                    'enrollment_id' => $enrollment->id,
                ],
                [
                    'progress_percent' => 0,
                    'last_accessed_at' => now(),
                ]
            );
        });

        try {
            $user->notify(new CourseEnrolledNotification(
                courseTitle: $course->title,
                courseId: $course->id,
                courseImage: $course->thumbnail_path,
            ));
            if ($enrollment) {
                \App\Events\EnrollmentConfirmed::dispatch($enrollment);
            }
        } catch (\Throwable $e) {
            \Log::warning('Notify enroll free failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Inscription validée ! Bienvenue dans le cours « ' . $course->title . ' ».');
    }

    /**
     * Demande d'inscription à un cours PAYANT (statut PENDING, attend validation admin
     * après vérification du paiement).
     */
    public function requestEnrollment(Request $request, Course $course)
    {
        $user = $request->user();
        abort_unless($user, 401, 'Vous devez être connecté.');

        if ((float) ($course->effective_price ?? $course->price ?? 0) <= 0) {
            return back()->with('error', 'Ce cours est gratuit, utilisez l\'inscription directe.');
        }
        if ($user->isEnrolledIn($course)) {
            return back()->with('error', 'Vous êtes déjà inscrit à ce cours.');
        }
        if ($user->hasPendingEnrollment($course)) {
            return back()->with('error', 'Une demande est déjà en attente pour ce cours.');
        }

        $data = $request->validate([
            'payment_method' => 'required|in:orange_money,mpesa,airtel_money,stripe,paypal,on_site,manual',
            'phone_number'   => 'nullable|string|max:30',
            'country_code'   => 'nullable|string|max:5',
            'transaction_id' => 'nullable|string|max:100',
            'note'           => 'nullable|string|max:500',
        ]);

        $enrollment = $user->requestEnrollment($course, [
            'notes' => ($data['note'] ?? null) . ($data['transaction_id'] ?? null ? "\n\n[ID transaction déclarée] " . $data['transaction_id'] : ''),
        ]);

        // Notifier les admins (best-effort)
        try {
            $admins = \App\Models\User::role(['admin', 'superadmin'])->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\NewEnrollmentRequestNotification($enrollment, $data));
            }
        } catch (\Throwable $e) {
            \Log::warning('Notify admin enrollment request failed: ' . $e->getMessage());
        }

        $this->sendEnrollmentRequestEmails($user, $course, $data);

        $methodLabel = strtoupper(str_replace('_', ' ', $data['payment_method']));
        $txSuffix = $data['transaction_id'] ?? null ? " (ID transaction : {$data['transaction_id']})" : '';
        return back()->with('success', "✅ Demande envoyée via {$methodLabel}{$txSuffix}. Un administrateur validera votre inscription après vérification du paiement.");
    }

    /**
     * Retirer du panier.
     */
    public function removeFromCart(Request $request, Course $course)
    {
        $query = CartItem::where('course_id', $course->id);
        $this->scopeCartQuery($request, $query);
        $query->delete();
        return back()->with('success', 'Cours retiré du panier');
    }

    /**
     * Appliquer un code promo au panier.
     */
    public function applyCoupon(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:50']);
        $code = strtoupper(trim($data['code']));

        $coupon = Coupon::where('code', $code)->where('is_active', true)->first();
        if (! $coupon) {
            return Redirect::back()->with('error', 'Code promo invalide.');
        }
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return Redirect::back()->with('error', "Ce code promo n'est pas encore valide.");
        }
        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return Redirect::back()->with('error', 'Ce code promo a expire.');
        }
        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return Redirect::back()->with('error', "Ce code promo a atteint sa limite d'utilisation.");
        }

        $items = $this->getCartItems($request);
        if ($items->isEmpty()) {
            return Redirect::back()->with('error', 'Votre panier est vide.');
        }

        $subtotal = (float) $items->sum(fn ($i) => max(0.0, (float) $i->price - (float) $i->discount_amount));
        if ($coupon->min_amount && $subtotal < (float) $coupon->min_amount) {
            return Redirect::back()->with('error', 'Ce code promo requiert un montant minimum de ' . number_format((float) $coupon->min_amount, 2) . ' USD.');
        }

        $request->session()->put('cart.coupon_code', $coupon->code);
        foreach ($items as $item) {
            $item->update(['coupon_code' => $coupon->code]);
        }

        return Redirect::back()->with('cart.applied_coupon', $coupon->code)->with('success', "Code promo {$coupon->code} applique.");
    }

    /**
     * Retirer le code promo du panier.
     */
    public function removeCoupon(Request $request)
    {
        $request->session()->forget('cart.coupon_code');
        $query = CartItem::query();
        $this->scopeCartQuery($request, $query);
        $query->update(['coupon_code' => null]);

        return Redirect::back()->with('success', 'Code promo retire.');
    }

    /**
     * Toggle wishlist.
     */
    public function toggleWishlist(Request $request, Course $course)
    {
        $user = $request->user();
        $existing = Wishlist::where('user_id', $user->id)->where('course_id', $course->id)->first();
        if ($existing) {
            $existing->delete();
            return back()->with('success', 'Retiré des favoris');
        }
        Wishlist::create(['user_id' => $user->id, 'course_id' => $course->id]);
        return back()->with('success', 'Ajouté aux favoris');
    }

    /**
     * Checkout.
     */
    public function checkout(Request $request)
    {
        $user = $request->user();
        $countryCode = $this->resolveCountryCode($request, $user);
        $country     = CountriesService::find($countryCode) ?? CountriesService::find('CD');

        [$items, $subtotalUsd, $coupon, $couponDiscount, $taxUsd, $totalUsd, $taxRate] = $this->resolveCartTotals($request);
        $paidItems = $items->filter(fn ($i) => (float) ($i->price - $i->discount_amount) > 0)->values();
        if ($paidItems->isEmpty()) {
            return redirect()->route('marketplace.cart')->with('info', 'Votre panier ne contient que des cours gratuits : utilisez "S\'inscrire gratuitement".');
        }

        $subtotal = CountriesService::convertFromUsd($subtotalUsd, $country['code'] ?? 'CD');
        $tax      = CountriesService::convertFromUsd($taxUsd, $country['code'] ?? 'CD');
        $total    = CountriesService::convertFromUsd($totalUsd, $country['code'] ?? 'CD');
        $localCouponDiscount = CountriesService::convertFromUsd($couponDiscount, $country['code'] ?? 'CD');

        $providers = $this->paymentService->enabledProviders()->map(fn ($p) => [
            'provider' => $p->provider,
            'label'    => PaymentGatewaySetting::PROVIDERS[$p->provider] ?? $p->provider,
            'sandbox'  => $p->sandbox,
        ]);

        return Inertia::render('Marketplace/Checkout', [
            'cart' => [
                'items'          => $items,
                'paid'           => $paidItems,
                'subtotal'       => $subtotalUsd,
                'subtotal_local' => $subtotal['amount'],
                'tax'            => $taxUsd,
                'tax_local'      => $tax['amount'],
                'tax_rate'       => $taxRate,
                'coupon_code'    => $coupon?->code,
                'coupon_discount' => $couponDiscount,
                'coupon_discount_local' => $localCouponDiscount['amount'],
                'total'          => $totalUsd,
                'total_local'    => $total['amount'],
                'currency'       => $total['currency'],
                'symbol'         => $total['symbol'],
            ],
            'coupon'            => $coupon ? ['code' => $coupon->code, 'description' => $coupon->description] : null,
            'items'             => $items,
            'subtotal'          => $subtotalUsd,
            'total'             => $totalUsd,
            'currency'          => $total['currency'],
            'symbol'            => $total['symbol'],
            'providers'         => $providers,
            'countries'         => CountriesService::options(),
            'country'           => $country,
            'recommendedProvider' => $this->paymentService->recommendProvider(
                $country['code'] ?? 'CD',
                $totalUsd,
            ),
            'user' => $user ? [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'country_code' => $user->country_code ?? $country['code'] ?? 'CD',
            ] : null,
        ]);
    }

    /**
     * Demande de validation par un administrateur pour tous les articles PAYANTS du panier.
     * Crée des enrollments en statut PENDING (sans paiement) qui seront validés ou rejetés manuellement.
     */
    public function requestEnrollmentCart(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401, 'Vous devez être connecté.');

        $data = $request->validate([
            'note'         => 'nullable|string|max:500',
            'country_code' => 'nullable|string|size:2',
            'phone_number' => 'nullable|string|max:30',
        ]);

        $items = $this->getCartItems($request)->load('course');
        $paidItems = $items->filter(fn ($i) => (float) ($i->price - $i->discount_amount) > 0);

        if ($paidItems->isEmpty()) {
            return back()->with('error', 'Aucun cours payant dans votre panier.');
        }

        $requested = 0;
        $skipped   = 0;
        $requestedEnrollments = [];
        $requestedCourses = [];

        foreach ($paidItems as $item) {
            $course = $item->course;
            if (! $course) { $skipped++; continue; }

            if ($user->isEnrolledIn($course) || $user->hasPendingEnrollment($course)) {
                $skipped++;
                continue;
            }

            try {
                $enrollment = $user->requestEnrollment($course, [
                    'notes' => ($data['note'] ?? null) . "\n[Origine] Demande via panier (sans paiement en ligne) — validation admin requise.",
                ]);
                $requestedEnrollments[] = $enrollment;
                $requestedCourses[] = $course;
                $item->delete(); // Retirer du panier
                $requested++;
            } catch (\Throwable $e) {
                \Log::warning('requestEnrollmentCart failed for course ' . $course->id . ': ' . $e->getMessage());
                $skipped++;
            }
        }

        // Notifier tous les admins / finance managers
        if ($requestedEnrollments) {
            try {
                $admins = \App\Models\User::role(['admin', 'superadmin', 'finance-manager'])->get();
                foreach ($admins as $admin) {
                    foreach ($requestedEnrollments as $enrollment) {
                        $admin->notify(new \App\Notifications\NewEnrollmentRequestNotification($enrollment, [
                            'payment_method' => 'manual_validation',
                            'note' => $data['note'] ?? null,
                            'phone_number' => $data['phone_number'] ?? null,
                            'country_code' => $data['country_code'] ?? null,
                        ]));
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('Notify admin (requestEnrollmentCart) failed: ' . $e->getMessage());
            }

            // Emails transactionnels (un par cours)
            foreach ($requestedEnrollments as $idx => $enrollment) {
                $course = $requestedCourses[$idx] ?? $enrollment->course;
                if ($course) {
                    $this->sendEnrollmentRequestEmails($user, $course, [
                        'payment_method' => 'manual_validation',
                        'note' => $data['note'] ?? null,
                        'phone_number' => $data['phone_number'] ?? null,
                        'transaction_id' => null,
                    ]);
                }
            }
        }

        if ($requested === 0) {
            return back()->with('error', 'Aucune demande créée (vous êtes peut-être déjà inscrit ou avez déjà une demande en attente).');
        }

        $msg = "✅ {$requested} demande(s) d'inscription envoyée(s) à l'équipe pédagogique.";
        if ($skipped > 0) $msg .= " ({$skipped} élément(s) ignoré(s) — déjà inscrit ou en attente).";
        $msg .= ' Vous recevrez une notification dès qu\'elle sera traitée.';

        return redirect()->route('student.my-enrollments')->with('success', $msg);
    }

    /**
     * Traitement checkout (initie le paiement).
     */
    public function processCheckout(Request $request)
    {
        $data = $request->validate([
            'provider' => 'required|string|in:stripe,paypal,orange_money,mpesa,airtel_money',
            'phone_number' => 'nullable|string|max:30',
            'country_code' => 'nullable|string|max:5',
            'billing_name' => 'required|string|max:200',
            'billing_email' => 'required|email|max:200',
        ]);

        $user = $request->user();
        [$items, $subtotal, $coupon, $couponDiscount, $tax, $total] = $this->resolveCartTotals($request);
        if ($items->isEmpty()) {
            return back()->with('error', 'Panier vide');
        }
        $totalRounded = round($total, 2);
        if ($totalRounded <= 0) {
            return back()->with('error', 'Votre panier ne contient que des cours gratuits : utilisez "S\'inscrire gratuitement".');
        }

        // Création de la commande en BDD (aligné schéma existant)
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-' . strtoupper(Str::random(10)),
            'status' => 'pending',
            'amount' => $totalRounded,
            'tax_amount' => $tax,
            'discount_amount' => $couponDiscount,
            'coupon_code' => $coupon?->code,
            'currency' => 'USD',
            'payment_provider' => $data['provider'],
            'phone_number' => $data['phone_number'] ?? null,
            'country_code' => $data['country_code'] ?? null,
            'billing_name' => $data['billing_name'],
            'billing_email' => $data['billing_email'],
        ]);

        // Attachement des cours à la commande
        foreach ($items as $item) {
            $order->items()->create([
                'course_id' => $item->course_id,
                'price' => $item->price,
                'discount' => $item->discount_amount,
            ]);
        }

        // Initiation du paiement
        $gateway = $this->paymentService->gateway($data['provider']);
        $intent = $gateway->createIntent($totalRounded, 'USD', [
            'order_id' => $order->id,
            'user_id' => $user->id,
        ]);

        $order->update([
            'payment_reference' => $intent['reference'],
            'status' => 'pending',
        ]);

        // Si redirection (PayPal), on redirige
        if (! empty($intent['redirect_url'])) {
            return Inertia::location($intent['redirect_url']);
        }

        // Pour Stripe / Mobile Money, on va vers la page de confirmation/paiement
        return Inertia::render('Marketplace/Payment', [
            'order' => $order,
            'intent' => $intent,
            'provider' => $data['provider'],
        ]);
    }

    /**
     * Page de confirmation post-paiement.
     */
    public function confirmation(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) abort(403);

        // Si le paiement est marqué succeeded (via webhook), on inscrit automatiquement
        if ($order->status === 'paid' || $order->status === 'succeeded') {
            $this->fulfillOrder($order);
        }

        return Inertia::render('Marketplace/Confirmation', [
            'order' => $order->load('items.course'),
        ]);
    }

    /**
     * Webhook générique pour tous les providers.
     */
    public function webhook(Request $request, string $provider)
    {
        try {
            $gateway = $this->paymentService->gateway($provider);
            $signature = $request->header('X-Signature') ?? $request->header('Stripe-Signature') ?? '';

            if (! $gateway->verifyWebhook($request->all(), $signature)) {
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $event = $gateway->parseWebhook($request->all());
            if (! $event) return response()->json(['ok' => true]);

            $order = Order::where('payment_reference', $event['reference'])->first();
            if (! $order) return response()->json(['ok' => true]);

            match ($event['status']) {
                'paid' => $this->markOrderPaid($order),
                'failed' => $order->update(['status' => 'failed']),
                'refunded' => $this->refundOrder($order, $event['amount'] ?? null),
                default => null,
            };

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            \Log::error('Webhook error: ' . $e->getMessage(), ['provider' => $provider]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Soumettre un avis.
     */
    public function review(Request $request, Course $course)
    {
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:200',
            'comment' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();
        // Vérifier que l'utilisateur a bien suivi le cours
        $enrolled = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)->exists();
        if (! $enrolled) {
            return back()->with('error', 'Vous devez être inscrit pour donner un avis.');
        }

        CourseReview::updateOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            [
                'rating' => $data['rating'],
                'title' => $data['title'] ?? null,
                'comment' => $data['comment'] ?? null,
                'is_approved' => false,
            ],
        );

        return back()->with('success', 'Merci pour votre avis ! Il sera publié après modération.');
    }

    // ── Helpers privés ───────────────────────────────────────

    private function getCartItems(Request $request)
    {
        $query = CartItem::query();
        $this->scopeCartQuery($request, $query);
        return $query->get();
    }

    private function getCartCount(Request $request): int
    {
        return $this->getCartItems($request)->count();
    }

    private function scopeCartQuery(Request $request, $query)
    {
        $user = $request->user();
        $sessionId = $request->session()->getId();
        if ($user) {
            CartItem::whereNull('user_id')
                ->where('session_id', $sessionId)
                ->update(['user_id' => $user->id, 'session_id' => null]);
            $query->where('user_id', $user->id);
        } else {
            $query->where('session_id', $sessionId)->whereNull('user_id');
        }
    }

    /**
     * Taux de TVA applicable au panier (web_settings/settings, groupe payment, cle vat_rate). Defaut 0.
     */
    private function getCartRate(): float
    {
        try {
            $rate = null;
            if (Schema::hasTable('web_settings')) {
                $rate = WebSetting::where('group', 'payment')->where('key', 'vat_rate')->value('value');
            }
            if ($rate === null && Schema::hasTable('settings')) {
                $rate = Setting::where('group', 'payment')->where('key', 'vat_rate')->value('value');
            }
            $rate = (float) ($rate ?? 0);
            return $rate > 1 ? $rate / 100 : max(0.0, $rate);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    private function getCartCoupon(Request $request): ?Coupon
    {
        try {
            $code = $request->session()->get('cart.coupon_code')
                ?: $this->getCartItems($request)->pluck('coupon_code')->filter()->first();
            if (! $code) {
                return null;
            }
            $coupon = Coupon::where('code', $code)->where('is_active', true)->first();
            if (! $coupon) {
                return null;
            }
            if ($coupon->starts_at && $coupon->starts_at->isFuture()) return null;
            if ($coupon->expires_at && $coupon->expires_at->isPast()) return null;
            if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) return null;
            return $coupon;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Calcule les totaux reels du panier (sous-total, remise coupon, TVA, total).
     *
     * @return array{0:\Illuminate\Support\Collection,1:float,2:?Coupon,3:float,4:float,5:float,6:float}
     */
    private function resolveCartTotals(Request $request): array
    {
        $items = $this->getCartItems($request)->load('course.instructor:id,name', 'course.category:id,name');
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += max(0.0, (float) $item->price - (float) $item->discount_amount);
        }
        $rate = $this->getCartRate();
        $coupon = $this->getCartCoupon($request);
        $couponDiscount = $coupon ? $this->computeCouponDiscount($coupon, $subtotal) : 0.0;
        $taxBase = max(0.0, $subtotal - $couponDiscount);
        $tax = round($taxBase * $rate, 2);
        $total = $taxBase + $tax;
        return [$items, $subtotal, $coupon, $couponDiscount, $tax, $total, $rate];
    }

    private function computeCouponDiscount(Coupon $coupon, float $subtotal): float
    {
        if ($coupon->min_amount && $subtotal < (float) $coupon->min_amount) {
            return 0.0;
        }
        $value = (float) $coupon->discount_value;
        return match ($coupon->discount_type) {
            'percent' => round($subtotal * min($value, 100) / 100, 2),
            'fixed' => min($value, $subtotal),
            default => 0.0,
        };
    }

    /**
     * Nom de la table d'avis marketplace si elle existe (course_reviews ecrit par review(), sinon reviews).
     */
    private function reviewsTable(): ?string
    {
        if (Schema::hasTable('course_reviews')) {
            return 'course_reviews';
        }
        if (Schema::hasTable('reviews')) {
            return 'reviews';
        }
        return null;
    }

    /**
     * @param array<int> $courseIds
     * @return array<int, array{avg_rating: float, count: int}>
     */
    private function reviewStats(array $courseIds): array
    {
        $table = $this->reviewsTable();
        if (! $table || empty($courseIds)) {
            return [];
        }
        return DB::table($table)
            ->whereIn('course_id', $courseIds)
            ->where('is_approved', true)
            ->selectRaw('course_id, AVG(rating) as avg_rating, COUNT(*) as c')
            ->groupBy('course_id')
            ->get()
            ->mapWithKeys(fn ($r) => [(int) $r->course_id => ['avg_rating' => round((float) $r->avg_rating, 1), 'count' => (int) $r->c]])
            ->all();
    }

    /**
     * @return array{avg_rating: ?float, count: int}
     */
    private function globalReviewStats(): array
    {
        $table = $this->reviewsTable();
        if (! $table) {
            return ['avg_rating' => null, 'count' => 0];
        }
        $row = DB::table($table)
            ->where('is_approved', true)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as c')
            ->first();
        $count = (int) ($row->c ?? 0);
        return ['avg_rating' => $count > 0 ? round((float) $row->avg_rating, 1) : null, 'count' => $count];
    }

    /**
     * @return array{avg_rating: float, count: int}
     */
    private function reviewAggregate(int $courseId): array
    {
        $table = $this->reviewsTable();
        if (! $table) {
            return ['avg_rating' => 0.0, 'count' => 0];
        }
        $row = DB::table($table)
            ->where('course_id', $courseId)
            ->where('is_approved', true)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as c')
            ->first();
        return ['avg_rating' => (float) ($row->avg_rating ?? 0), 'count' => (int) ($row->c ?? 0)];
    }

    private function flatApprovedReviews(int $courseId, int $limit = 10): array
    {
        $table = $this->reviewsTable();
        if (! $table) {
            return [];
        }
        $hasTitle = $table === 'course_reviews';
        $columns = $hasTitle
            ? ['id', 'user_id', 'rating', 'title', 'comment', 'created_at']
            : ['id', 'user_id', 'rating', 'comment', 'created_at'];
        $rows = DB::table($table)
            ->where('course_id', $courseId)
            ->where('is_approved', true)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get($columns);
        if ($rows->isEmpty()) {
            return [];
        }
        $users = DB::table('users')
            ->whereIn('id', $rows->pluck('user_id')->unique()->all())
            ->get(['id', 'name', 'avatar'])
            ->keyBy('id');
        return $rows->map(function ($r) use ($users, $hasTitle) {
            $u = $users->get($r->user_id);
            return [
                'id' => $r->id,
                'rating' => (int) $r->rating,
                'title' => $hasTitle ? ($r->title ?? null) : null,
                'comment' => $r->comment,
                'created_at' => $r->created_at,
                'user_name' => $u?->name,
                'user_avatar' => $u?->avatar,
            ];
        })->values()->all();
    }

    private function upsertCartItem(Request $request, Course $course)
    {
        $user = $request->user();
        $sessionId = $request->session()->getId();
        CartItem::updateOrCreate(
            [
                'course_id' => $course->id,
                'user_id' => $user?->id,
                'session_id' => $user ? null : $sessionId,
            ],
            [
                'price' => $course->effective_price ?? $course->price ?? 0,
                'discount_amount' => 0,
            ],
        );
    }

    /**
     * Inscrit l'utilisateur aux cours après paiement réussi.
     * Lie la commande et bascule l'inscription en ACTIVE.
     */
    private function fulfillOrder(Order $order): void
    {
        $confirmedEnrollments = [];

        DB::transaction(function () use ($order, &$confirmedEnrollments) {
            foreach ($order->items()->with('course')->get() as $item) {
                if (! $item->course) continue;

                $enrollment = Enrollment::where('user_id', $order->user_id)
                    ->where('course_id', $item->course_id)
                    ->first();

                if (! $enrollment) {
                    $enrollment = new Enrollment();
                    $enrollment->user_id = $order->user_id;
                    $enrollment->course_id = $item->course_id;
                }

                $enrollment->order_id = $order->id;
                $enrollment->status = Enrollment::STATUS_ACTIVE;
                $enrollment->progress_percent = $enrollment->progress_percent ?? 0;
                $enrollment->enrolled_at = $enrollment->enrolled_at ?? now();
                $enrollment->reviewed_at = now();
                $enrollment->reviewed_by = $enrollment->reviewed_by ?? $order->user_id;
                $enrollment->save();

                // Notification
                $order->user?->notify(new CourseEnrolledNotification(
                    courseTitle: $item->course->title,
                    courseId: $item->course_id,
                    courseImage: $item->course->thumbnail_path,
                ));

                $confirmedEnrollments[] = $enrollment;
            }

            // Vider le panier
            CartItem::where('user_id', $order->user_id)->delete();

            if ($order->coupon_code) {
                Coupon::where('code', $order->coupon_code)->increment('used_count');
            }
        });

        try {
            session()->forget('cart.coupon_code');
        } catch (\Throwable $e) {
            \Log::debug('Session indisponible pour oublier le coupon panier: ' . $e->getMessage());
        }

        // Emails transactionnels hors transaction (évite les envois en cas de rollback)
        try {
            \App\Events\PaymentReceived::dispatch($order);
            foreach ($confirmedEnrollments as $enrollment) {
                \App\Events\EnrollmentConfirmed::dispatch($enrollment);
            }
        } catch (\Throwable $e) {
            \Log::warning('Post-payment emails failed: ' . $e->getMessage());
        }
    }

    /**
     * Envoie les emails transactionnels d'une demande d'inscription :
     * confirmation à l'étudiant + alerte détaillée aux administrateurs.
     */
    private function sendEnrollmentRequestEmails(\App\Models\User $user, \App\Models\Course $course, array $data): void
    {
        try {
            $emailService = app(\App\Services\Email\EmailTemplateService::class);
            $methodLabels = [
                'orange_money' => 'Orange Money', 'mpesa' => 'M-Pesa', 'airtel_money' => 'Airtel Money',
                'stripe' => 'Carte bancaire (Stripe)', 'paypal' => 'PayPal',
                'on_site' => 'Paiement sur place', 'manual' => 'Validation manuelle',
                'manual_validation' => 'Sans paiement en ligne (validation manuelle)',
            ];
            $paymentLabel = $methodLabels[$data['payment_method'] ?? ''] ?? ($data['payment_method'] ?? 'Non spécifiée');

            // Les adresses BCC sont configurées sur le template 'enrollment_request_received'
            // (page admin → Modèles d'emails → modifier le modèle).
            $emailService->sendTemplatedEmail('enrollment_request_received', $user, [
                'course_name' => $course->title,
                'course_url' => route('marketplace.show', $course->slug ?? $course->id),
                'payment_method' => $paymentLabel,
                'request_date' => now()->format('d/m/Y'),
            ]);

            $admins = \App\Models\User::role(['admin', 'superadmin', 'finance-manager'])->get();
            foreach ($admins as $admin) {
                $emailService->sendTemplatedEmail('enrollment_request_admin', $admin, [
                    'admin_name' => $admin->name,
                    'student_name' => $user->name,
                    'student_email' => $user->email,
                    'course_name' => $course->title,
                    'payment_method' => $paymentLabel,
                    'phone_number' => $data['phone_number'] ?? 'Non fourni',
                    'transaction_id' => $data['transaction_id'] ?? 'N/A',
                    'note' => $data['note'] ?? 'Aucune note',
                    'admin_url' => route('admin.enrollments.index'),
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('Enrollment request emails failed: ' . $e->getMessage());
        }
    }

    private function markOrderPaid(Order $order): void
    {
        $order->update(['status' => 'paid', 'paid_at' => now()]);
        Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'provider' => $order->payment_provider,
            'reference' => $order->payment_reference,
            'amount' => $order->amount,
            'status' => 'succeeded',
            'paid_at' => now(),
        ]);
        $this->fulfillOrder($order);
    }

    private function refundOrder(Order $order, ?float $amount): void
    {
        $order->update(['status' => 'refunded', 'refunded_at' => now()]);
    }
}