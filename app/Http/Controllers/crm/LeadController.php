<?php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CreditAccount;
use App\Models\CrmApproval;
use App\Models\CrmLead;
use App\Models\CrmLeadNote;
use App\Models\CrmLeadInterview;
use App\Models\CrmLeadApprovalFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LeadController extends Controller
{
    /**
     * Display the lead pipeline.
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user->canAccessCrmPage('lead') && $user->role !== 'CEO') {
            abort(403);
        }

        $leads = CrmLead::with([
            'notes.user',
            'interviews.user',
            'approvalFiles.user',
            'assignedStaff'
        ])
            ->whereNotIn('status', ['Converted', 'Archived'])
            ->orderBy('created_at', 'asc')
            ->get();

        return Inertia::render('Dashboard/CRM/Lead', ['leads' => $leads]);
    }

    /**
     * Store a new lead.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->canAccessCrmPage('lead') && $user->role !== 'CEO') {
            abort(403);
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'interest_fabric' => 'required|string',
            'estimated_value' => 'required|numeric|min:0',
        ]);

        CrmLead::create([
            'company_name' => $validated['company_name'],
            'contact_person' => $validated['contact_person'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'interest_fabric' => $validated['interest_fabric'],
            'estimated_value' => $validated['estimated_value'],
            'status' => 'Inquiry',
            'assigned_staff_id' => Auth::id(),
        ]);

        return back()->with('message', 'New lead created.');
    }

    /**
     * Update lead status (move to next stage).
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canAccessCrmPage('lead') && $user->role !== 'CEO') {
            abort(403);
        }

        $lead = CrmLead::findOrFail($id);
        $lead->update(['status' => $request->status]);

        return back();
    }

    /**
     * Convert a lead to a business client.
     */
    public function convertToClient(Request $request)
    {
        $user = Auth::user();
        if (!$user->canAccessCrmPage('lead') && $user->role !== 'CEO') {
            abort(403);
        }

        $validated = $request->validate([
            'lead_id' => 'required|exists:crm_leads,id',
            'company_name' => 'required|string|max:255',
            'business_type' => 'required|string',
            'tin_number' => 'required|string|unique:clients,tin_number',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'required|string',
            'company_address' => 'required|string',
            'password' => 'required|string|min:8',
        ]);

        try {
            DB::beginTransaction();

            $client = Client::create([
                'company_name' => $validated['company_name'],
                'business_type' => $validated['business_type'],
                'tin_number' => $validated['tin_number'],
                'contact_person' => $validated['contact_person'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'company_address' => $validated['company_address'],
                'password' => bcrypt($validated['password']),
                'status' => 'active',
            ]);

            CreditAccount::create([
                'client_id' => $client->id,
                'outstanding_balance' => 0.00,
                'is_good_payer' => 1,
            ]);

            $lead = CrmLead::find($validated['lead_id']);
            $lead->update(['status' => 'Converted']);

            DB::commit();
            return back()->with('message', 'Lead converted to client successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Conversion failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Add a note to a lead (with approval for staff).
     */
    public function addNote(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canAccessCrmPage('lead') && $user->role !== 'CEO') {
            abort(403);
        }

        $validated = $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $lead = CrmLead::findOrFail($id);

        if ($user->position === 'manager' || $user->role === 'CEO') {
            $lead->notes()->create([
                'user_id' => $user->id,
                'note' => $validated['note'],
            ]);
            return back()->with('message', 'Note added.');
        }

        // Staff needs approval
        CrmApproval::create([
            'user_id' => $user->id,
            'action' => 'add_note',
            'data' => [
                'lead_id' => $lead->id,
                'note' => $validated['note'],
            ],
            'status' => 'pending',
        ]);

        return back()->with('message', 'Note submitted for approval.');
    }

    /**
     * Schedule an interview for a lead (with approval for staff).
     */
    public function scheduleInterview(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canAccessCrmPage('lead') && $user->role !== 'CEO') {
            abort(403);
        }

        $validated = $request->validate([
            'scheduled_at' => 'required|date',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $lead = CrmLead::findOrFail($id);

        if ($user->position === 'manager' || $user->role === 'CEO') {
            $lead->interviews()->create([
                'user_id' => $user->id,
                'scheduled_at' => $validated['scheduled_at'],
                'location' => $validated['location'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
            return back()->with('message', 'Interview scheduled.');
        }

        CrmApproval::create([
            'user_id' => $user->id,
            'action' => 'schedule_interview',
            'data' => array_merge(['lead_id' => $lead->id], $validated),
            'status' => 'pending',
        ]);

        return back()->with('message', 'Interview request submitted for approval.');
    }

    /**
     * Upload approval file for a lead (with approval for staff).
     */
    public function uploadApprovalFile(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canAccessCrmPage('lead') && $user->role !== 'CEO') {
            abort(403);
        }

        $validated = $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $lead = CrmLead::findOrFail($id);

        if ($user->position === 'manager' || $user->role === 'CEO') {
            $path = $request->file('file')->store('lead_approval_files', 'public');
            $lead->approvalFiles()->create([
                'file_path' => $path,
                'original_name' => $request->file('file')->getClientOriginalName(),
                'uploaded_by' => $user->id,
            ]);
            return back()->with('message', 'File uploaded.');
        }

        // Staff: store temporarily
        $path = $request->file('file')->store('temp_approval_files', 'public');
        CrmApproval::create([
            'user_id' => $user->id,
            'action' => 'upload_approval_file',
            'data' => [
                'lead_id' => $lead->id,
                'file_path' => $path,
                'original_name' => $request->file('file')->getClientOriginalName(),
            ],
            'status' => 'pending',
        ]);

        return back()->with('message', 'File upload submitted for approval.');
    }

    /**
     * Accept lead (move to Closed-Won) – manager/CEO only.
     */
    public function acceptLead($id)
    {
        $user = Auth::user();
        if (!$user->canAccessCrmPage('lead') && $user->role !== 'CEO') {
            abort(403);
        }

        $lead = CrmLead::findOrFail($id);

        if ($user->position === 'manager' || $user->role === 'CEO') {
            $lead->update(['status' => 'Closed-Won']);
            return back()->with('message', 'Lead accepted and moved to Closed-Won.');
        }

        CrmApproval::create([
            'user_id' => $user->id,
            'action' => 'accept_lead',
            'data' => ['lead_id' => $lead->id],
            'status' => 'pending',
        ]);

        return back()->with('message', 'Accept request submitted for approval.');
    }

    /**
     * Reject lead (move to Lost) – manager/CEO only.
     */
    public function rejectLead(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->canAccessCrmPage('lead') && $user->role !== 'CEO') {
            abort(403);
        }

        $validated = $request->validate([
            'reject_reason' => 'required|string|max:255',
        ]);

        $lead = CrmLead::findOrFail($id);

        if ($user->position === 'manager' || $user->role === 'CEO') {
            $lead->update(['status' => 'Lost', 'lost_reason' => $validated['reject_reason']]);
            return back()->with('message', 'Lead rejected and marked as Lost.');
        }

        CrmApproval::create([
            'user_id' => $user->id,
            'action' => 'reject_lead',
            'data' => [
                'lead_id' => $lead->id,
                'lost_reason' => $validated['reject_reason'],
            ],
            'status' => 'pending',
        ]);

        return back()->with('message', 'Rejection request submitted for approval.');
    }
}