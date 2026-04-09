<?php

namespace App\Http\Controllers\hrm;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class InterviewController extends Controller
{
    public function index(Request $request)
    {
        // 1. DYNAMIC MODULE DETECTION
        // Extracts the prefix from the route name (e.g., 'scm.interview.index' -> 'SCM')
        // This allows top-tier management (CEO/Secretary) to see the correct applicants per module
        $routeName = $request->route()->getName();
        $modulePrefix = explode('.', $routeName)[0];
        $module = strtoupper($modulePrefix);

        $applicants = Applicant::with('interview')
            ->where('status', 'Interview')
            ->where('assigned_module', $module)
            ->where('archived', false)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($a) => $this->formatApplicant($a));

        // Using Single-UI structure path
        return Inertia::render('Dashboard/HRM/Interview', [
            'applicants' => $applicants,
            'currentModule' => $module,
        ]);
    }

    public function schedule(Request $request, $id)
    {
        $request->validate([
            'scheduled_at' => 'required|date',
            'interview_type' => 'required|string',
            'duration' => 'nullable|integer',
            'interviewer' => 'nullable|string',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $applicant = Applicant::findOrFail($id);
        Interview::updateOrCreate(
            ['applicant_id' => $applicant->id],
            $request->only('scheduled_at', 'interview_type', 'duration', 'interviewer', 'location', 'notes')
        );

        return back()->with('message', 'Interview scheduled.');
    }

    public function pass(Request $request, $id)
    {
        $applicant = Applicant::findOrFail($id);

        // 2. USE APPLICANT'S MODULE
        // Assign the trainee to the module they actually applied for
        $module = $applicant->assigned_module;

        // Create user account as trainee
        $user = User::create([
            'name' => $applicant->first_name.' '.$applicant->last_name,
            'email' => $applicant->email,
            'password' => Hash::make('password'), // temporary password
            'role' => $module,
            'position' => 'trainee',
            'is_active' => true,
            'join_date' => now(),
            'employee_id' => $this->generateEmployeeId($module),
        ]);

        $applicant->update([
            'status' => 'Passed',
            'archived' => false,
            'interview_feedback' => $request->feedback ?? null,
        ]);

        return back()->with('message', 'Applicant passed interview and became trainee.');
    }

    public function fail(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string']);

        $applicant = Applicant::findOrFail($id);
        $applicant->update([
            'status' => 'Failed Interview',
            'archived' => true,
            'rejection_reason' => $request->reason,
        ]);

        return back()->with('message', 'Applicant failed interview and archived.');
    }

    public function passToOtherModule(Request $request, $id)
    {
        $request->validate(['module' => 'required|in:HRM,ECO,CRM,SCM,MAN,PROJ,FIN,LOG,IT']);

        $applicant = Applicant::findOrFail($id);
        $applicant->update([
            'assigned_module' => $request->module,
            'status' => 'Interview',
            'archived' => false,
        ]);

        return back()->with('message', "Applicant passed to {$request->module} for interview.");
    }

    private function generateEmployeeId($role)
    {
        $year = now()->year;
        $last = User::where('employee_id', 'like', "MONTI-{$year}-{$role}-%")
            ->orderBy('employee_id', 'desc')
            ->first();
        $num = $last ? (int) substr($last->employee_id, -4) + 1 : 1;

        return sprintf('MONTI-%s-%s-%04d', $year, $role, $num);
    }

    private function formatApplicant($applicant)
    {
        return [
            'id' => $applicant->id,
            'name' => $applicant->first_name.' '.$applicant->last_name,
            'email' => $applicant->email,
            'phone' => $applicant->phone_number,
            'position_applied' => $applicant->position_applied,
            'status' => $applicant->status,
            'assigned_module' => $applicant->assigned_module,
            'scheduled_at' => $applicant->interview?->scheduled_at,
            'interview_type' => $applicant->interview?->interview_type,
            'duration' => $applicant->interview?->duration,
            'interviewer' => $applicant->interview?->interviewer,
            'location' => $applicant->interview?->location,
            'notes' => $applicant->interview?->notes,
        ];
    }
}
