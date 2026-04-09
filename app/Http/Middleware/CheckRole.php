<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        // CEO has unrestricted access to all modules
        if ($user->role === 'CEO') {
            return $next($request);
        }

        // If no specific roles are required, allow any authenticated user
        if (empty($roles)) {
            return $next($request);
        }

        // Convert allowed roles to uppercase for case-insensitive matching
        $allowedRoles = array_map('strtoupper', $roles);

        // For secretaries and general managers: they retain their original role's permissions
        if (in_array($user->position, ['secretary', 'general_manager'])) {
            if (in_array($user->role, $allowedRoles)) {
                return $next($request);
            }
        }

        // Normal check: user's role must be in the allowed roles list
        if (in_array($user->role, $allowedRoles)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this resource.');
    }
}