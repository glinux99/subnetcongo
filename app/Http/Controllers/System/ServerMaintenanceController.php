<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ServerMaintenance; // Modèle supposé pour planifier les tâches
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServerMaintenanceController extends Controller
{
    public function __construct()
    {
        // $this->middleware('role:Super Admin');
    }

    /**
     * Tableau de bord de maintenance serveur.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        // Récupérer les tâches planifiées
        $scheduledTasks = ServerMaintenance::where('status', 'pending')
            ->orderBy('scheduled_at')
            ->get();

        // Historique récent
        $history = ServerMaintenance::where('status', '!=', 'pending')
            ->latest()
            ->limit(10)
            ->get();

        // État du système (Mocké si fonctions PHP désactivées)
        $systemHealth = $this->getSystemHealth();

        // État du mode maintenance Laravel
        $isDown = app()->isDownForMaintenance();

        return Inertia::render('System/Maintenance', [
            'scheduled_tasks' => $scheduledTasks,
            'history' => $history,
            'system_health' => $systemHealth,
            'is_maintenance_mode' => $isDown,
            'laravel_version' => app()->version(),
            'php_version' => phpversion(),
        ]);
    }

    /**
     * Planifie une nouvelle tâche de maintenance.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:security,performance,update,backup,database,other',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'severity' => 'required|in:low,medium,high,critical',
            'scheduled_at' => 'required|date',
            'duration_minutes' => 'nullable|integer|min:1',
            'affected_services' => 'nullable|array',
            'downtime_estimate' => 'nullable|string|max:50',
        ]);

        $task = ServerMaintenance::create([
            'type' => $request->input('type'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'severity' => $request->input('severity', 'medium'),
            'scheduled_at' => Carbon::parse($request->input('scheduled_at')),
            'duration_minutes' => $request->input('duration_minutes'),
            'affected_services' => $request->input('affected_services', []),
            'downtime_estimate' => $request->input('downtime_estimate'),
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'maintenance_scheduled',
            'auditable_type' => ServerMaintenance::class,
            'auditable_id' => $task->id,
            'new_values' => json_encode($task->toArray()),
        ]);

        return back()->with('success', 'Tâche de maintenance planifiée.');
    }

    /**
     * Exécute une commande Artisan immédiatement.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function runTask(Request $request)
    {
        $request->validate([
            'task' => 'required|in:optimize,cache:clear,view:clear,config:clear,route:clear,queue:restart,migrate',
        ]);

        $command = $request->input('task');

        // Sécurité : empêcher les commandes dangereuses sans confirmation explicite (géré par frontend)
        if ($command === 'migrate' && !$request->boolean('force')) {
            return back()->with('warning', 'La migration nécessite une confirmation explicite.');
        }

        try {
            Artisan::call($command);
            $output = Artisan::output();

            AuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'artisan_command_executed',
                'auditable_type' => 'System',
                'auditable_id' => 0,
                'new_values' => json_encode(['command' => $command, 'output' => $output]),
            ]);

            return back()->with('success', "Commande '$command' exécutée avec succès.")->with('output', $output);
        } catch (\Exception $e) {
            return back()->with('error', "Erreur lors de l'exécution : " . $e->getMessage());
        }
    }

    /**
     * Bascule le mode maintenance de l'application.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleMaintenanceMode(Request $request)
    {
        $shouldBeDown = $request->boolean('down');
        $secret = $request->input('secret', null);
        $message = $request->input('message', 'Site en maintenance. Veuillez revenir plus tard.');

        try {
            if ($shouldBeDown) {
                $params = ['--message' => $message];
                if ($secret) {
                    $params['--secret'] = $secret;
                }
                Artisan::call('down', $params);
                $msg = 'Application mise en mode maintenance.';
            } else {
                Artisan::call('up');
                $msg = 'Application remise en ligne.';
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'event' => $shouldBeDown ? 'maintenance_mode_enabled' : 'maintenance_mode_disabled',
                'auditable_type' => 'System',
                'auditable_id' => 0,
                'ip_address' => $request->ip(),
            ]);

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Affiche les logs système (laravel.log).
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function logs(Request $request)
    {
        $logFile = storage_path('logs/laravel.log');
        $logs = [];

        if (File::exists($logFile)) {
            // Lire les 1000 dernières lignes (simplifié)
            $content = File::get($logFile);
            $lines = explode("\n", $content);
            $logs = array_slice(array_reverse($lines), 0, 200); // 200 dernières lignes
        }

        return Inertia::render('System/Maintenance', [
            'logs' => $logs,
            'file_size' => File::exists($logFile) ? round(File::size($logFile) / 1024 / 1024, 2) . ' MB' : '0 MB',
            'view' => 'logs',
        ]);
    }

    /**
     * Télécharge le fichier de log.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadLog()
    {
        $logFile = storage_path('logs/laravel.log');
        if (!File::exists($logFile)) {
            return back()->with('error', 'Fichier de log introuvable.');
        }
        return response()->download($logFile, 'laravel-' . date('Y-m-d-H-i') . '.log');
    }

    /**
     * Vide le fichier de log.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clearLog()
    {
        $logFile = storage_path('logs/laravel.log');
        File::put($logFile, '');

        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'system_log_cleared',
            'auditable_type' => 'System',
            'auditable_id' => 0,
        ]);

        return back()->with('success', 'Logs système effacés.');
    }

    /**
     * Lance une sauvegarde de la base de données.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function backupDatabase()
    {
        // Nécessite le package spatie/laravel-backup configuré
        try {
            Artisan::call('backup:run', ['--only-db' => true]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'event' => 'database_backup_created',
                'auditable_type' => 'System',
                'auditable_id' => 0,
            ]);

            return back()->with('success', 'Sauvegarde de la base de données lancée en arrière-plan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur backup : ' . $e->getMessage());
        }
    }

    /**
     * Annule une tâche planifiée.
     *
     * @param ServerMaintenance $maintenance
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(ServerMaintenance $maintenance)
    {
        if ($maintenance->status !== 'pending') {
            return back()->with('error', 'Seules les tâches en attente peuvent être annulées.');
        }

        $maintenance->delete();

        return back()->with('success', 'Tâche de maintenance annulée.');
    }

    /**
     * Affiche les informations PHP (phpinfo).
     *
     * @return \Inertia\Response
     */
    public function phpInfo()
    {
        ob_start();
        phpinfo();
        $phpinfo = ob_get_clean();

        // Nettoyage basique pour l'affichage dans une iframe ou div
        $phpinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $phpinfo);

        return Inertia::render('System/Maintenance', [
            'info' => $phpinfo,
            'view' => 'phpinfo',
        ]);
    }

    /**
     * Récupère les métriques de santé du serveur.
     *
     * @return array
     */
    private function getSystemHealth()
    {
        $diskTotal = @disk_total_space('/');
        $diskFree = @disk_free_space('/');
        $diskUsed = $diskTotal ? ($diskTotal - $diskFree) : 0;

        // sys_getloadavg() n'existe que sur Linux. Sur Windows on retourne [0,0,0].
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];

        // RAM (Linux /proc/meminfo, fallback Windows via wmic / GlobalMemoryStatus)
        $memory = ['total' => 0, 'free' => 0, 'used' => 0, 'percent' => 0];
        if (false !== ($memInfo = @file_get_contents("/proc/meminfo"))) {
            preg_match("/MemTotal:\s+(\d+)/", $memInfo, $total);
            preg_match("/MemFree:\s+(\d+)/", $memInfo, $free);
            $memory['total'] = (int) ($total[1] ?? 0) * 1024;
            $memory['free']  = (int) ($free[1] ?? 0) * 1024;
            $memory['used']  = $memory['total'] - $memory['free'];
            $memory['percent'] = $memory['total'] > 0 ? round(($memory['used'] / $memory['total']) * 100, 1) : 0;
        } elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = 'wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value 2>nul';
            $output = @shell_exec($cmd);
            if ($output) {
                preg_match('/TotalVisibleMemorySize=(\d+)/', $output, $t);
                preg_match('/FreePhysicalMemory=(\d+)/', $output, $f);
                $memory['total'] = (int) ($t[1] ?? 0) * 1024;
                $memory['free']  = (int) ($f[1] ?? 0) * 1024;
                $memory['used']  = $memory['total'] - $memory['free'];
                $memory['percent'] = $memory['total'] > 0 ? round(($memory['used'] / $memory['total']) * 100, 1) : 0;
            }
        }

        return [
            'disk' => [
                'total' => $this->formatBytes($diskTotal),
                'used'  => $this->formatBytes($diskUsed),
                'free'  => $this->formatBytes($diskFree),
                'percent' => $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0,
            ],
            'cpu_load' => [
                '1min' => $load[0] ?? 0,
                '5min' => $load[1] ?? 0,
                '15min' => $load[2] ?? 0,
            ],
            'memory' => $memory,
            'database' => [
                'connection' => DB::connection()->getDatabaseName(),
                'size' => $this->getDatabaseSize(),
            ],
            'platform' => [
                'os' => PHP_OS,
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
            ],
        ];
    }

    /**
     * Estime la taille de la BDD (MySQL).
     */
    private function getDatabaseSize()
    {
        try {
            $result = DB::select('SELECT sum(data_length + index_length) / 1024 / 1024 as size FROM information_schema.TABLES WHERE table_schema = ?', [DB::connection()->getDatabaseName()]);
            return round($result[0]->size, 2) . ' MB';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
