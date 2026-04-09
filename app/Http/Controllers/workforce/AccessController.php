<?php

namespace App\Http\Controllers\workforce;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkforcePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AccessController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user->role !== 'CEO') {
            abort(403, 'Unauthorized access.');
        }

        $candidates = User::whereIn('position', ['secretary', 'general_manager', 'manager', 'supervisor'])
            ->where('role', '!=', 'CEO') // Only manage others
            ->get()
            ->map(function ($u) {
                $perms = WorkforcePermission::where('user_id', $u->id)->get()->map(fn ($p) => [
                    'module' => $p->module,
                    'department' => $p->department,
                    'access_level' => $p->access_level,
                ]);

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'role' => $u->role,
                    'position' => $u->position,
                    'permissions' => $perms,
                ];
            });

        return Inertia::render('Dashboard/Workforce/Access', [
            'candidates' => $candidates,
            'modules' => ['HRM', 'ECO', 'CRM', 'SCM', 'MAN', 'PROJ', 'FIN', 'LOG', 'IT', 'WAR', 'ORD', 'PRO'],
            'departments' => ['knitting', 'dyeing', 'maintenance'],
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'CEO') {
            abort(403, 'Only the CEO can modify permissions.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permissions' => 'present|array', // "present" allows empty array to clear access
            'permissions.*.module' => 'nullable|string',
            'permissions.*.access_level' => 'required|in:view,schedule,manage',
        ]);

        // Clear existing permissions
        WorkforcePermission::where('user_id', $request->user_id)->delete();

        // If specific rules were provided, save them
        foreach ($request->permissions as $perm) {
            WorkforcePermission::create([
                'user_id' => $request->user_id,
                'module' => $perm['module'] ?? null,
                'department' => $perm['department'] ?? null,
                'access_level' => $perm['access_level'],
            ]);
        }

        return back()->with('message', 'Workforce access updated.');
    }
}