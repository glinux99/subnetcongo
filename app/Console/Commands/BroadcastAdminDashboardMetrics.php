<?php

namespace App\Console\Commands;

use App\Events\AdminDashboardMetricsUpdated;
use App\Support\InfrastructureMetrics;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BroadcastAdminDashboardMetrics extends Command
{
    protected $signature = 'dashboard:broadcast-metrics {--force : Envoyer même si les métriques n’ont pas changé}';

    protected $description = 'Diffuse par WebSocket les métriques infra du dashboard administrateur';

    public function handle(): int
    {
        if (config('broadcasting.default') === 'null' && ! $this->option('force')) {
            $this->warn('BROADCAST_DRIVER=null : rien à diffuser.');
            return self::SUCCESS;
        }

        // Force une mesure fraîche ; le contrôleur garde un cache 30 s pour les pages.
        InfrastructureMetrics::forget();
        $infra = InfrastructureMetrics::collect();

        $hash = hash('sha256', json_encode([
            'platform' => $infra['platform'] ?? null,
            'cpu' => $infra['cpu'] ?? null,
            'ram' => $infra['ram'] ?? null,
            'disk' => $infra['disk'] ?? null,
            'status' => $infra['status'] ?? null,
        ]));

        if (! $this->option('force') && Cache::get('admin_dashboard_metrics_hash') === $hash) {
            $this->info('Métriques inchangées — WebSocket non déclenché.');
            return self::SUCCESS;
        }

        try {
            broadcast(new AdminDashboardMetricsUpdated($infra));
            Cache::forever('admin_dashboard_metrics_hash', $hash);
            $this->info('Métriques diffusées sur le canal privé admin.dashboard.metrics.');
        } catch (\Throwable $e) {
            Log::warning('Échec broadcast métriques dashboard', ['error' => $e->getMessage()]);
            $this->error('Broadcast impossible : ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
