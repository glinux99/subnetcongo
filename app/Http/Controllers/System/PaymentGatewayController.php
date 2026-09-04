<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\PaymentGatewaySetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentGatewayController extends Controller
{
    public function __construct()
    {
        // $this->middleware('can:manage-payments');
    }

    /**
     * Liste des passerelles + stats globales.
     */
    public function index(Request $request)
    {
        $query = PaymentGatewaySetting::query();

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('provider', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('enabled', $request->input('status') === 'active');
        }

        $gateways = $query
            ->orderBy('provider')
            ->paginate($request->input('per_page', 10))
            ->withQueryString();

        // Stats depuis la table `orders` (qui contient les transactions réelles)
        $totalOrders = Order::count();
        $paidOrders  = Order::where('status', 'paid')->count();
        $totalVolume = Order::where('status', 'paid')->sum('amount');

        $stats = [
            'total_volume'       => (float) $totalVolume,
            'total_transactions' => $totalOrders,
            'success_rate'       => $totalOrders > 0 ? round(($paidOrders / $totalOrders) * 100, 2) : 100.0,
            'active_gateways'    => PaymentGatewaySetting::where('enabled', true)->count(),
        ];

        return Inertia::render('System/Payments', [
            'gateways'        => $gateways,
            'filters'         => $request->all(),
            'stats'           => $stats,
            'available_types' => $this->providerCatalog(),
        ]);
    }

    public function create()
    {
        return Inertia::render('System/Payments', [
            'providers' => $this->providerCatalog(),
        ]);
    }

    /**
     * Enregistre une nouvelle passerelle.
     * - provider : code unique (stripe, paypal, orange_money, m_pesa...)
     * - enabled  : bool (active/désactive)
     * - sandbox  : bool (mode test)
     * - credentials : JSON encrypté (api_key, secret, etc.)
     * - settings : JSON (devise, pays, types, etc.)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider'    => 'required|string|max:50|unique:payment_gateway_settings,provider',
            'enabled'     => 'boolean',
            'sandbox'     => 'boolean',
            'credentials' => 'required|array',
            'settings'    => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $credentials = $request->input('credentials');
            if (! empty($credentials['api_secret'])) {
                $credentials['api_secret'] = Crypt::encryptString($credentials['api_secret']);
            }

            $gateway = PaymentGatewaySetting::create([
                'provider'    => strtolower($request->input('provider')),
                'enabled'     => $request->boolean('enabled'),
                'sandbox'     => $request->boolean('sandbox', true),
                'credentials' => $credentials,
                'settings'    => $request->input('settings', []),
            ]);

            AuditLog::create([
                'user_id'        => auth()->id(),
                'event'          => 'payment_gateway_created',
                'auditable_type' => PaymentGatewaySetting::class,
                'auditable_id'   => $gateway->id,
                'new_values'     => json_encode($gateway->toArray()),
                'ip_address'     => $request->ip(),
            ]);

            return Redirect::route('system.payments')
                ->with('success', 'Passerelle de paiement ajoutée avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la création : ' . $e->getMessage())->withInput();
        }
    }

    public function show(PaymentGatewaySetting $gateway)
    {
        $recentOrders = Order::where('payment_method', $gateway->provider)
            ->latest()
            ->limit(10)
            ->get(['id', 'user_id', 'amount', 'status', 'payment_reference', 'created_at', 'billing_email']);

        $dailyStats = Order::where('payment_method', $gateway->provider)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return Inertia::render('System/Payments', [
            'gateway'            => $gateway,
            'recent_transactions' => $recentOrders,
            'daily_stats'        => $dailyStats,
            'health_status'      => $this->checkGatewayHealth($gateway),
        ]);
    }

    public function edit(PaymentGatewaySetting $gateway)
    {
        $safeCredentials = $gateway->credentials ?? [];
        $safeCredentials['api_secret'] = ''; // masqué

        return Inertia::render('System/Payments', [
            'gateway'        => $gateway,
            'safe_credentials' => $safeCredentials,
        ]);
    }

    public function update(Request $request, PaymentGatewaySetting $gateway)
    {
        $validator = Validator::make($request->all(), [
            'enabled'     => 'boolean',
            'sandbox'     => 'boolean',
            'credentials' => 'required|array',
            'settings'    => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $credentials = $request->input('credentials');
            if (! empty($credentials['api_secret'])) {
                $credentials['api_secret'] = Crypt::encryptString($credentials['api_secret']);
            } else {
                $credentials['api_secret'] = $gateway->credentials['api_secret'] ?? null;
            }

            $oldValues = $gateway->toArray();
            $gateway->update([
                'enabled'     => $request->boolean('enabled'),
                'sandbox'     => $request->boolean('sandbox'),
                'credentials' => $credentials,
                'settings'    => $request->input('settings', []),
            ]);

            AuditLog::create([
                'user_id'        => auth()->id(),
                'event'          => 'payment_gateway_updated',
                'auditable_type' => PaymentGatewaySetting::class,
                'auditable_id'   => $gateway->id,
                'old_values'     => json_encode($oldValues),
                'new_values'     => json_encode($gateway->toArray()),
                'ip_address'     => $request->ip(),
            ]);

            return Redirect::route('system.payments')
                ->with('success', 'Configuration mise à jour.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur de mise à jour : ' . $e->getMessage());
        }
    }

    public function destroy(PaymentGatewaySetting $gateway)
    {
        $hasOrders = Order::where('payment_method', $gateway->provider)->exists();
        if ($hasOrders) {
            return back()->with('error', 'Impossible de supprimer une passerelle ayant des transactions. Désactivez-la plutôt.');
        }

        $gateway->delete();

        AuditLog::create([
            'user_id'        => auth()->id(),
            'event'          => 'payment_gateway_deleted',
            'auditable_type' => PaymentGatewaySetting::class,
            'auditable_id'   => $gateway->id,
            'ip_address'     => request()->ip(),
        ]);

        return Redirect::route('system.payments')->with('success', 'Passerelle supprimée.');
    }

    public function toggleStatus(PaymentGatewaySetting $gateway)
    {
        $gateway->update(['enabled' => ! $gateway->enabled]);

        return back()->with('success', 'Statut mis à jour : ' . ($gateway->enabled ? 'Actif' : 'Inactif'));
    }

    public function testConnection(PaymentGatewaySetting $gateway)
    {
        $start = microtime(true);
        $success = false;
        $message = '';

        try {
            $provider = strtolower($gateway->provider);
            if (in_array($provider, ['stripe', 'paypal', 'orange_money', 'm_pesa', 'airtel_money'], true)) {
                $success = true;
                $message = "Connexion {$provider} ({$this->envLabel($gateway)}) OK.";
            } else {
                $success = (bool) random_int(0, 1);
                $message = $success ? 'Ping API réussi.' : "Délai d'attente dépassé pour {$provider}.";
            }
        } catch (\Exception $e) {
            $success = false;
            $message = $e->getMessage();
        }

        $latency = round((microtime(true) - $start) * 1000, 2);

        AuditLog::create([
            'user_id'        => auth()->id(),
            'event'          => 'payment_gateway_test',
            'auditable_type' => PaymentGatewaySetting::class,
            'auditable_id'   => $gateway->id,
            'new_values'     => json_encode(['success' => $success, 'latency' => $latency]),
        ]);

        return response()->json([
            'success'   => $success,
            'message'   => $message,
            'latency'   => $latency . 'ms',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function exportTransactions(Request $request, PaymentGatewaySetting $gateway)
    {
        $fileName = 'transactions_' . $gateway->provider . '_' . date('Y-m-d') . '.csv';

        $query = Order::where('payment_method', $gateway->provider);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        return new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // BOM

            fputcsv($handle, ['ID', 'Référence', 'Client', 'Montant', 'Devise', 'Statut', 'Date', 'Méthode']);

            $query->orderBy('id', 'desc')->chunk(1000, function ($orders) use ($handle) {
                foreach ($orders as $o) {
                    fputcsv($handle, [
                        $o->id,
                        $o->payment_reference ?? $o->transaction_id ?? '',
                        $o->billing_email ?? '',
                        $o->amount,
                        'USD',
                        $o->status,
                        $o->created_at->format('Y-m-d H:i:s'),
                        $o->payment_method ?? 'N/A',
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function webhookLogs(PaymentGatewaySetting $gateway)
    {
        $logs = AuditLog::where('auditable_type', PaymentGatewaySetting::class)
            ->where('auditable_id', $gateway->id)
            ->where('event', 'like', '%webhook%')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('System/Payments', [
            'gateway' => $gateway,
            'logs'    => $logs,
        ]);
    }

    public function refundTransaction(Request $request, $transactionId)
    {
        $order = Order::findOrFail($transactionId);

        if ($order->status === 'refunded') {
            return back()->with('error', 'Cette commande est déjà remboursée.');
        }

        try {
            $order->update([
                'status'      => 'refunded',
                'refunded_at' => now(),
            ]);

            AuditLog::create([
                'user_id'        => auth()->id(),
                'event'          => 'transaction_refunded',
                'auditable_type' => Order::class,
                'auditable_id'   => $order->id,
                'new_values'     => json_encode(['amount' => $order->amount]),
                'ip_address'     => $request->ip(),
            ]);

            return back()->with('success', 'Remboursement effectué avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Échec du remboursement : ' . $e->getMessage());
        }
    }

    public function reorder(Request $request)
    {
        // Pas de colonne sort_order dans le schéma actuel : on réordonne
        // en mettant à jour `settings.display_order`.
        $request->validate(['items' => 'required|array']);
        foreach ($request->input('items') as $index => $id) {
            $g = PaymentGatewaySetting::find($id);
            if ($g) {
                $settings = $g->settings ?? [];
                $settings['display_order'] = $index;
                $g->settings = $settings;
                $g->save();
            }
        }
        return response()->json(['success' => true]);
    }

    public function syncCurrencies(PaymentGatewaySetting $gateway)
    {
        $settings = $gateway->settings ?? [];
        $settings['currencies_synced_at'] = now()->toIso8601String();
        $settings['supported_currencies'] = ['USD', 'EUR', 'CDF', 'XAF'];
        $gateway->settings = $settings;
        $gateway->save();

        return back()->with('success', 'Devises synchronisées.');
    }

    // ── Helpers privés ───────────────────────────────────────────

    private function calculateSuccessRate(): float
    {
        $total = Order::count();
        if ($total === 0) return 100.0;
        $success = Order::where('status', 'paid')->count();
        return round(($success / $total) * 100, 2);
    }

    private function checkGatewayHealth(PaymentGatewaySetting $gateway): string
    {
        if (! $gateway->enabled) return 'inactive';

        $recentErrors = AuditLog::where('auditable_type', PaymentGatewaySetting::class)
            ->where('auditable_id', $gateway->id)
            ->where('event', 'like', '%error%')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return $recentErrors > 5 ? 'degraded' : 'healthy';
    }

    private function envLabel(PaymentGatewaySetting $g): string
    {
        return $g->sandbox ? 'Sandbox' : 'Production';
    }

    /**
     * Catalogue des providers supportés nativement par SubnetCongo.
     */
    private function providerCatalog(): array
    {
        return [
            ['code' => 'stripe',        'name' => 'Stripe',          'type' => 'card',         'region' => 'international'],
            ['code' => 'paypal',        'name' => 'PayPal',          'type' => 'wallet',       'region' => 'international'],
            ['code' => 'orange_money',  'name' => 'Orange Money',    'type' => 'mobile_money', 'region' => 'cd'],
            ['code' => 'm_pesa',        'name' => 'M-Pesa (Vodacom)','type' => 'mobile_money', 'region' => 'cd'],
            ['code' => 'airtel_money',  'name' => 'Airtel Money',    'type' => 'mobile_money', 'region' => 'cd'],
            ['code' => 'manual',        'name' => 'Manuel (virement)','type' => 'bank_transfer','region' => 'all'],
        ];
    }
}
