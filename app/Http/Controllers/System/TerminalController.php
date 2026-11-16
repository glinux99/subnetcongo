<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TerminalSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Terminal persistant SubnetCongo — v1
 *
 * Démarre des commandes artisan détachées côté serveur sur Linux/Plesk,
 * avec sortie dans `storage/app/terminal/<id>.log`.
 * Sur Windows (dev), exécute simplement en ligne (pas de détachement possible).
 */
class TerminalController extends Controller
{
    private const DANGEROUS_PATTERNS = [
        'down', 'up',
        'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'db:seed', 'db:wipe',
        'key:generate', 'storage:link', 'queue:flush', 'queue:failed',
        'optimize:clear', 'octane:clear', 'vendor:publish',
    ];

    public function index(Request $request)
    {
        $uid = $request->user()?->id;

        $sessions = TerminalSession::query()
            ->where('user_id', $uid)
            ->orWhereNull('user_id')
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'command', 'title', 'status', 'exit_code', 'started_at', 'ended_at']);

        return Inertia::render('System/Terminal', [
            'sessions' => $sessions,
        ]);
    }

    public function start(Request $request)
    {
        $command = trim((string) $request->input('cmd', ''));
        $title = trim((string) $request->input('title', ''));

        if ($command === '') {
            return back()->with('error', 'Aucune commande fournie.');
        }

        if (stripos($command, 'php artisan ') === 0) {
            $command = trim(substr($command, strlen('php artisan ')));
        } elseif (stripos($command, 'artisan ') === 0) {
            $command = trim(substr($command, strlen('artisan ')));
        }

        $bare = $command;
        $dangerous = $this->isDangerous($bare);
        $confirmed = $request->boolean('confirm');
        if ($dangerous && ! $confirmed) {
            return back()->with('error', 'Commande destructive détectée. Confirmez avec confirm=yes.');
        }

        $tokens = $this->tokenize($bare);
        if (empty($tokens)) {
            return back()->with('error', 'Aucune commande fournie.');
        }
        $artisanCommand = array_shift($tokens);
        $args = $tokens;

        $userId = $request->user()?->id;
        $id = uniqid('term_', true);
        $logDir = storage_path('app/terminal');
        if (! is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logPath = $logDir . '/' . $id . '.log';
        $exitPath = $logDir . '/' . $id . '.exit';

        $title = $title !== '' ? $title : $artisanCommand;

        // Crée l'entrée (status=running) av. détachement pour avoir l'id comme référence
        $session = TerminalSession::create([
            'user_id' => $userId,
            'command' => $artisanCommand,
            'full_command' => 'php artisan ' . $artisanCommand . (empty($args) ? '' : ' ' . implode(' ', $args)),
            'title' => mb_substr($title, 0, 140),
            'status' => 'running',
            'log_path' => $logPath,
            'started_at' => now(),
        ]);

        try {
            $pid = $this->launchDetached($artisanCommand, $args, $logPath, $exitPath);
        } catch (\Throwable $e) {
            $session->update([
                'status' => 'error',
                'ended_at' => now(),
                'exit_code' => 1,
            ]);
            Log::error('terminal_launch_fail', ['error' => $e->getMessage()]);
            AuditLog::create([
                'user_id' => $userId,
                'event' => 'artisan_command_failed',
                'auditable_type' => 'System',
                'auditable_id' => 0,
                'ip_address' => $request->ip(),
                'new_values' => json_encode(['command' => $artisanCommand, 'error' => $e->getMessage()]),
            ]);
            return back()->with('error', 'Impossible de démarrer la session : ' . $e->getMessage());
        }

        $session->update(['process_id' => $pid >= 0 ? $pid : null]);

        // Audit
        AuditLog::create([
            'user_id' => $userId,
            'event' => 'artisan_command_dispatched',
            'auditable_type' => 'System',
            'auditable_id' => $session->id,
            'ip_address' => $request->ip(),
            'new_values' => json_encode([
                'session_id' => $session->id,
                'command' => $artisanCommand,
                'pid' => $pid,
            ]),
        ]);

        return redirect()->route('system.terminal')->with('success', 'Commande lancée (session #' . $session->id . ').');
    }

    public function tail(Request $request, TerminalSession $session)
    {
        // Autorise user propriétaire ou superadmin
        $uid = $request->user()?->id;
        if ($session->user_id && (int) $session->user_id !== (int) $uid) {
            abort(403);
        }

        $offset = max(0, (int) $request->query('offset', 0));
        $logPath = (string) $session->log_path;

        $content = file_exists($logPath) ? file_get_contents($logPath) : '';
        $newContent = substr((string) $content, $offset);
        $newOffset = strlen((string) $content);

        $this->syncDoneFromFileOrPid($session);
        $session->refresh();

        return response()->json([
            'content' => $newContent,
            'offset' => $newOffset,
            'title' => $session->title,
            'status' => $session->status,
            'exit_code' => $session->exit_code,
            'started_at' => optional($session->started_at)->toIso8601String(),
            'ended_at' => optional($session->ended_at)->toIso8601String(),
        ]);
    }

    public function kill(Request $request, TerminalSession $session)
    {
        $uid = $request->user()?->id;
        if ($session->user_id && (int) $session->user_id !== (int) $uid) {
            abort(403);
        }

        $pid = (int) $session->process_id;
        if ($pid > 0 && PHP_OS_FAMILY !== 'Windows') {
            @shell_exec('kill ' . $pid . ' 2>&1');
        }

        $session->update(['status' => 'killed', 'ended_at' => now()]);
        return redirect()->route('system.terminal')->with('success', 'Session stoppée.');
    }

    public function close(Request $request, TerminalSession $session)
    {
        $uid = $request->user()?->id;
        if ($session->user_id && (int) $session->user_id !== (int) $uid) {
            abort(403);
        }
        $session->update(['status' => 'closed', 'ended_at' => $session->ended_at ?? now()]);
        return redirect()->route('system.terminal')->with('success', 'Session fermée.');
    }

    private function syncDoneFromFileOrPid(TerminalSession $session): void
    {
        $exitPath = str_replace('.log', '.exit', (string) $session->log_path);
        $done = false;
        $exitCode = $session->exit_code;

        if (file_exists($exitPath)) {
            $done = true;
            $raw = trim((string) file_get_contents($exitPath));
            $exitCode = is_numeric($raw) ? (int) $raw : null;
        } elseif ($session->process_id && function_exists('posix_kill')) {
            // posix_kill returns false for missing pid
            $alive = @posix_kill((int) $session->process_id, 0);
            if ($alive === false) {
                $done = true;
            }
        }

        if ($done) {
            $session->update([
                'status' => $exitCode === null || $exitCode === 0 ? 'done' : 'error',
                'exit_code' => $exitCode,
                'ended_at' => now(),
            ]);
        }
    }

    /**
     * Lance `php artisan <cmd> [args]` en détaché (Linux/WSL/WAMP/Cron).
     */
    private function launchDetached(string $artisanCommand, array $args, string $logPath, string $exitPath): int
    {
        // Commande complète (php artisan)
        $commandParts = array_merge([$this->phpCliBinary(), base_path('artisan'), $artisanCommand], $args);
        $isWindows = PHP_OS_FAMILY === 'Windows';
        $cmdString = implode(' ', array_map('escapeshellarg', $commandParts));

        if (PHP_OS_FAMILY === 'Windows') {
            // Sur Windows (dev), les commandes longues bloqueraient la requête HTTP
            // (le serveur de dev local est mono-processus en pratique) → refus clair.
            $longRunning = ['queue:work', 'queue:listen', 'queue:monitor', 'schedule:work', 'horizon', 'pail', 'serve', 'tinker', 'db:monitor', 'reverb:start'];
            $cmdLc = strtolower($artisanCommand);
            foreach ($longRunning as $pat) {
                if ($cmdLc === $pat || str_starts_with($cmdLc, $pat)) {
                    throw new \RuntimeException(
                        'Commande longue refusée sur Windows : elle bloquerait le serveur local. ' .
                        'Ajoute --once si la commande le supporte, ou lance-la dans une console séparée (php artisan ' . $artisanCommand . ').'
                    );
                }
            }
            // Fallback Windows (dev) : synchrone, stdout/stderr → fichier log via redirect shell
            $logDir = dirname($logPath);
            if (! is_dir($logDir)) { @mkdir($logDir, 0755, true); }
            $cmdStringWin = sprintf(
                'cmd /c "%s > "%s" 2>&1 < NUL"',
                $cmdString,
                str_replace('/', '\\', $logPath)
            );
            $proc = @proc_open(
                $cmdStringWin,
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes
            );
            $rc = null;
            if (is_resource($proc)) {
                if (isset($pipes[0])) { @fclose($pipes[0]); }
                if (isset($pipes[1])) { @fclose($pipes[1]); }
                if (isset($pipes[2])) { @fclose($pipes[2]); }
                $rc = proc_close($proc);
            }
            file_put_contents($exitPath, (string) ($rc ?? 0));
            return -1;
        }

        // Unix : vrai détachement via setsid + nohup
        $wrapper = sprintf(
            'sh -c %s & echo $!',
            escapeshellarg(
                $cmdString . ' </dev/null > ' . escapeshellarg($logPath) . ' 2>&1; echo $? > ' . escapeshellarg($exitPath)
            )
        );

        $pid = (int) trim((string) shell_exec(sprintf('setsid nohup %s </dev/null >/dev/null 2>&1 & echo $!', $wrapper)));
        if ($pid <= 0) {
            // Fallback si setsid indisponible
            $pid = (int) trim((string) shell_exec(sprintf('nohup %s </dev/null >/dev/null 2>&1 & echo $!', $wrapper)));
        }

        return $pid;
    }

    /**
     * Choisit un vrai binaire PHP CLI (≥ 8.2).
     * Sous php-fpm, PHP_BINARY pointe sur php-fpm (qui ne sait pas lancer artisan) —
     * on ne l'utilise donc que si le SAPI courant est CLI. Sinon on teste php8.3 → php8.2
     * (la prod Plesk a `php` = 8.1, trop vieux). `TERMINAL_PHP_BINARY` (env) force le choix.
     */
    private function phpCliBinary(): string
    {
        $forced = env('TERMINAL_PHP_BINARY');
        if (is_string($forced) && $forced !== '') {
            return $forced;
        }

        if (PHP_SAPI === 'cli' || PHP_SAPI === 'cli-server') {
            return PHP_BINARY;
        }

        foreach (['php8.3', 'php8.2', 'php8.1', 'php'] as $name) {
            $resolved = $this->resolveBinary($name);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return 'php';
    }

    private function resolveBinary(string $name): ?string
    {
        $path = $name;
        if (PHP_OS_FAMILY !== 'Windows' && ! is_executable($name)) {
            $found = trim((string) @shell_exec('command -v ' . escapeshellarg($name) . ' 2>/dev/null'));
            if ($found === '') {
                return null;
            }
            $path = $found;
        }
        $versionId = trim((string) @shell_exec(escapeshellarg($path) . ' -r "echo PHP_VERSION_ID;" 2>/dev/null'));
        return ctype_digit($versionId) && (int) $versionId >= 80200 ? $path : null;
    }

    private function isDangerous(string $bare): bool
    {
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if ($pattern === $bare || str_starts_with($bare, $pattern . ' ')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Tokenise basique : espaces, guillemets simples/doubles.
     */
    private function tokenize(string $input): array
    {
        $tokens = [];
        $current = '';
        $inQuotes = null;
        $len = strlen($input);

        for ($i = 0; $i < $len; $i++) {
            $c = $input[$i];
            if ($inQuotes !== null) {
                if ($c === $inQuotes) {
                    $inQuotes = null;
                    continue;
                }
                $current .= $c;
                continue;
            }
            if ($c === '"' || $c === "'") {
                $inQuotes = $c;
                continue;
            }
            if ($c === ' ' || $c === "\t") {
                if ($current !== '') {
                    $tokens[] = $current;
                    $current = '';
                }
                continue;
            }
            $current .= $c;
        }
        if ($current !== '') $tokens[] = $current;
        return $tokens;
    }
}
