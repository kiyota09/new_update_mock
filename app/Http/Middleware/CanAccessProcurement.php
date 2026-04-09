<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CanAccessProcurement
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }
        
        // CEO, secretary, general manager always have access
        if ($user->role === 'CEO' || in_array($user->position, ['secretary', 'general_manager'])) {
            return $next($request);
        }
        
        // Check explicit permission
        $hasAccess = \App\Models\ProAccess::where('user_id', $user->id)
            ->where('can_access_procurement', true)
            ->exists();
        if (!$hasAccess) {
            abort(403, 'You do not have access to the Procurement module.');
        }
        return $next($request);
    }
}