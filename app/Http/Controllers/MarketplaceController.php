<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Wishlist;
use App\Notifications\CourseEnrolledNotification;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $courses = $query->paginate(12)->withQueryString();

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
        ]);
    }

    /**
     * Page détail d'un cours.
     */
    public function show(Request $request, Course $course)
    {
        $course->load([
            'instructor:id,name,bio,profile_photo_url',
            'category:id,name',
            'modules.lessons' => fn ($q) => $q->orderBy('order'),
            'reviews' => fn ($q) => $q->where('is_approved', true)->latest()->limit(10),
            'reviews.user:id,name,profile_photo_url',
        ]);

        $stats = [
            'students' => $course->enrollments()->count(),
            'rating' => round($course->reviews()->avg('rating') ?? 0, 1),
            'reviews_count' => $course->reviews()->count(),
            'lessons_count' => $course->lessons()->count(),
            'duration_hours' => round(($course->duration_minutes ?? 0) / 60, 1),
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
            ->limit(4)->get();

        return Inertia::render('Marketplace/Show', [
            'course' => $course,
            'stats' => $stats,
            'isEnrolled' => $isEnrolled,
            'enrollmentStatus' => $enrollmentStatus,
            'inWishlist' => $inWishlist,
            'related' => $related,
            'cartCount' => $this->getCartCount($request),
        ]);
    }

    /**
     * Panier.
     */
    public function cart(Request $request)
    {
        $items = $this->getCartItems($request)->load('course.instructor:id,name', 'course.category:id,name');

        return Inertia::render('Marketplace/Cart', [
            'items' => $items,
            'subtotal' => $items->sum(fn ($i) => $i->price - $i->discount_amount),
            'cartCount' => $items->count(),
        ]);
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

        DB::transaction(function () use ($user, $course) {
            $user->enrollIn($course, [], Enrollment::STATUS_ACTIVE);
            // Initialise la progression à 0
            \App\Models\UserProgress::firstOrCreate(
                ['user_id' => $user->id, 'course_id' => $course->id],
                ['progress_percent' => 0, 'last_accessed_at' => now()]
            );
        });

        try {
            $user->notify(new CourseEnrolledNotification(
                courseTitle: $course->title,
                courseId: $course->id,
                courseImage: $course->thumbnail_path,
            ));
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
            'phone_number' => 'nullable|string|max:30',
            'country_code' => 'nullable|string|max:5',
            'note' => 'nullable|string|max:500',
        ]);

        $enrollment = $user->requestEnrollment($course, [
            'notes' => $data['note'] ?? null,
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

        return back()->with('success', 'Votre demande d\'inscription a été envoyée. L\'administrateur la traitera après vérification de votre paiement (' . strtoupper(str_replace('_', ' ', $data['payment_method'])) . ').');
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
        $items = $this->getCartItems($request)->load('course');
        if ($items->isEmpty()) {
            return redirect()->route('marketplace.cart')->with('error', 'Votre panier est vide.');
        }

        $subtotal = $items->sum(fn ($i) => $i->price - $i->discount_amount);
        $tax = round($subtotal * 0.0, 2); // 0% pour l'instant, à configurer par pays
        $total = $subtotal + $tax;

        $providers = $this->paymentService->enabledProviders()->map(fn ($p) => [
            'provider' => $p->provider,
            'label' => PaymentGatewaySetting::PROVIDERS[$p->provider] ?? $p->provider,
            'sandbox' => $p->sandbox,
        ]);

        return Inertia::render('Marketplace/Checkout', [
            'items' => $items,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'providers' => $providers,
            'recommendedProvider' => $this->paymentService->recommendProvider(
                $request->input('country_code', 'CD'),
                $total,
            ),
        ]);
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
        $items = $this->getCartItems($request)->load('course');
        if ($items->isEmpty()) {
            return back()->with('error', 'Panier vide');
        }

        $subtotal = $items->sum(fn ($i) => $i->price - $i->discount_amount);
        $tax = round($subtotal * 0.0, 2);
        $total = $subtotal + $tax;

        // Création de la commande en BDD (aligné schéma existant)
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-' . strtoupper(Str::random(10)),
            'status' => 'pending',
            'amount' => $total,
            'tax_amount' => $tax,
            'discount_amount' => 0,
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
        $intent = $gateway->createIntent($total, 'USD', [
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
            $query->where(function ($q) use ($user, $sessionId) {
                $q->where('user_id', $user->id)->orWhere('session_id', $sessionId);
            });
        } else {
            $query->where('session_id', $sessionId);
        }
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
        DB::transaction(function () use ($order) {
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
                $enrollment->reviewed_by = $enrollment->reviewed_by ?? 1; // 1 = système
                $enrollment->save();

                // Notification
                $order->user?->notify(new CourseEnrolledNotification(
                    courseTitle: $item->course->title,
                    courseId: $item->course_id,
                    courseImage: $item->course->thumbnail_path,
                ));
            }

            // Vider le panier
            CartItem::where('user_id', $order->user_id)->delete();
        });
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