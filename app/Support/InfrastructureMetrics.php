<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Métriques d'infrastructure pour le dashboard administrateur.
 * Centralise la collecte Windows/Linux afin que le contrôleur et la commande
 * de broadcast utilisent exactement la même source.
 */
class InfrastructureMetrics
{
    public const CACHE_KEY = 'subnet_infra_health';

    public static function status(): array
    {
        return Cache::remember(self::CACHE_KEY, 30, fn () => self::collect());
    }

    public static function collect(): array
    {
        return PHP_OS_FAMILY === 'Windows'
            ? self::windowsMetrics()
            : self::linuxMetrics();
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function windowsMetrics(): array
    {
        try {
            $cpuOutput = shell_exec('wmic cpu get loadpercentage /value');
            preg_match('/LoadPercentage=(\d+)/', (string) $cpuOutput, $matches);
            $cpu = isset($matches[1]) ? (int) $matches[1] : rand(10, 30);

            $freeMem = shell_exec('wmic OS get FreePhysicalMemory /Value');
            $totalMem = shell_exec('wmic OS get TotalVisibleMemorySize /Value');
            preg_match('/FreePhysicalMemory=(\d+)/', (string) $freeMem, $mFree);
            preg_match('/TotalVisibleMemorySize=(\d+)/', (string) $totalMem, $mTotal);

            $ram = 0;
            if (isset($mFree[1], $mTotal[1]) && (int) $mTotal[1] > 0) {
                $ram = (int) round(100 - (($mFree[1] / $mTotal[1]) * 100));
            }

            $diskFree = disk_free_space('C:');
            $diskTotal = disk_total_space('C:');
            $diskUsage = $diskTotal > 0 ? (int) round(100 - (($diskFree / $diskTotal) * 100)) : 0;

            return [
                'platform' => 'Windows (Development)',
                'cpu' => $cpu,
                'ram' => $ram,
                'disk' => $diskUsage,
                'db_latency' => self::measureDbLatency(),
                'status' => $cpu < 85 ? 'Optimal' : 'Surchargé',
                'uptime' => 'N/A sur Windows dev',
            ];
        } catch (\Throwable $e) {
            return self::mockMetrics('Erreur Win Metrics: ' . $e->getMessage());
        }
    }

    private static function linuxMetrics(): array
    {
        try {
            $load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
            $cpu = (int) round(($load[0] ?? 0) * 100 / 4);

            $ram = self::linuxRamUsage();

            $diskFree = disk_free_space('/');
            $diskTotal = disk_total_space('/');
            $diskUsage = $diskTotal > 0 ? (int) round(100 - (($diskFree / $diskTotal) * 100)) : 0;

            return [
                'platform' => 'Linux (Production)',
                'cpu' => $cpu,
                'ram' => $ram,
                'disk' => $diskUsage,
                'db_latency' => self::measureDbLatency(),
                'status' => $cpu < 80 ? 'Optimal' : 'Attention',
                'uptime' => trim((string) shell_exec('uptime -p')),
            ];
        } catch (\Throwable $e) {
            return self::mockMetrics('Erreur Linux Metrics: ' . $e->getMessage());
        }
    }

    private static function linuxRamUsage(): int
    {
        $free = trim((string) shell_exec('free'));
        if (! $free) {
            return 0;
        }

        $lines = explode("\n", $free);
        $mem = array_values(array_filter(explode(' ', $lines[1] ?? '')));

        if (count($mem) < 3 || (float) ($mem[1] ?? 0) === 0.0) {
            return 0;
        }

        return (int) round(($mem[2] / $mem[1]) * 100);
    }

    private static function measureDbLatency(): float
    {
        $start = microtime(true);
        DB::select('SELECT 1');
        return round((microtime(true) - $start) * 1000, 2);
    }

    private static function mockMetrics(string $error): array
    {
        Log::critical('Infrastructure Metrics Error: ' . $error);

        return [
            'platform' => 'Unknown (Error Fallback)',
            'cpu' => 0,
            'ram' => 0,
            'disk' => 0,
            'db_latency' => 0,
            'status' => 'Indisponible',
            'error' => $error,
        ];
    }
}
