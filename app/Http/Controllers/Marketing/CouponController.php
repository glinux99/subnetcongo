<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon; // Modèle à créer
use App\Models\Course; // Modèle existant (supposons)
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CouponController extends Controller
{
    /**
     * Constructeur pour appliquer les middlewares de permissions.
     */
    public function __construct()
    {
        // Exemple de permissions
        // $this->middleware('can:view-coupons')->only(['index', 'show', 'export']);
        // $this->middleware('can:manage-coupons')->except(['index', 'show', 'export']);
    }

    /**
     * Affiche la liste des coupons avec filtres, recherche et pagination.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $type = $request->input('type');
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $perPage = $request->input('per_page', 15);

        $query = Coupon::query()
            ->when($search, function ($q, $search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($status, function ($q, $status) {
                if ($status === 'expired') {
                    $q->where('expires_at', '<', now());
                } elseif ($status === 'active') {
                    $q->where(function($subQ) {
                        $subQ->where('status', 'active')
                             ->where(function($dateQ) {
                                 $dateQ->where('expires_at', '>=', now())
                                       ->orWhereNull('expires_at');
                             });
                    });
                } else {
                    $q->where('status', $status);
                }
            })
            ->when($type, function ($q, $type) {
                $q->where('type', $type);
            });

        // Tri
        $validSorts = ['code', 'type', 'value', 'used_count', 'created_at', 'expires_at'];
        if (in_array($sortField, $validSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $coupons = $query->paginate($perPage)->withQueryString();

        // Statistiques pour l'en-tête
        $stats = [
            'total' => Coupon::count(),
            'active' => Coupon::where('status', 'active')->where(fn($q) => $q->where('expires_at', '>=', now())->orWhereNull('expires_at'))->count(),
            'inactive' => Coupon::where('status', 'inactive')->count(),
            'total_usage' => Coupon::sum('used_count'),
        ];

        return Inertia::render('Marketing/Coupons', [
            'coupons' => $coupons,
            'filters' => $request->all(['search', 'status', 'type', 'sort_field', 'sort_direction', 'per_page']),
            'stats' => $stats,
        ]);
    }

    /**
     * Affiche le formulaire de création d'un coupon.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        // On pourrait charger des données associables, comme des cours ou des produits
        $courses = Course::where('status', 'published')->select('id', 'title')->get();

        return Inertia::render('Marketing/Coupons', [
            'courses' => $courses,
        ]);
    }

    /**
     * Enregistre un nouveau coupon.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50|unique:coupons,code',
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:percentage,fixed_amount',
            'value' => 'required|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
            'status' => 'required|in:active,inactive',
            'applies_to_courses' => 'nullable|array',
            'applies_to_courses.*' => 'exists:courses,id',
        ]);

        DB::beginTransaction();
        try {
            $couponCode = $validated['code'] ?: $this->generateUniqueCode();

            $coupon = Coupon::create([
                'code' => strtoupper($couponCode),
                'description' => $validated['description'],
                'type' => $validated['type'],
                'value' => $validated['value'],
                'usage_limit' => $validated['usage_limit'],
                'usage_limit_per_user' => $validated['usage_limit_per_user'],
                'used_count' => 0,
                'expires_at' => $validated['expires_at'] ? Carbon::parse($validated['expires_at']) : null,
                'status' => $validated['status'],
            ]);

            // Lier le coupon à des cours spécifiques si la relation existe
            if (!empty($validated['applies_to_courses'])) {
                // $coupon->courses()->sync($validated['applies_to_courses']);
            }

            DB::commit();

            return Redirect::route('marketing.coupons.index')
                ->with('success', 'Coupon créé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()
                ->with('error', 'Erreur lors de la création du coupon: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Affiche les détails d'un coupon.
     *
     * @param Coupon $coupon
     * @return \Inertia\Response
     */
    public function show(Coupon $coupon)
    {
        // Simule la récupération de l'historique d'utilisation
        // $coupon->load(['usages.user', 'courses']);
        $usages = []; // CouponUsage::where('coupon_id', $coupon->id)->with('user')->latest()->paginate(10);

        return Inertia::render('Marketing/Coupons', [
            'coupon' => $coupon,
            'usages' => $usages,
        ]);
    }

    /**
     * Affiche le formulaire d'édition d'un coupon.
     *
     * @param Coupon $coupon
     * @return \Inertia\Response
     */
    public function edit(Coupon $coupon)
    {
        // $coupon->load('courses:id'); // Charger les IDs des cours liés
        $courses = Course::where('status', 'published')->select('id', 'title')->get();

        return Inertia::render('Marketing/Coupons', [
            'coupon' => $coupon,
            'courses' => $courses,
        ]);
    }

    /**
     * Met à jour un coupon.
     *
     * @param Request $request
     * @param Coupon $coupon
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons')->ignore($coupon->id)],
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:percentage,fixed_amount',
            'value' => 'required|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'status' => 'required|in:active,inactive',
            'applies_to_courses' => 'nullable|array',
            'applies_to_courses.*' => 'exists:courses,id',
        ]);

        DB::beginTransaction();
        try {
            $coupon->update($validated);

            // Mettre à jour les cours liés
            if ($request->has('applies_to_courses')) {
                // $coupon->courses()->sync($validated['applies_to_courses']);
            }

            DB::commit();

            return Redirect::route('marketing.coupons.index')
                ->with('success', 'Coupon mis à jour.');

        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()
                ->with('error', 'Erreur de mise à jour: ' . $e->getMessage());
        }
    }

    /**
     * Supprime un coupon.
     *
     * @param Coupon $coupon
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Coupon $coupon)
    {
        try {
            // On pourrait vérifier si le coupon a été utilisé avant de le supprimer
            // if ($coupon->used_count > 0) {
            //     return Redirect::back()->with('error', 'Impossible de supprimer un coupon qui a déjà été utilisé. Désactivez-le plutôt.');
            // }
            $coupon->delete();

            return Redirect::route('marketing.coupons.index')
                ->with('success', 'Coupon supprimé.');
        } catch (\Exception $e) {
            return Redirect::back()
                ->with('error', 'Impossible de supprimer le coupon.');
        }
    }

    /**
     * Suppression en masse.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:coupons,id']);

        $count = Coupon::whereIn('id', $request->ids)->count();
        Coupon::whereIn('id', $request->ids)->delete();

        return Redirect::back()->with('success', "$count coupons ont été supprimés.");
    }

    /**
     * Exporte les données des coupons en CSV.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $fileName = 'coupons_export_' . date('Y-m-d') . '.csv';

        // On réutilise une logique de filtrage similaire à l'index
        $query = Coupon::query();
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }
        $coupons = $query->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Code', 'Description', 'Type', 'Valeur', 'Statut', 'Limite d\'utilisation', 'Utilisations', 'Expire le', 'Créé le'];

        $callback = function() use($coupons, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($coupons as $coupon) {
                $row['ID'] = $coupon->id;
                $row['Code'] = $coupon->code;
                $row['Description'] = $coupon->description;
                $row['Type'] = $coupon->type;
                $row['Valeur'] = $coupon->value;
                $row['Statut'] = $coupon->status;
                $row['Limite d\'utilisation'] = $coupon->usage_limit ?? 'Illimitée';
                $row['Utilisations'] = $coupon->used_count;
                $row['Expire le'] = $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : 'N/A';
                $row['Créé le'] = $coupon->created_at->format('Y-m-d');

                fputcsv($file, array_values($row));
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Génère un code de coupon unique.
     *
     * @param string $prefix
     * @param int $length
     * @return string
     */
    private function generateUniqueCode(string $prefix = '', int $length = 8): string
    {
        do {
            $code = $prefix . strtoupper(Str::random($length));
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }
}
