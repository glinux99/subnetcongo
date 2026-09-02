<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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

        abort(403, "Accès refusé — rôle requis : " . implode(', ', $roles));
    }
}