<?php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class InterviewController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->canAccessCrmPage('interview') && $user->role !== 'CEO') {
            abort(403);
        }

        $applicants = Applicant::with('interview')
            ->where('status', 'Interview')
            ->where('assigned_module', 'CRM')
            ->where('archived', false)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->first_name.' '.$a->last_name,
                'email' => $a->email,
                'phone' => $a->phone_number,
                'position_applied' => $a->position_applied,
                'scheduled_at' => $a->interview?->scheduled_at,
                'interview_type' => $a->interview?->interview_type,
                'notes' => $a->interview?->notes,
            ]);

        return Inertia::render('Dashboard/CRM/Interview', ['applicants' => $applicants]);
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
        $user = User::create([
            'name' => $applicant->first_name.' '.$applicant->last_name,
            'email' => $applicant->email,
            'password' => Hash::make('password'),
            'role' => 'CRM',
            'position' => 'trainee',
            'is_active' => true,
            'join_date' => now(),
            'employee_id' => $this->generateEmployeeId(),
        ]);

        $applicant->update(['status' => 'Passed', 'archived' => false]);

        return back()->with('message', 'Applicant passed interview and became trainee.');
    }

    public function fail(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string']);
        $applicant = Applicant::findOrFail($id);
        $applicant->update(['status' => 'Failed Interview', 'archived' => true, 'rejection_reason' => $request->reason]);
        return back()->with('message', 'Applicant failed interview.');
    }

    public function passToOtherModule(Request $request, $id)
    {
        $request->validate(['module' => 'required|in:HRM,ECO,SCM,MAN,PROJ,FIN,LOG,IT']);
        $applicant = Applicant::findOrFail($id);
        $applicant->update(['assigned_module' => $request->module, 'status' => 'Interview']);
        return back()->with('message', "Applicant passed to {$request->module}.");
    }

    private function generateEmployeeId()
    {
        $year = now()->year;
        $last = User::where('employee_id', 'like', "MONTI-{$year}-CRM-%")->orderBy('employee_id', 'desc')->first();
        $num = $last ? (int) substr($last->employee_id, -4) + 1 : 1;
        return sprintf('MONTI-%s-CRM-%04d', $year, $num);
    }
}