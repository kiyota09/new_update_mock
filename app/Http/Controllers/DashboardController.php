<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = strtoupper($user->role);
        $position = strtolower($user->position);

        // Trainees
        if ($position === 'trainee') {
            return Inertia::render('Dashboard/TRAINEE/index', [
                'user' => $user,
                'stats' => [
                    'progress' => 45,
                    'assigned_modules' => 5,
                    'days_remaining' => 12,
                ],
            ]);
        }

        // CEO
        if ($role === 'CEO') {
            return redirect()->route('ceo.dashboard');
        }

        // Map role to route name
        $routeMap = [
            // HRM uses a unified dashboard
            'HRM' => 'hrm.dashboard',

            // CRM also uses a unified dashboard (no separate employee/manager)
            'CRM' => 'crm.dashboard',

            // Other modules have separate manager / employee dashboards
            'SCM'  => 'scm.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
            'FIN'  => 'fin.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
            'MAN'  => 'man.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
            'INV'  => 'inv.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
            'ORD'  => 'ord.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
            'WAR'  => 'war.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
            'ECO'  => 'eco.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
            'PRO'  => 'pro.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
            'PROJ' => 'proj.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
            'IT'   => 'it.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
        ];

        if (isset($routeMap[$role])) {
            return redirect()->route($routeMap[$role]);
        }

        // Fallback for any other role (e.g., staff with no specific module)
        return Inertia::render('Dashboard', [
            'stats' => [
                'total_tasks' => 0,
                'pending_tasks' => 0,
                'completed_tasks' => 0,
            ],
            'user' => $user,
        ]);
    }
}