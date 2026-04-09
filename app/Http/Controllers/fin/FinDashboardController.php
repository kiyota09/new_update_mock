<?php

namespace App\Http\Controllers\fin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class FinDashboardController extends Controller
{
    public function managerDashboard()
    {
        return Inertia::render('Dashboard/FIN/Manager/index', [
            'user' => auth()->user(),
            'stats' => [
                'totalRevenue' => 0,
                'pendingInvoices' => 0,
                'overduePayments' => 0,
            ],
        ]);
    }

    public function staffDashboard()
    {
        return Inertia::render('Dashboard/FIN/Employee/index', [
            'user' => auth()->user(),
            'stats' => [
                'totalRevenue' => 0,
                'pendingInvoices' => 0,
                'overduePayments' => 0,
            ],
        ]);
    }
}
