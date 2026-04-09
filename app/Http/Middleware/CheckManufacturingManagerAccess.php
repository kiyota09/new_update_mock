<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckManufacturingManagerAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        // Allow if:
        // 1. User is a regular manager (position = manager)
        // 2. User is a manufacturing supervisor
        // 3. User is Secretary or General Manager AND has 'MAN' in granted_modules
        if ($user->position === 'manager' ||
            $user->is_manufacturing_supervisor ||
            (in_array($user->position, ['secretary', 'general_manager']) && 
             in_array('MAN', $user->granted_modules ?? []))) {
            return $next($request);
        }

        abort(403, 'Unauthorized - Only manufacturing managers, supervisors, or authorized secretaries/GMs can access this page.');
    }
}