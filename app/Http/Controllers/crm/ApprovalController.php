<?php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CrmMeeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->canAccessCrmPage('approval') && $user->role !== 'CEO') {
            abort(403);
        }

        $pendingClients = Client::where('status', 'pending')->with('meetings')->get();

        return Inertia::render('Dashboard/CRM/Approval', ['pendingClients' => $pendingClients]);
    }

    public function addNote(Request $request, $id)
    {
        $request->validate(['note' => 'required|string']);

        $client = Client::findOrFail($id);
        $existing = $client->notes ? $client->notes . "\n" : '';
        $client->update(['notes' => $existing . $request->note]);

        return back()->with('message', 'Note added.');
    }

    public function scheduleMeeting(Request $request, $id)
    {
        $validated = $request->validate([
            'scheduled_at' => 'required|date',
            'meeting_type' => 'required|string',
            'location'     => 'nullable|string',
            'notes'        => 'nullable|string',
        ]);

        CrmMeeting::create([
            'client_id'    => $id,
            'scheduled_at' => $validated['scheduled_at'],
            'meeting_type' => $validated['meeting_type'],
            'location'     => $validated['location'],
            'notes'        => $validated['notes'],
            'created_by'   => Auth::id(),
            'status'       => 'scheduled',
        ]);

        return back()->with('message', 'Meeting scheduled.');
    }

    public function updateMeetingStatus(Request $request, $meetingId)
    {
        $meeting = CrmMeeting::findOrFail($meetingId);
        $meeting->update(['status' => $request->status]);
        return back()->with('message', 'Meeting status updated.');
    }

    public function accept($id)
    {
        $client = Client::findOrFail($id);
        $client->update(['status' => 'active']);
        return back()->with('message', 'Client approved and now active.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string']);
        $client = Client::findOrFail($id);
        $client->update(['status' => 'rejected', 'rejection_reason' => $request->reason]);
        return back()->with('message', 'Client rejected.');
    }
}