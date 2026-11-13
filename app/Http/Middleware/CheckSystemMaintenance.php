<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Mode maintenance opérationnel :
 * - par défaut, le site public affiche une 503 proprement ;
 * - les utilisateurs admin/superadmin conservent l'accès ;
 * - le mode de debug vise à éviter de bloquer les uploads si le bug est dans ce middleware.
 */
class CheckSystemMaintenance
{
    public function handle(Request $request, Closure $next)
    {
        $maintenance = (bool) config('settings.maintenance_mode', false);
        if (! $maintenance) {
            return $next($request);
        }

        $user = Auth::user();
        $isAdmin = $user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['superadmin', 'admin']);

        if ($isAdmin) {
            return $next($request);
        }

        // Autoriser /login et assets pour ne pas complètement bloquer l'accès
        if ($request->is('login') || $request->is('logout') || $request->is('build/*') || $request->is('assets/*') || $request->is('media/*')) {
            return $next($request);
        }

        abort(503, 'Le site est temporairement en maintenance. Nous revenons très vite.');
    }
}
