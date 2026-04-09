<?php

namespace App\Http\Controllers\it;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ItDashboardController extends Controller
{
    public function managerDashboard()
    {
        return Inertia::render('Dashboard/IT/Manager/index', [
            'user' => auth()->user(),
            'stats' => [
                'activeTickets' => 0,
                'systemUptime' => '99.9%',
                'securityAlerts' => 0,
            ],
        ]);
    }

    public function staffDashboard()
    {
        return Inertia::render('Dashboard/IT/Employee/index', [
            'user' => auth()->user(),
            'stats' => [
                'activeTickets' => 0,
                'systemUptime' => '99.9%',
                'securityAlerts' => 0,
            ],
        ]);
    }
}
