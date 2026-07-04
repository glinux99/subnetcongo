<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Affiliate; // Modèle à créer
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AffiliateController extends Controller
{
    /**
     * Constructeur pour appliquer les middlewares de permissions.
     */
    public function __construct()
    {
        // Exemple de permissions
        // $this->middleware('can:view-affiliates')->only(['index', 'show', 'export']);
        // $this->middleware('can:manage-affiliates')->except(['index', 'show', 'export']);
    }

    /**
     * Affiche la liste des affiliés avec filtres, recherche et pagination.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $perPage = $request->input('per_page', 15);

        $query = $this->buildAffiliateQuery($request);

        // Tri
        $validSorts = ['created_at', 'status', 'commission_rate', 'clicks', 'conversions', 'total_earnings'];
        if (in_array($sortField, $validSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $affiliates = $query->paginate($perPage)->withQueryString();

        // Statistiques pour l'en-tête
        $stats = [
            'total' => Affiliate::count(),
            'active' => Affiliate::where('status', 'active')->count(),
            'pending' => Affiliate::where('status', 'pending')->count(),
            'total_earnings' => Affiliate::sum('total_earnings'), // Supposant que ce champ existe
        ];

        return Inertia::render('Marketing/Affiliates/Index', [
            'affiliates' => $affiliates,
            'filters' => $request->all(['search', 'status', 'sort_field', 'sort_direction', 'per_page']),
            'stats' => $stats,
        ]);
    }

    /**
     * Affiche le formulaire de création d'un affilié.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        // On ne propose que les utilisateurs qui ne sont pas déjà affiliés
        $users = User::whereDoesntHave('affiliate')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Marketing/Affiliates/Create', [
            'users' => $users,
        ]);
    }

    /**
     * Enregistre un nouvel affilié.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|unique:affiliates,user_id',
            'code' => 'nullable|string|max:50|unique:affiliates,code',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:active,pending,inactive',
            'payment_email' => 'required|email',
        ]);

        DB::beginTransaction();
        try {
            $user = User::findOrFail($validated['user_id']);

            $affiliateCode = $validated['code'] ?: $this->generateUniqueCode($user->name);

            $affiliate = Affiliate::create([
                'user_id' => $user->id,
                'code' => $affiliateCode,
                'commission_rate' => $validated['commission_rate'],
                'status' => $validated['status'],
                'payment_email' => $validated['payment_email'],
                'clicks' => 0,
                'conversions' => 0,
                'total_earnings' => 0,
                'balance' => 0,
            ]);

            // Assigner un rôle à l'utilisateur
            // $user->assignRole('affiliate');

            // Envoyer une notification de bienvenue
            // $user->notify(new WelcomeAffiliate($affiliate));

            DB::commit();

            return Redirect::route('marketing.affiliates.index')
                ->with('success', 'Affilié créé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()
                ->with('error', 'Erreur lors de la création de l\'affilié: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Affiche les détails d'un affilié.
     *
     * @param Affiliate $affiliate
     * @return \Inertia\Response
     */
    public function show(Affiliate $affiliate)
    {
        $affiliate->load(['user', 'referrals.user', 'payouts']); // Simule les relations

        // Simulation de données de performance
        $performance_chart = [
            'labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
            'clicks' => [120, 150, 80, 200, 250, 180],
            'conversions' => [12, 18, 7, 25, 30, 22],
        ];

        return Inertia::render('Marketing/Affiliates/Show', [
            'affiliate' => $affiliate,
            'performance_chart' => $performance_chart,
        ]);
    }

    /**
     * Affiche le formulaire d'édition d'un affilié.
     *
     * @param Affiliate $affiliate
     * @return \Inertia\Response
     */
    public function edit(Affiliate $affiliate)
    {
        $affiliate->load('user');
        return Inertia::render('Marketing/Affiliates/Edit', [
            'affiliate' => $affiliate,
        ]);
    }

    /**
     * Met à jour un affilié.
     *
     * @param Request $request
     * @param Affiliate $affiliate
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Affiliate $affiliate)
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50', Rule::unique('affiliates')->ignore($affiliate->id)],
            'commission_rate' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:active,pending,inactive,banned',
            'payment_email' => 'required|email',
            'notes' => 'nullable|string',
        ]);

        try {
            $affiliate->update($validated);

            return Redirect::route('marketing.affiliates.index')
                ->with('success', 'Affilié mis à jour.');

        } catch (\Exception $e) {
            return Redirect::back()
                ->with('error', 'Erreur de mise à jour: ' . $e->getMessage());
        }
    }

    /**
     * Supprime (désactive) un affilié.
     *
     * @param Affiliate $affiliate
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Affiliate $affiliate)
    {
        try {
            // On pourrait choisir de supprimer ou de désactiver
            // $affiliate->delete();
            $affiliate->update(['status' => 'inactive']);


            // Retirer le rôle
            // $affiliate->user->removeRole('affiliate');

            return Redirect::route('marketing.affiliates.index')
                ->with('success', 'Affilié désactivé.');
        } catch (\Exception $e) {
            return Redirect::back()
                ->with('error', 'Impossible de désactiver l\'affilié.');
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
        $request->validate(['ids' => 'required|array', 'ids.*' => 'exists:affiliates,id']);

        $count = Affiliate::whereIn('id', $request->ids)->count();
        Affiliate::whereIn('id', $request->ids)->update(['status' => 'inactive']);

        return Redirect::back()->with('success', "$count affiliés ont été désactivés.");
    }

    /**
     * Approuve une demande d'affiliation.
     *
     * @param Affiliate $affiliate
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve(Affiliate $affiliate)
    {
        if ($affiliate->status !== 'pending') {
            return Redirect::back()->with('warning', 'Cet affilié n\'est pas en attente d\'approbation.');
        }

        $affiliate->update(['status' => 'active']);

        // Envoyer une notification d'approbation
        // $affiliate->user->notify(new AffiliateApproved($affiliate));

        return Redirect::back()->with('success', 'Affilié approuvé et activé.');
    }

    /**
     * Rejette une demande d'affiliation.
     *
     * @param Request $request
     * @param Affiliate $affiliate
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(Request $request, Affiliate $affiliate)
    {
        $request->validate(['reason' => 'nullable|string|max:1000']);

        if ($affiliate->status !== 'pending') {
            return Redirect::back()->with('warning', 'Cet affilié n\'est pas en attente.');
        }

        $reason = $request->input('reason', 'Votre demande ne correspond pas à nos critères actuels.');

        // Envoyer une notification de rejet avec la raison
        // $affiliate->user->notify(new AffiliateRejected($affiliate, $reason));

        // On supprime la demande rejetée
        $affiliate->delete();

        return Redirect::route('marketing.affiliates.index')
            ->with('success', 'Demande d\'affiliation rejetée et supprimée.');
    }

    /**
     * Exporte les données des affiliés en CSV.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $fileName = 'affiliates_export_' . date('Y-m-d') . '.csv';

        // On réutilise la logique de filtrage de l'index
        $affiliates = $this->buildAffiliateQuery($request)->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Nom', 'Email', 'Code', 'Taux Commission (%)', 'Statut', 'Clics', 'Conversions', 'Gains Totaux', 'Solde Actuel', 'Inscrit le'];

        $callback = function() use($affiliates, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($affiliates as $aff) {
                $row['ID'] = $aff->id;
                $row['Nom'] = $aff->user->name;
                $row['Email'] = $aff->user->email;
                $row['Code'] = $aff->code;
                $row['Taux Commission (%)'] = $aff->commission_rate;
                $row['Statut'] = $aff->status;
                $row['Clics'] = $aff->clicks;
                $row['Conversions'] = $aff->conversions;
                $row['Gains Totaux'] = $aff->total_earnings;
                $row['Solde Actuel'] = $aff->balance;
                $row['Inscrit le'] = $aff->created_at->format('Y-m-d');

                fputcsv($file, array_values($row));
            }
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Retourne les statistiques pour un tableau de bord.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        $topAffiliates = Affiliate::with('user:id,name')
            ->orderBy('total_earnings', 'desc')
            ->take(5)
            ->get(['id', 'user_id', 'total_earnings']);

        $recentSignups = Affiliate::with('user:id,name')
            ->latest()
            ->take(5)
            ->get(['id', 'user_id', 'created_at']);

        $data = [
            'total_affiliates' => Affiliate::count(),
            'pending_applications' => Affiliate::where('status', 'pending')->count(),
            'total_revenue_generated' => 0, // Calculer via les ventes liées
            'total_commission_paid' => 0, // Calculer via les paiements
            'top_affiliates' => $topAffiliates,
            'recent_signups' => $recentSignups,
        ];

        return response()->json($data);
    }

    /**
     * Affiche la page de gestion des paiements pour un affilié.
     *
     * @param Affiliate $affiliate
     * @return \Inertia\Response
     */
    public function payouts(Affiliate $affiliate)
    {
        $affiliate->load('user');

        // Simule la récupération des paiements
        $payouts = []; // AffiliatePayout::where('affiliate_id', $affiliate->id)->latest()->paginate(10);

        return Inertia::render('Marketing/Affiliates/Payouts', [
            'affiliate' => $affiliate,
            'payouts' => $payouts,
            'payout_methods' => ['PayPal', 'Virement Bancaire'], // Exemple
        ]);
    }

    /**
     * Génère un nouveau paiement pour un ou plusieurs affiliés.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generatePayout(Request $request)
    {
        $request->validate([
            'affiliate_ids' => 'required|array',
            'affiliate_ids.*' => 'exists:affiliates,id',
            'payout_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $affiliates = Affiliate::whereIn('id', $request->affiliate_ids)
            ->where('balance', '>', 0) // Ne payer que ceux avec un solde positif
            ->get();

        if ($affiliates->isEmpty()) {
            return Redirect::back()->with('warning', 'Aucun affilié sélectionné n\'a de solde à payer.');
        }

        DB::beginTransaction();
        try {
            foreach ($affiliates as $affiliate) {
                $amount = $affiliate->balance;

                // Créer l'enregistrement du paiement (modèle AffiliatePayout à créer)
                /*
                AffiliatePayout::create([
                    'affiliate_id' => $affiliate->id,
                    'amount' => $amount,
                    'status' => 'processing',
                    'payout_date' => $request->payout_date,
                    'notes' => $request->notes,
                ]);
                */

                // Mettre à jour le solde de l'affilié
                $affiliate->update([
                    'balance' => 0,
                ]);
            }
            DB::commit();

            return Redirect::back()->with('success', 'Paiements générés pour ' . $affiliates->count() . ' affilié(s).');

        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Erreur lors de la génération des paiements: ' . $e->getMessage());
        }
    }

    /**
     * Génère un code d'affiliation unique.
     *
     * @param string $name
     * @return string
     */
    private function generateUniqueCode(string $name): string
    {
        $baseCode = Str::slug($name, '');
        $code = $baseCode;
        $counter = 1;

        while (Affiliate::where('code', $code)->exists()) {
            $code = $baseCode . $counter;
            $counter++;
        }

        return $code;
    }

    /**
     * Construit la requête de base pour les affiliés avec les filtres communs.
     *
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildAffiliateQuery(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        return Affiliate::with(['user:id,name,email,profile_photo_path'])
            ->when($search, function ($q, $search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQ) use ($search) {
                      $userQ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                  });
            })
            ->when($status, function ($q, $status) {
                $q->where('status', $status);
            });
    }
}
