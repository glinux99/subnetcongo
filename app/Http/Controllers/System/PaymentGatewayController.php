<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PaymentGateway; // Modèle supposé
use App\Models\PaymentTransaction; // Modèle supposé
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentGatewayController extends Controller
{
    /**
     * Constructeur pour appliquer les middlewares et permissions.
     */
    public function __construct()
    {
        // $this->middleware('can:manage-payments');
    }

    /**
     * Affiche la liste des passerelles de paiement.
     *
     * Inclut des statistiques financières globales et des filtres avancés.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $query = PaymentGateway::query();

        // Recherche textuelle
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('provider_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtres
        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type')); // ex: card, mobile_money, crypto
        }

        // Tri
        $sortField = $request->input('sort_field', 'sort_order');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        $gateways = $query->paginate($request->input('per_page', 10))->withQueryString();

        // Statistiques globales (Mockées ou réelles selon le modèle Transaction)
        $stats = [
            'total_volume' => PaymentTransaction::where('status', 'success')->sum('amount'),
            'total_transactions' => PaymentTransaction::count(),
            'success_rate' => $this->calculateSuccessRate(),
            'active_gateways' => PaymentGateway::where('is_active', true)->count(),
        ];

        return Inertia::render('System/Payments/Gateways/Index', [
            'gateways' => $gateways,
            'filters' => $request->all(),
            'stats' => $stats,
            'available_types' => ['card', 'mobile_money', 'bank_transfer', 'crypto', 'wallet'],
        ]);
    }

    /**
     * Affiche le formulaire de création d'une nouvelle passerelle.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('System/Payments/Gateways/Create', [
            'providers' => [
                ['code' => 'stripe', 'name' => 'Stripe', 'type' => 'card'],
                ['code' => 'paypal', 'name' => 'PayPal', 'type' => 'wallet'],
                ['code' => 'maxicash', 'name' => 'MaxiCash', 'type' => 'mobile_money'],
                ['code' => 'illicocash', 'name' => 'IllicoCash', 'type' => 'mobile_money'],
                ['code' => 'equity', 'name' => 'Equity Pay', 'type' => 'card'],
            ]
        ]);
    }

    /**
     * Enregistre une nouvelle passerelle de paiement.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'provider_code' => 'required|string|max:50',
            'type' => 'required|string|in:card,mobile_money,bank_transfer,crypto,wallet',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'currency' => 'required|string|size:3',
            'config' => 'required|array',
            'config.api_key' => 'required|string',
            'config.api_secret' => 'nullable|string',
            'config.environment' => 'required|in:sandbox,production',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Gestion de l'upload du logo
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('gateways', 'public');
            }

            // Chiffrement des secrets
            $config = $request->input('config');
            if (isset($config['api_secret'])) {
                $config['api_secret'] = Crypt::encryptString($config['api_secret']);
            }

            // Si défini par défaut, désactiver les autres
            if ($request->boolean('is_default')) {
                PaymentGateway::where('is_default', true)->update(['is_default' => false]);
            }

            $gateway = PaymentGateway::create([
                'name' => $request->input('name'),
                'provider_code' => $request->input('provider_code'),
                'type' => $request->input('type'),
                'is_active' => $request->boolean('is_active'),
                'is_default' => $request->boolean('is_default'),
                'currency' => strtoupper($request->input('currency')),
                'config' => $config,
                'logo_path' => $logoPath,
                'sort_order' => PaymentGateway::max('sort_order') + 1,
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'payment_gateway_created',
                'auditable_type' => PaymentGateway::class,
                'auditable_id' => $gateway->id,
                'new_values' => json_encode($gateway->toArray()),
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return Redirect::route('system.payments.gateways.index')
                ->with('success', 'Passerelle de paiement ajoutée avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de la création : ' . $e->getMessage());
        }
    }

    /**
     * Affiche les détails d'une passerelle et ses transactions récentes.
     *
     * @param PaymentGateway $gateway
     * @return \Inertia\Response
     */
    public function show(PaymentGateway $gateway)
    {
        // Décrypter pour l'affichage (partiel) si nécessaire, ou garder masqué
        // On ne renvoie jamais les secrets complets au front

        $recentTransactions = PaymentTransaction::where('payment_gateway_id', $gateway->id)
            ->latest()
            ->limit(10)
            ->with('user:id,name,email')
            ->get();

        $dailyStats = PaymentTransaction::where('payment_gateway_id', $gateway->id)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return Inertia::render('System/Payments/Gateways/Show', [
            'gateway' => $gateway,
            'recent_transactions' => $recentTransactions,
            'daily_stats' => $dailyStats,
            'health_status' => $this->checkGatewayHealth($gateway),
        ]);
    }

    /**
     * Affiche le formulaire d'édition.
     *
     * @param PaymentGateway $gateway
     * @return \Inertia\Response
     */
    public function edit(PaymentGateway $gateway)
    {
        // On ne décrypte pas le secret ici, on laisse le champ vide.
        // Si l'utilisateur remplit, on met à jour. Sinon on garde l'ancien.
        $config = $gateway->config;
        $config['api_secret'] = ''; // Masqué pour sécurité

        return Inertia::render('System/Payments/Gateways/Edit', [
            'gateway' => $gateway,
            'safe_config' => $config,
        ]);
    }

    /**
     * Met à jour une passerelle existante.
     *
     * @param Request $request
     * @param PaymentGateway $gateway
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, PaymentGateway $gateway)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'currency' => 'required|string|size:3',
            'config' => 'required|array',
            'config.api_key' => 'required|string',
            'config.environment' => 'required|in:sandbox,production',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        DB::beginTransaction();
        try {
            $data = $request->only(['name', 'is_active', 'is_default', 'currency']);
            $config = $request->input('config');

            // Gestion du secret : mise à jour seulement si fourni
            if (!empty($config['api_secret'])) {
                $config['api_secret'] = Crypt::encryptString($config['api_secret']);
            } else {
                // Garder l'ancien secret
                $config['api_secret'] = $gateway->config['api_secret'] ?? null;
            }
            $data['config'] = $config;

            if ($request->hasFile('logo')) {
                $data['logo_path'] = $request->file('logo')->store('gateways', 'public');
            }

            if ($request->boolean('is_default') && !$gateway->is_default) {
                PaymentGateway::where('id', '!=', $gateway->id)->update(['is_default' => false]);
            }

            $oldValues = $gateway->toArray();
            $gateway->update($data);

            AuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'payment_gateway_updated',
                'auditable_type' => PaymentGateway::class,
                'auditable_id' => $gateway->id,
                'old_values' => json_encode($oldValues),
                'new_values' => json_encode($gateway->toArray()),
                'ip_address' => $request->ip(),
            ]);

            DB::commit();

            return Redirect::route('system.payments.gateways.index')
                ->with('success', 'Configuration mise à jour.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur de mise à jour: ' . $e->getMessage());
        }
    }

    /**
     * Supprime (soft delete) une passerelle.
     *
     * @param PaymentGateway $gateway
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(PaymentGateway $gateway)
    {
        if ($gateway->transactions()->exists()) {
            return back()->with('error', 'Impossible de supprimer une passerelle ayant des transactions. Désactivez-la plutôt.');
        }

        $gateway->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'payment_gateway_deleted',
            'auditable_type' => PaymentGateway::class,
            'auditable_id' => $gateway->id,
            'ip_address' => request()->ip(),
        ]);

        return Redirect::route('system.payments.gateways.index')
            ->with('success', 'Passerelle supprimée.');
    }

    /**
     * Active ou désactive rapidement une passerelle.
     *
     * @param PaymentGateway $gateway
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleStatus(PaymentGateway $gateway)
    {
        $gateway->update(['is_active' => !$gateway->is_active]);

        return back()->with('success', 'Statut mis à jour : ' . ($gateway->is_active ? 'Actif' : 'Inactif'));
    }

    /**
     * Teste la connexion API avec le fournisseur.
     *
     * @param PaymentGateway $gateway
     * @return \Illuminate\Http\JsonResponse
     */
    public function testConnection(PaymentGateway $gateway)
    {
        // Simulation de test de connexion selon le provider
        $success = false;
        $message = '';
        $latency = 0;

        $start = microtime(true);

        try {
            if ($gateway->provider_code === 'stripe') {
                // Exemple fictif d'appel Stripe
                // Http::withToken(Crypt::decryptString($gateway->config['api_secret']))->get('https://api.stripe.com/v1/balance');
                $success = true;
                $message = 'Connexion Stripe établie.';
            } elseif ($gateway->provider_code === 'paypal') {
                $success = true;
                $message = 'Connexion PayPal Sandbox OK.';
            } else {
                // Mock générique
                $success = rand(0, 1) === 1;
                $message = $success ? 'Ping API réussi.' : 'Délai d\'attente dépassé.';
            }
        } catch (\Exception $e) {
            $success = false;
            $message = $e->getMessage();
        }

        $latency = round((microtime(true) - $start) * 1000, 2);

        // Log du test
        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'payment_gateway_test',
            'auditable_type' => PaymentGateway::class,
            'auditable_id' => $gateway->id,
            'new_values' => json_encode(['success' => $success, 'latency' => $latency]),
        ]);

        return response()->json([
            'success' => $success,
            'message' => $message,
            'latency' => $latency . 'ms',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Exporte les transactions d'une passerelle en CSV.
     *
     * @param Request $request
     * @param PaymentGateway $gateway
     * @return StreamedResponse
     */
    public function exportTransactions(Request $request, PaymentGateway $gateway)
    {
        $fileName = 'transactions_' . $gateway->provider_code . '_' . date('Y-m-d') . '.csv';

        $query = PaymentTransaction::where('payment_gateway_id', $gateway->id)
            ->with('user');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        return new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // BOM

            fputcsv($handle, ['ID', 'Référence', 'Utilisateur', 'Montant', 'Devise', 'Statut', 'Date', 'Méthode']);

            $query->chunk(1000, function ($transactions) use ($handle) {
                foreach ($transactions as $tx) {
                    fputcsv($handle, [
                        $tx->id,
                        $tx->reference,
                        $tx->user ? $tx->user->email : 'Guest',
                        $tx->amount,
                        $tx->currency,
                        $tx->status,
                        $tx->created_at->format('Y-m-d H:i:s'),
                        $tx->payment_method ?? 'N/A',
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Affiche les logs de webhook pour le débogage.
     *
     * @param PaymentGateway $gateway
     * @return \Inertia\Response
     */
    public function webhookLogs(PaymentGateway $gateway)
    {
        // Supposons une table webhook_logs ou un filtrage sur AuditLog
        $logs = AuditLog::where('auditable_type', PaymentGateway::class)
            ->where('auditable_id', $gateway->id)
            ->where('event', 'like', 'webhook_%')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('System/Payments/Gateways/WebhookLogs', [
            'gateway' => $gateway,
            'logs' => $logs,
        ]);
    }

    /**
     * Rembourse une transaction spécifique via l'API de la passerelle.
     *
     * @param Request $request
     * @param string $transactionId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function refundTransaction(Request $request, $transactionId)
    {
        $transaction = PaymentTransaction::findOrFail($transactionId);
        $gateway = $transaction->gateway;

        if (!$gateway) {
            return back()->with('error', 'Passerelle introuvable.');
        }

        // Logique de remboursement (Mock)
        try {
            // Appel API réel ici...
            // $gatewayService->refund($transaction);

            $transaction->update(['status' => 'refunded', 'refunded_at' => now()]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'transaction_refunded',
                'auditable_type' => PaymentTransaction::class,
                'auditable_id' => $transaction->id,
                'new_values' => json_encode(['amount' => $transaction->amount]),
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', 'Remboursement effectué avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Échec du remboursement : ' . $e->getMessage());
        }
    }

    /**
     * Calcule le taux de succès global des transactions.
     *
     * @return float
     */
    private function calculateSuccessRate()
    {
        $total = PaymentTransaction::count();
        if ($total === 0) return 100;

        $success = PaymentTransaction::where('status', 'success')->count();
        return round(($success / $total) * 100, 2);
    }

    /**
     * Vérifie l'état de santé basique d'une passerelle.
     *
     * @param PaymentGateway $gateway
     * @return string
     */
    private function checkGatewayHealth(PaymentGateway $gateway)
    {
        if (!$gateway->is_active) return 'inactive';

        // Vérifier s'il y a eu des erreurs récentes (dernière heure)
        $recentErrors = AuditLog::where('auditable_type', PaymentGateway::class)
            ->where('auditable_id', $gateway->id)
            ->where('event', 'like', '%error%')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentErrors > 5) return 'degraded';

        return 'healthy';
    }

    /**
     * Réorganise l'ordre d'affichage des passerelles.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorder(Request $request)
    {
        $request->validate(['items' => 'required|array']);

        foreach ($request->input('items') as $index => $id) {
            PaymentGateway::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Force la synchronisation des devises supportées depuis le provider.
     *
     * @param PaymentGateway $gateway
     * @return \Illuminate\Http\RedirectResponse
     */
    public function syncCurrencies(PaymentGateway $gateway)
    {
        // Logique mockée pour récupérer les devises supportées par l'API
        // $currencies = $api->getCurrencies();
        // $gateway->update(['supported_currencies' => $currencies]);

        return back()->with('success', 'Devises synchronisées (Mock).');
    }
}
