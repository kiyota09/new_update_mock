<?php

namespace App\Http\Middleware;

use App\Models\OrdAccess;
use Closure;
use Illuminate\Http\Request;

class CanAccessOrd
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        // CEO, secretary, general manager always have access
        if ($user->role === 'CEO' || in_array($user->position, ['secretary', 'general_manager'])) {
            return $next($request);
        }
        
        $hasAccess = OrdAccess::where('user_id', $user->id)->value('can_access_ord') ?? false;
        if (! $hasAccess) {
            abort(403, 'You do not have permission to access the Order Management module.');
        }

        return $next($request);
    }
}