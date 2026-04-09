<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPosition
{
    /**
     * Handle an incoming request.
     *
     * @param  string  ...$positions  <-- The "..." allows multiple arguments
     */
    public function handle(Request $request, Closure $next, ...$positions): Response
    {
        // 1. Check if user is logged in
        $user = $request->user();
        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        // CEO always has access
        if ($user->role === 'CEO') {
            return $next($request);
        }

        // Normalize all allowed positions to lowercase
        $allowedPositions = array_map('strtolower', $positions);

        // For secretaries and general managers: allow if 'manager' is in allowed positions
        if (in_array($user->position, ['secretary', 'general_manager'])) {
            if (in_array('manager', $allowedPositions)) {
                return $next($request);
            }
        }

        // Check if the user's position is in the allowed list
        if (in_array(strtolower($user->position), $allowedPositions)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this resource.');
    }
}