<?php

namespace App\Http\Middleware;

use App\Models\SupportTicket;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garde-fou global : vérifie qu'au moins un des rôles fournis est attribué à l'utilisateur.
 * Utilisation : ->middleware(['role:admin,instructor'])
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Superadmin a toujours accès
        if ($user->hasRole('superadmin')) {
            return $next($request);
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        $reason = "Accès refusé — rôle requis : " . implode(', ', $roles);
        $this->recordSupportTicket($request, $reason, $roles);

        abort(403, $reason);
    }

    /**
     * Crée automatiquement un ticket support pour un 403 (échec non bloquant).
     */
    private function recordSupportTicket(Request $request, string $reasons, array $roles): void
    {
        try {
            $user = $request->user();

            if (! $user) {
                return;
            }

            do {
                $ticketNumber = '403-' . now()->format('ymdHis') . '-' . Str::lower(Str::random(4));
            } while (SupportTicket::where('ticket_number', $ticketNumber)->exists());

            $categoryId = DB::table('ticket_categories')
                ->where('name', 'like', '%Bug technique%')
                ->value('id');

            SupportTicket::create([
                'ticket_number' => $ticketNumber,
                'user_id' => $user->id,
                'ticket_category_id' => $categoryId ?: null,
                'assigned_to_user_id' => null,
                'subject' => 'Accès refusé : ' . substr($request->path(), 0, 200),
                'description' => 'URL: ' . $request->fullUrl()
                    . "\nRôles requis : " . implode(', ', $roles)
                    . "\nRôle(s) utilisateur : " . ($user->roles ? implode(', ', $user->roles->pluck('name')->all()) : '(aucun)')
                    . "\nIP : " . $request->ip()
                    . "\nUser-Agent : " . substr($request->userAgent() ?? '', 0, 300),
                'status' => 'open',
                'priority' => 'medium',
            ]);
        } catch (\Throwable $e) {
            Log::warning('EnsureUserHasRole: échec création ticket support (403) — ' . $reasons . ' — ' . $e->getMessage());
        }
    }
}
