<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\WebSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApiController extends Controller
{
    /**
     * Page principale du Gateway API – 100% données réelles.
     *
     * Retourne un seul payload agrégé (Vue) pour l'onglet Vue d'ensemble
     * et les compteurs en bandeau. Les onglets Tokens / Logs / Settings
     * sont servis par les routes dédiés.
     */
    public function index(Request $request)
    {
        $now = Carbon::now();
        $last30 = $now->copy()->subDays(30);

        // 1. Tokens (Sanctum)
        $totalTokens = PersonalAccessToken::count();
        $activeTokens = PersonalAccessToken::query()
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
            })->count();
        $expiredTokens = PersonalAccessToken::where('expires_at', '<', Carbon::now())->count();
        $lastUsedToken = PersonalAccessToken::orderByDesc('last_used_at')->first();

        // 2. Audit logs "API" : on considère comme API tout log avec method HTTP
        //    OU url commençant par /api/ OU event_name commençant par "api."
        $apiLogs = AuditLog::query()
            ->where(function ($q) {
                $q->whereNotNull('method')
                  ->orWhere('url', 'like', '/api/%')
                  ->orWhere('event_name', 'like', 'api.%')
                  ->orWhere('event', 'like', 'api_%');
            });

        $totalRequests = (clone $apiLogs)->count();
        $requestsToday  = (clone $apiLogs)->whereDate('created_at', $now->today())->count();
        $errorsCount    = (clone $apiLogs)->whereIn('level', ['error', 'critical'])
                            ->orWhere('event', 'like', '%error%')
                            ->orWhere('event', 'like', '%fail%')
                            ->count();
        $errorRate = $totalRequests > 0 ? round(($errorsCount / $totalRequests) * 100, 2) : 0;

        // 3. Trafic 30 jours (groupé par jour)
        $traffic = (clone $apiLogs)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', $last30)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => ['date' => (string) $r->date, 'count' => (int) $r->count])
            ->values();

        // 4. Distribution par méthode HTTP
        $methods = (clone $apiLogs)
            ->select('method', DB::raw('count(*) as count'))
            ->whereNotNull('method')
            ->where('created_at', '>=', $last30)
            ->groupBy('method')
            ->pluck('count', 'method')
            ->toArray();

        // 5. Top 5 consommateurs (par user_id)
        $topUsers = (clone $apiLogs)
            ->select('user_id', DB::raw('count(*) as count'))
            ->whereNotNull('user_id')
            ->where('created_at', '>=', $last30)
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get();
        $topUsers->loadMissing('user:id,name,email');

        // 6. Settings API persistés (WebSetting group='api')
        $settings = [
            'api_enabled'             => (bool) WebSetting::get('api_enabled', true),
            'default_rate_limit'      => (int)  WebSetting::get('default_rate_limit', 60),
            'logging_enabled'         => (bool) WebSetting::get('logging_enabled', true),
            'token_expiration_days'   => (int)  WebSetting::get('token_expiration_days', 365),
            'cors_allowed_origins'    => WebSetting::get('cors_allowed_origins', '*'),
        ];

        // 7. Health snapshot
        $health = $this->computeHealth();

        return Inertia::render('System/Api', [
            'stats' => [
                'total_tokens'    => $totalTokens,
                'active_tokens'   => $activeTokens,
                'expired_tokens'  => $expiredTokens,
                'total_requests'  => $totalRequests,
                'requests_today'  => $requestsToday,
                'error_rate'      => $errorRate,
                'errors_count'    => $errorsCount,
                'last_activity'   => $lastUsedToken && $lastUsedToken->last_used_at
                    ? $lastUsedToken->last_used_at->diffForHumans()
                    : 'Aucune activité',
            ],
            'traffic_data' => $traffic,
            'methods'      => $methods,
            'top_users'    => $topUsers->map(fn ($r) => [
                'count' => (int) $r->count,
                'user'  => $r->user ? [
                    'id'    => $r->user->id,
                    'name'  => $r->user->name,
                    'email' => $r->user->email,
                ] : null,
            ]),
            'settings'     => $settings,
            'health'       => $health,
            'csrf_token'   => csrf_token(),
        ]);
    }

    /**
     * Liste paginée des tokens Sanctum.
     */
    public function tokens(Request $request)
    {
        $query = PersonalAccessToken::query()->with('tokenable');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
                if (ctype_digit($search)) {
                    $q->orWhere('tokenable_id', (int) $search);
                }
            });
        }

        if ($userId = $request->input('user_id')) {
            $query->where('tokenable_id', $userId)->where('tokenable_type', User::class);
        }

        $sortField = $request->input('sort_field', 'created_at');
        $sortDir   = $request->input('sort_direction', 'desc');
        if (in_array($sortField, ['name', 'last_used_at', 'created_at', 'expires_at'], true)) {
            $query->orderBy($sortField, $sortDir);
        }

        $tokens = $query->paginate(15)->withQueryString();

        // Hydrate user (Sanctum ne définit pas la relation par défaut)
        $tokens->getCollection()->transform(function (PersonalAccessToken $token) {
            $user = $token->tokenable_type === User::class
                ? User::find($token->tokenable_id)
                : null;
            return [
                'id'             => $token->id,
                'name'           => $token->name,
                'abilities'      => $token->abilities ?? ['*'],
                'last_used_at'   => $token->last_used_at?->toIso8601String(),
                'expires_at'     => $token->expires_at?->toIso8601String(),
                'created_at'     => $token->created_at?->toIso8601String(),
                'is_expired'     => $token->expires_at ? $token->expires_at->isPast() : false,
                'is_active'      => ! $token->expires_at || $token->expires_at->isFuture(),
                'user'           => $user ? [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ] : null,
            ];
        });

        return response()->json([
            'tokens'  => $tokens,
            'users'   => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    /**
     * Crée un Personal Access Token pour un utilisateur.
     * Retourne le token en clair UNE SEULE fois.
     */
    public function storeToken(Request $request)
    {
        $data = $request->validate([
            'user_id'    => 'required|integer|exists:users,id',
            'name'       => 'required|string|max:255',
            'abilities'  => 'nullable|array',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $user     = User::findOrFail($data['user_id']);
        $name     = $data['name'];
        $abilities = ! empty($data['abilities']) ? $data['abilities'] : ['*'];
        $expiresAt = ! empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;

        $result = $user->createToken($name, $abilities, $expiresAt);
        $plain  = $result->plainTextToken;
        $token  = $result->accessToken;

        AuditLog::create([
            'user_id'        => $request->user()?->id,
            'event_name'     => 'api.token.created',
            'event'          => 'api_token_created',
            'level'          => 'info',
            'description'    => "Token « {$name} » créé pour l'utilisateur #{$user->id} ({$user->email})",
            'auditable_type' => PersonalAccessToken::class,
            'auditable_id'   => $token->id,
            'new_values'     => json_encode(['name' => $name, 'user_id' => $user->id, 'abilities' => $abilities]),
            'url'            => $request->fullUrl(),
            'method'         => 'POST',
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'tags'           => 'api,tokens',
        ]);

        return response()->json([
            'success'     => true,
            'message'     => 'Token créé. Copiez-le maintenant, il ne sera plus jamais affiché.',
            'plain_token' => $plain,
            'token'       => [
                'id'         => $token->id,
                'name'       => $token->name,
                'user'       => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
                'abilities'  => $abilities,
                'expires_at' => $expiresAt?->toIso8601String(),
                'created_at' => $token->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Révoque un token.
     */
    public function revokeToken(Request $request, string $id)
    {
        $token = PersonalAccessToken::findOrFail($id);
        $old   = $token->toArray();
        $token->delete();

        AuditLog::create([
            'user_id'        => $request->user()?->id,
            'event_name'     => 'api.token.revoked',
            'event'          => 'api_token_revoked',
            'level'          => 'warning',
            'description'    => "Token #{$id} « {$old['name']} » révoqué",
            'auditable_type' => PersonalAccessToken::class,
            'auditable_id'   => $id,
            'old_values'     => json_encode($old),
            'url'            => $request->fullUrl(),
            'method'         => 'DELETE',
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'tags'           => 'api,tokens',
        ]);

        return response()->json(['success' => true, 'message' => 'Token révoqué.']);
    }

    /**
     * Révoque tous les tokens d'un utilisateur.
     */
    public function revokeUserTokens(Request $request, string $userId)
    {
        $user  = User::findOrFail($userId);
        $count = $user->tokens()->delete();

        AuditLog::create([
            'user_id'        => $request->user()?->id,
            'event_name'     => 'api.tokens.flushed',
            'event'          => 'api_tokens_flushed',
            'level'          => 'warning',
            'description'    => "Suppression de {$count} token(s) de l'utilisateur #{$user->id}",
            'auditable_type' => User::class,
            'auditable_id'   => $user->id,
            'new_values'     => json_encode(['count' => $count]),
            'url'            => $request->fullUrl(),
            'method'         => 'DELETE',
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'tags'           => 'api,tokens',
        ]);

        return response()->json(['success' => true, 'message' => "{$count} token(s) révoqué(s) pour {$user->name}."]);
    }

    /**
     * Supprime tous les tokens expirés.
     */
    public function pruneTokens(Request $request)
    {
        $count = PersonalAccessToken::where('expires_at', '<', now())->delete();

        AuditLog::create([
            'user_id'        => $request->user()?->id,
            'event_name'     => 'api.tokens.pruned',
            'event'          => 'api_tokens_pruned',
            'level'          => 'info',
            'description'    => "{$count} token(s) expiré(s) nettoyé(s)",
            'auditable_type' => PersonalAccessToken::class,
            'auditable_id'   => 0,
            'new_values'     => json_encode(['count' => $count]),
            'url'            => $request->fullUrl(),
            'method'         => 'POST',
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'tags'           => 'api,tokens',
        ]);

        return response()->json(['success' => true, 'message' => "{$count} token(s) expiré(s) nettoyé(s)."]);
    }

    /**
     * Liste paginée des audit logs API.
     */
    public function logs(Request $request)
    {
        $query = AuditLog::with('user')
            ->where(function ($q) {
                $q->whereNotNull('method')
                  ->orWhere('url', 'like', '/api/%')
                  ->orWhere('event_name', 'like', 'api.%')
                  ->orWhere('event', 'like', 'api_%');
            });

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                  ->orWhere('event_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%");
            });
        }

        if ($request->filled('method')) {
            $query->where('method', strtoupper($request->input('method')));
        }
        if ($request->filled('level')) {
            $query->where('level', $request->input('level'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20))
            ->withQueryString();

        $logs->getCollection()->transform(fn (AuditLog $log) => [
            'id'           => $log->id,
            'event'        => $log->event,
            'event_name'   => $log->event_name,
            'level'        => $log->level ?? 'info',
            'description'  => $log->description,
            'url'          => $log->url,
            'method'       => $log->method,
            'ip_address'   => $log->ip_address,
            'user_agent'   => $log->user_agent,
            'tags'         => $log->tags,
            'metadata'     => $log->metadata,
            'created_at'   => $log->created_at?->toIso8601String(),
            'user'         => $log->user ? [
                'id'    => $log->user->id,
                'name'  => $log->user->name,
                'email' => $log->user->email,
            ] : null,
        ]);

        return response()->json([
            'logs'    => $logs,
            'methods' => AuditLog::whereNotNull('method')->distinct()->pluck('method')->values(),
            'levels'  => AuditLog::whereNotNull('level')->distinct()->pluck('level')->values(),
        ]);
    }

    /**
     * Détail d'un log.
     */
    public function logShow(string $id)
    {
        $log = AuditLog::with('user')->findOrFail($id);
        return response()->json(['log' => $log]);
    }

    /**
     * Statistiques JSON pour rafraîchir les graphes sans recharger la page.
     */
    public function stats(Request $request)
    {
        $period = $request->input('period', '30_days');
        $start  = match ($period) {
            '7_days'  => Carbon::now()->subDays(7),
            '90_days' => Carbon::now()->subDays(90),
            'year'    => Carbon::now()->subYear(),
            default   => Carbon::now()->subDays(30),
        };

        $base = AuditLog::query()
            ->where(function ($q) {
                $q->whereNotNull('method')
                  ->orWhere('url', 'like', '/api/%')
                  ->orWhere('event_name', 'like', 'api.%')
                  ->orWhere('event', 'like', 'api_%');
            })
            ->where('created_at', '>=', $start);

        $requests = (clone $base)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')->orderBy('date')->get();

        $methods = (clone $base)
            ->select('method', DB::raw('count(*) as count'))
            ->whereNotNull('method')
            ->groupBy('method')->pluck('count', 'method')->toArray();

        $levels = (clone $base)
            ->select('level', DB::raw('count(*) as count'))
            ->whereNotNull('level')
            ->groupBy('level')->pluck('count', 'level')->toArray();

        return response()->json([
            'period'   => $period,
            'requests' => $requests,
            'methods'  => $methods,
            'levels'   => $levels,
        ]);
    }

    /**
     * Renvoie les settings API actuels.
     */
    public function settings()
    {
        return response()->json([
            'settings' => [
                'api_enabled'           => (bool) WebSetting::get('api_enabled', true),
                'default_rate_limit'    => (int)  WebSetting::get('default_rate_limit', 60),
                'logging_enabled'       => (bool) WebSetting::get('logging_enabled', true),
                'token_expiration_days' => (int)  WebSetting::get('token_expiration_days', 365),
                'cors_allowed_origins'  => WebSetting::get('cors_allowed_origins', '*'),
            ],
        ]);
    }

    /**
     * Persiste les settings API dans WebSetting (group='api').
     */
    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'api_enabled'           => 'required|boolean',
            'default_rate_limit'    => 'required|integer|min:1|max:10000',
            'logging_enabled'       => 'required|boolean',
            'token_expiration_days' => 'required|integer|min:0|max:3650',
            'cors_allowed_origins'  => 'nullable|string|max:1000',
        ]);

        foreach ($data as $k => $v) {
            WebSetting::set($k, is_bool($v) ? (int) $v : (string) $v, 'api', is_bool($v) ? 'boolean' : (is_int($v) ? 'integer' : 'text'));
        }

        AuditLog::create([
            'user_id'     => $request->user()?->id,
            'event_name'  => 'api.settings.updated',
            'event'       => 'api_settings_updated',
            'level'       => 'info',
            'description' => 'Paramètres de l\'API mis à jour',
            'new_values'  => json_encode($data),
            'url'         => $request->fullUrl(),
            'method'      => 'POST',
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'tags'        => 'api,settings',
        ]);

        return response()->json(['success' => true, 'message' => 'Paramètres enregistrés.']);
    }

    /**
     * Diagnostics temps réel.
     */
    public function health()
    {
        return response()->json($this->computeHealth());
    }

    /**
     * Export CSV des logs API.
     */
    public function exportLogs(Request $request)
    {
        $fileName = 'api_logs_' . date('Y-m-d_H-i') . '.csv';

        $query = AuditLog::with('user')
            ->where(function ($q) {
                $q->whereNotNull('method')
                  ->orWhere('url', 'like', '/api/%')
                  ->orWhere('event_name', 'like', 'api.%')
                  ->orWhere('event', 'like', 'api_%');
            })
            ->orderByDesc('created_at');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        return new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
            fputcsv($handle, ['ID', 'Date', 'Niveau', 'Événement', 'Description', 'Méthode', 'URL', 'IP', 'Utilisateur', 'Agent']);
            $query->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->created_at?->toDateTimeString(),
                        $log->level,
                        $log->event,
                        $log->description,
                        $log->method,
                        $log->url,
                        $log->ip_address,
                        $log->user?->name ?? '—',
                        $log->user_agent,
                    ]);
                }
            });
            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Calcule l'état de santé (DB / Cache / Storage).
     */
    private function computeHealth(): array
    {
        $checks = [];

        try { DB::connection()->getPdo(); $checks['database'] = true; }
        catch (\Throwable $e) { $checks['database'] = false; $checks['database_error'] = $e->getMessage(); }

        try {
            cache()->put('api_health_check', 'ok', 5);
            $checks['cache'] = cache()->get('api_health_check') === 'ok';
        } catch (\Throwable $e) { $checks['cache'] = false; $checks['cache_error'] = $e->getMessage(); }

        try { $checks['storage'] = is_writable(storage_path('app')) && is_writable(public_path('storage')); }
        catch (\Throwable $e) { $checks['storage'] = false; $checks['storage_error'] = $e->getMessage(); }

        $checks['status']    = ! in_array(false, $checks, true) ? 'healthy' : 'degraded';
        $checks['timestamp'] = now()->toIso8601String();
        $checks['php']       = PHP_VERSION;
        $checks['laravel']   = app()->version();

        return $checks;
    }
}
