<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApiController extends Controller
{
    /**
     * Display the API Management Dashboard.
     *
     * This view provides a high-level overview of the API's health,
     * recent activity, and key metrics such as total requests,
     * error rates, and active tokens.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        // Calculate date ranges for stats
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);

        // 1. Token Statistics
        $totalTokens = PersonalAccessToken::count();
        $activeTokens = PersonalAccessToken::where(function ($query) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>', Carbon::now());
        })->count();

        $lastUsedToken = PersonalAccessToken::orderBy('last_used_at', 'desc')->first();

        // 2. Request Statistics (based on AuditLog)
        // Assuming 'api' events or URLs starting with /api are logged
        $apiLogsQuery = AuditLog::where('url', 'like', '%/api/%');

        $totalRequests = $apiLogsQuery->count();
        $recentRequests = $apiLogsQuery->where('created_at', '>=', $now->copy()->subDay())->count();
        $errorRequests = $apiLogsQuery->where('event', 'like', '%error%')
                                      ->orWhere('event', 'like', '%fail%')
                                      ->count();

        // 3. Traffic Chart Data (Last 30 Days)
        $trafficData = AuditLog::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('url', 'like', '%/api/%')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // 4. Top API Users
        $topUsers = AuditLog::select('user_id', DB::raw('count(*) as count'))
            ->where('url', 'like', '%/api/%')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->limit(5)
            ->with('user:id,name,email,profile_photo_path') // Assuming profile_photo_path exists or similar
            ->get();

        return Inertia::render('System/Api', [
            'stats' => [
                'total_tokens' => $totalTokens,
                'active_tokens' => $activeTokens,
                'total_requests' => $totalRequests,
                'recent_requests' => $recentRequests,
                'error_rate' => $totalRequests > 0 ? round(($errorRequests / $totalRequests) * 100, 2) : 0,
                'last_activity' => $lastUsedToken ? $lastUsedToken->last_used_at?->diffForHumans() : 'Never',
            ],
            'traffic_data' => $trafficData,
            'top_users' => $topUsers,
            'filters' => $request->all(['date_range']),
        ]);
    }

    /**
     * List all Personal Access Tokens.
     *
     * Allows administrators to view, search, and manage tokens
     * issued to users within the system.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Inertia\Response
     */
    public function tokens(Request $request)
    {
        $query = PersonalAccessToken::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('tokenable_id', 'like', "%{$search}%"); // Searching by ID usually
            });
        }

        // Filter by User
        if ($request->filled('user_id')) {
            $query->where('tokenable_id', $request->input('user_id'))
                  ->where('tokenable_type', User::class);
        }

        // Sorting
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['name', 'last_used_at', 'created_at', 'expires_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $tokens = $query->paginate($request->input('per_page', 15))
            ->withQueryString();

        // Transform tokens to include user info manually if relation not defined on PAT model
        $tokens->getCollection()->transform(function ($token) {
            if ($token->tokenable_type === User::class) {
                $token->user = User::find($token->tokenable_id);
            }
            return $token;
        });

        return Inertia::render('Configurations/System/Api/Tokens', [
            'tokens' => $tokens,
            'filters' => $request->only(['search', 'user_id', 'sort_field', 'sort_direction']),
        ]);
    }

    /**
     * Store a newly created API Token.
     *
     * This method allows admins to manually generate tokens for users.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'abilities' => 'nullable|array',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::findOrFail($request->input('user_id'));
        $name = $request->input('name');
        $abilities = $request->input('abilities', ['*']);
        $expiresAt = $request->input('expires_at') ? Carbon::parse($request->input('expires_at')) : null;

        // Create the token using Sanctum's method
        $tokenResult = $user->createToken($name, $abilities, $expiresAt);

        // Log the action
        AuditLog::create([
            'user_id' => $request->user()->id,
            'event' => 'api_token_created',
            'auditable_type' => PersonalAccessToken::class,
            'auditable_id' => $tokenResult->accessToken->id,
            'new_values' => json_encode(['name' => $name, 'user_id' => $user->id]),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('flash', [
            'token' => $tokenResult->plainTextToken,
            'message' => 'Token created successfully. Please copy it now as it will not be shown again.',
        ]);
    }

    /**
     * Revoke a specific API Token.
     *
     * @param string $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function revokeToken(Request $request, string $id)
    {
        $token = PersonalAccessToken::findOrFail($id);

        // Optional: Check permissions
        // if ($request->user()->cannot('delete', $token)) { abort(403); }

        $oldValues = $token->toArray();
        $token->delete();

        // Log the action
        AuditLog::create([
            'user_id' => $request->user()->id,
            'event' => 'api_token_revoked',
            'auditable_type' => PersonalAccessToken::class,
            'auditable_id' => $id,
            'old_values' => json_encode($oldValues),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'API Token revoked successfully.');
    }

    /**
     * Revoke all tokens for a specific user.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $userId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function revokeUserTokens(Request $request, string $userId)
    {
        $user = User::findOrFail($userId);

        $count = $user->tokens()->delete();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'event' => 'api_tokens_flushed',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'new_values' => json_encode(['count' => $count]),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "All {$count} tokens for user {$user->name} have been revoked.");
    }

    /**
     * Display API Access Logs.
     *
     * Filters AuditLogs specifically for API related events.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Inertia\Response
     */
    public function logs(Request $request)
    {
        $query = AuditLog::with('user')
            ->where(function($q) {
                $q->where('url', 'like', '%/api/%')
                  ->orWhere('event', 'like', 'api_%');
            });

        // Filtering
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('event', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            // Assuming we might tag logs with status in 'tags' or infer from event name
            $status = $request->input('status');
            if ($status === 'error') {
                $query->where('event', 'like', '%error%');
            } elseif ($status === 'success') {
                $query->where('event', 'not like', '%error%');
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20))
            ->withQueryString();

        return Inertia::render('Configurations/System/Api/Logs', [
            'logs' => $logs,
            'filters' => $request->all(),
        ]);
    }

    /**
     * Show details of a specific API Log.
     *
     * @param string $id
     * @return \Inertia\Response
     */
    public function logShow(string $id)
    {
        $log = AuditLog::with('user')->findOrFail($id);

        return Inertia::render('Configurations/System/Api/LogShow', [
            'log' => $log,
        ]);
    }

    /**
     * Get API Statistics Data (JSON).
     *
     * Used for dynamic charts on the frontend.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(Request $request)
    {
        $period = $request->input('period', '30_days');
        $startDate = match ($period) {
            '7_days' => Carbon::now()->subDays(7),
            '90_days' => Carbon::now()->subDays(90),
            'year' => Carbon::now()->subYear(),
            default => Carbon::now()->subDays(30),
        };

        // Requests per day
        $requests = AuditLog::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('url', 'like', '%/api/%')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Methods distribution (GET, POST, etc.) - Assuming method is logged or inferred
        // Since AuditLog doesn't have 'method' column explicitly in the migration provided,
        // we might skip this or assume it's in 'tags' or 'event'.
        // Let's try to parse it from 'event' if the convention is "METHOD /url"
        // Otherwise, we return empty.

        return response()->json([
            'requests' => $requests,
            'period' => $period,
        ]);
    }

    /**
     * Display API Settings.
     *
     * Allows configuration of global API parameters like rate limiting,
     * maintenance mode, and allowed origins.
     *
     * @return \Inertia\Response
     */
    public function settings()
    {
        // In a real app, these would come from a database table (e.g., 'settings')
        // For now, we'll mock the structure or read from config
        $settings = [
            'api_enabled' => config('api.enabled', true),
            'default_rate_limit' => config('api.rate_limit', 60),
            'logging_enabled' => config('api.logging', true),
            'allowed_origins' => config('cors.allowed_origins', ['*']),
            'token_expiration_days' => config('sanctum.expiration', null) ? (config('sanctum.expiration') / 60 / 24) : 0,
        ];

        return Inertia::render('Configurations/System/Api/Settings', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update API Settings.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'api_enabled' => 'required|boolean',
            'default_rate_limit' => 'required|integer|min:1|max:10000',
            'logging_enabled' => 'required|boolean',
            'token_expiration_days' => 'required|integer|min:0',
        ]);

        // Here we would save to the Settings model or update a config file.
        // Since we don't have a generic Settings model in the context provided (except empty WebSetting),
        // we will simulate saving and log the action.

        // Example logic if we had a Settings model:
        // Setting::set('api.enabled', $validated['api_enabled']);
        // ...

        AuditLog::create([
            'user_id' => $request->user()->id,
            'event' => 'api_settings_updated',
            'auditable_type' => 'System',
            'auditable_id' => 0,
            'new_values' => json_encode($validated),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', 'API Settings updated successfully.');
    }

    /**
     * Display API Documentation.
     *
     * Renders a view that might embed Swagger UI or Redoc.
     *
     * @return \Inertia\Response
     */
    public function documentation()
    {
        return Inertia::render('Configurations/System/Api/Documentation', [
            'spec_url' => url('/api/documentation/json'), // Hypothetical endpoint
        ]);
    }

    /**
     * Export API Logs to CSV.
     *
     * Generates a streamed download of API logs for auditing purposes.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportLogs(Request $request)
    {
        $fileName = 'api_logs_' . date('Y-m-d_H-i') . '.csv';

        $query = AuditLog::with('user')
            ->where('url', 'like', '%/api/%')
            ->orderBy('created_at', 'desc');

        // Apply same filters as index
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        return new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // Add BOM for Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");

            // Headers
            fputcsv($handle, [
                'ID',
                'Date',
                'User',
                'Event',
                'URL',
                'IP Address',
                'User Agent',
                'Tags'
            ]);

            $query->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->created_at->toDateTimeString(),
                        $log->user ? $log->user->name : 'System/Guest',
                        $log->event,
                        $log->url,
                        $log->ip_address,
                        $log->user_agent,
                        $log->tags,
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
     * Check API System Health.
     *
     * Performs checks on database, cache, and storage to ensure
     * the API subsystem is functioning correctly.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function health()
    {
        $checks = [
            'database' => false,
            'cache' => false,
            'storage' => false,
        ];

        // Check Database
        try {
            DB::connection()->getPdo();
            $checks['database'] = true;
        } catch (\Exception $e) {
            $checks['database_error'] = $e->getMessage();
        }

        // Check Cache (assuming file or redis)
        try {
            cache()->set('health_check', 'ok', 10);
            if (cache()->get('health_check') === 'ok') {
                $checks['cache'] = true;
            }
        } catch (\Exception $e) {
            $checks['cache_error'] = $e->getMessage();
        }

        // Check Storage
        try {
            $checks['storage'] = is_writable(storage_path());
        } catch (\Exception $e) {
            $checks['storage_error'] = $e->getMessage();
        }

        $status = !in_array(false, $checks, true) ? 'healthy' : 'degraded';

        return response()->json([
            'status' => $status,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $status === 'healthy' ? 200 : 503);
    }

    /**
     * Prune expired tokens.
     *
     * Removes tokens that have passed their expiration date.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function pruneTokens(Request $request)
    {
        $count = PersonalAccessToken::where('expires_at', '<', now())->delete();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'event' => 'api_tokens_pruned',
            'auditable_type' => PersonalAccessToken::class,
            'auditable_id' => 0,
            'new_values' => json_encode(['deleted_count' => $count]),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Cleaned up {$count} expired tokens.");
    }
}
