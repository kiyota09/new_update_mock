<?php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CrmClientAssignment;
use App\Models\CrmMeeting;
use App\Models\CrmFeedback;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CustomerProfileController extends Controller
{
    /**
     * Display a list of clients (all for managers/CEO, assigned only for staff).
     */
    public function index()
    {
        $user = Auth::user();

        if (!in_array($user->role, ['CRM', 'CEO'])) {
            abort(403, 'Unauthorized access.');
        }

        if ($user->role === 'CRM' && $user->position === 'staff') {
            // Staff sees only assigned clients
            $assignments = CrmClientAssignment::with('client')->where('staff_id', $user->id)->get();
            $clients = $assignments->pluck('client');
            if ($clients->isEmpty()) {
                return Inertia::render('Dashboard/CRM/CustomerProfilesList', [
                    'clients' => [],
                    'message' => 'No clients assigned to you.'
                ]);
            }
        } else {
            // Manager/CEO sees all active clients
            $clients = Client::where('status', 'active')->get();
        }

        return Inertia::render('Dashboard/CRM/CustomerProfilesList', [
            'clients' => $clients,
        ]);
    }

    /**
     * Show the detailed profile of a specific client.
     */
    public function show($id)
    {
        $user = Auth::user();
        $client = Client::with(['feedback.assignee'])->findOrFail($id);

        // Restrict staff to only their assigned clients
        if ($user->role === 'CRM' && $user->position === 'staff') {
            $isAssigned = CrmClientAssignment::where('client_id', $client->id)
                                             ->where('staff_id', $user->id)
                                             ->exists();
            if (!$isAssigned) {
                abort(403, 'You are not assigned to this client.');
            }
        } elseif (!in_array($user->role, ['CRM', 'CEO'])) {
            abort(403, 'Unauthorized access.');
        }

        $meetings = CrmMeeting::where('client_id', $client->id)->latest()->get();
        $feedback = CrmFeedback::where('client_id', $client->id)->with('assignee')->latest()->get();

        return Inertia::render('Dashboard/CRM/CustomerProfile', [
            'client'   => $client,
            'meetings' => $meetings,
            'feedback' => $feedback,
        ]);
    }
}