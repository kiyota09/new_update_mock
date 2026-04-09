<?php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CrmLead;
use App\Models\CrmFeedback;
use App\Models\CrmMeeting;
use App\Models\CrmClientAssignment;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CrmDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Allow only CRM users (any position) and CEO
        if (!in_array($user->role, ['CRM', 'CEO'])) {
            abort(403, 'Unauthorized access.');
        }

        $stats = [
            'total_clients' => Client::where('status', 'active')->count(),
            'pending_clients' => Client::where('status', 'pending')->count(),
            'total_leads' => CrmLead::count(),
            'open_feedback' => CrmFeedback::where('status', 'open')->count(),
        ];

        // If user is staff and has assigned clients for investigation, show only those
        if ($user->position === 'staff') {
            $assignedClientIds = CrmClientAssignment::where('staff_id', $user->id)->pluck('client_id');
            $recentFeedback = CrmFeedback::whereIn('client_id', $assignedClientIds)->latest()->take(5)->get();
        } else {
            $recentFeedback = CrmFeedback::latest()->take(5)->get();
        }

        return Inertia::render('Dashboard/CRM/CRMDashboard', [
            'stats' => $stats,
            'recentFeedback' => $recentFeedback,
        ]);
    }
}