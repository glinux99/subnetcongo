<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $search  = trim((string) $request->input('search', ''));
        $status  = $request->input('status');
        $type    = $request->input('type');
        $perPage = min(max((int) $request->input('per_page', 10), 5), 100);

        $query = Coupon::query()
            ->when($search, function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', function ($q) {
                $q->where('is_active', true)
                  ->where(fn ($d) => $d->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
            })
            ->when($status === 'inactive', function ($q) {
                $q->where('is_active', false);
            })
            ->when($status === 'expiring', function ($q) {
                $q->where('is_active', true)
                  ->whereNotNull('expires_at')
                  ->whereBetween('expires_at', [now(), now()->addDays(7)]);
            })
            ->when(in_array($type, ['percent', 'fixed'], true), function ($q) use ($type) {
                $q->where('discount_type', $type);
            })
            ->latest();

        $coupons = $query->paginate($perPage)->withQueryString();

        $stats = [
            'total'         => Coupon::count(),
            'active'        => Coupon::where('is_active', true)
                ->where(fn ($d) => $d->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
                ->count(),
            'total_usage'   => (int) Coupon::sum('used_count'),
            'expiring_soon' => Coupon::where('is_active', true)
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [now(), now()->addDays(7)])
                ->count(),
        ];

        return Inertia::render('Marketing/Coupons', [
            'coupons' => $coupons,
            'filters' => $request->only(['search', 'status', 'type', 'per_page']),
            'stats'   => $stats,
        ]);
    }

    public function create()
    {
        return redirect()->route('marketing.coupons.index');
    }

    public function store(Request $request)
    {
        $validated = $this->validateCoupon($request);

        $validated['code'] = strtoupper(trim($validated['code'] ?? ''));
        if ($validated['code'] === '') {
            $validated['code'] = $this->generateUniqueCode();
        }

        $isActive = (bool) ($validated['is_active'] ?? true);

        Coupon::create([
            'code'              => $validated['code'],
            'description'       => $validated['description'] ?? null,
            'discount_type'     => $validated['discount_type'],
            'discount_value'    => $validated['discount_value'],
            'usage_limit'       => $validated['usage_limit'] ?? null,
            'max_uses_per_user' => $validated['max_uses_per_user'] ?? 1,
            'min_amount'        => $validated['min_amount'] ?? null,
            'starts_at'         => $validated['starts_at'] ?? null,
            'expires_at'        => $validated['expires_at'] ?? null,
            'used_count'        => 0,
            'is_active'         => $isActive,
            'status'            => $isActive ? 'active' : 'inactive',
        ]);

        return redirect()->route('marketing.coupons.index')
            ->with('success', 'Coupon créé avec succès.');
    }

    public function show(Coupon $coupon)
    {
        return redirect()->route('marketing.coupons.index');
    }

    public function edit(Coupon $coupon)
    {
        return redirect()->route('marketing.coupons.index');
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $this->validateCoupon($request, $coupon);

        $validated['code'] = strtoupper(trim($validated['code'] ?? $coupon->code));
        if ($validated['code'] === '') {
            $validated['code'] = $coupon->code;
        }

        $isActive = (bool) ($validated['is_active'] ?? $coupon->is_active);

        $coupon->update([
            'code'              => $validated['code'],
            'description'       => $validated['description'] ?? null,
            'discount_type'     => $validated['discount_type'],
            'discount_value'    => $validated['discount_value'],
            'usage_limit'       => $validated['usage_limit'] ?? null,
            'max_uses_per_user' => $validated['max_uses_per_user'] ?? 1,
            'min_amount'        => $validated['min_amount'] ?? null,
            'starts_at'         => $validated['starts_at'] ?? null,
            'expires_at'        => $validated['expires_at'] ?? null,
            'is_active'         => $isActive,
            'status'            => $isActive ? 'active' : 'inactive',
        ]);

        return redirect()->route('marketing.coupons.index')
            ->with('success', 'Coupon mis à jour.');
    }

    public function destroy(Coupon $coupon)
    {
        if ($coupon->used_count > 0) {
            $coupon->update(['is_active' => false, 'status' => 'inactive']);

            return redirect()->route('marketing.coupons.index')
                ->with('success', "Le coupon {$coupon->code} a déjà été utilisé {$coupon->used_count} fois : il a été archivé (désactivé) plutôt que supprimé.");
        }

        $coupon->delete();

        return redirect()->route('marketing.coupons.index')
            ->with('success', 'Coupon supprimé.');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:coupons,id',
        ]);

        $coupons = Coupon::whereIn('id', $validated['ids'])->get();
        $deleted = 0;
        $archived = 0;

        foreach ($coupons as $coupon) {
            if ($coupon->used_count > 0) {
                $coupon->update(['is_active' => false, 'status' => 'inactive']);
                $archived++;
            } else {
                $coupon->delete();
                $deleted++;
            }
        }

        return redirect()->route('marketing.coupons.index')
            ->with('success', "{$deleted} coupon(s) supprimé(s), {$archived} archivé(s) (déjà utilisés).");
    }

    public function export(Request $request)
    {
        $fileName = 'coupons_export_' . now()->format('Y-m-d') . '.csv';

        $query = Coupon::query()->latest();
        if (in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('status', $request->input('status'));
        }
        if (in_array($request->input('type'), ['percent', 'fixed'], true)) {
            $query->where('discount_type', $request->input('type'));
        }
        $coupons = $query->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$fileName}",
        ];

        $columns = ['ID', 'Code', 'Description', 'Type', 'Valeur', 'Min. achat', 'Limite globale', 'Limite / utilisateur', 'Utilisations', 'Statut', 'Actif', 'Début', 'Fin', 'Créé le'];

        $callback = function () use ($coupons, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($coupons as $coupon) {
                fputcsv($file, [
                    $coupon->id,
                    $coupon->code,
                    $coupon->description,
                    $coupon->discount_type,
                    $coupon->discount_value,
                    $coupon->min_amount ?? 'Aucun',
                    $coupon->usage_limit ?? 'Illimitée',
                    $coupon->max_uses_per_user ?? 'Illimitée',
                    $coupon->used_count,
                    $coupon->status,
                    $coupon->is_active ? 'oui' : 'non',
                    $coupon->starts_at?->format('Y-m-d H:i'),
                    $coupon->expires_at?->format('Y-m-d H:i'),
                    $coupon->created_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    private function validateCoupon(Request $request, ?Coupon $coupon = null): array
    {
        return $request->validate([
            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('coupons', 'code')->ignore($coupon?->id),
            ],
            'description'       => 'nullable|string|max:255',
            'discount_type'     => 'required|in:percent,fixed',
            'discount_value'    => [
                'required', 'numeric', 'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('discount_type') === 'percent' && (float) $value > 100) {
                        $fail('La valeur d’un coupon en pourcentage ne peut pas dépasser 100.');
                    }
                },
            ],
            'usage_limit'       => 'nullable|integer|min:1',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'min_amount'        => 'nullable|numeric|min:0',
            'starts_at'         => 'nullable|date',
            'expires_at'        => 'nullable|date|after_or_equal:starts_at',
            'is_active'         => 'boolean',
        ], [], [
            'code'           => 'code',
            'discount_value' => 'valeur',
        ]);
    }

    private function generateUniqueCode(string $prefix = 'SERPENT-', int $length = 6): string
    {
        do {
            $code = $prefix . strtoupper(Str::random($length));
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }
}
