<?php

namespace App\Http\Controllers\man\Staff;

use App\Models\Machine;
use App\Models\MachineReport;
use App\Models\SoftenerJob;
use App\Models\SqueezerJob;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DyeingSqueezerController extends ManufacturingStaffController
{
    public function index()
    {
        $pendingCount = SoftenerJob::where('status', 'softened')->count();
        $recentJobs = SqueezerJob::with('softenerJob.fabric')
            ->where('operator_id', $this->staff()->id)
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingSqueezer/Index', [
            'stats' => [
                'pending' => $pendingCount,
                'total_today' => SqueezerJob::whereDate('processed_at', today())->count(),
            ],
            'recentJobs' => $recentJobs,
        ]);
    }

    public function dyeingSqueezer()
    {
        $softenerJobs = SoftenerJob::with('fabric')
            ->where('status', 'softened')
            ->get();

        $machines = Machine::where('type', 'squeezer')
            ->where('status', 'available')
            ->get(['id', 'machine_no']);

        return Inertia::render('Dashboard/MAN/Employee/DyeingSqueezer/DyeingSqueezer', [
            'softenerJobs' => $softenerJobs,
            'machines' => $machines,
        ]);
    }

    public function storeSqueezer(Request $request)
    {
        $validated = $request->validate([
            'softener_job_id' => 'required|exists:softener_jobs,id',
            'machine_id' => 'required|exists:machines,id',
            'remarks' => 'nullable|string',
        ]);

        $softenerJob = SoftenerJob::findOrFail($validated['softener_job_id']);
        $softenerJob->update(['status' => 'squeezed']);

        SqueezerJob::create([
            'softener_job_id' => $validated['softener_job_id'],
            'machine_id' => $validated['machine_id'],
            'remarks' => $validated['remarks'],
            'operator_id' => $this->staff()->id,
            'shift' => $this->getShift(),
            'code' => $this->generateCode('SQUEEZE', SqueezerJob::class),
            'processed_at' => now(),
        ]);

        // The fabric's status is updated by the checker later
        return redirect()->back()->with('message', 'Squeezing recorded successfully.');
    }

    public function reports()
    {
        $machines = Machine::where('type', 'squeezer')->get(['id', 'machine_no', 'status']);
        $myReports = MachineReport::where('reported_by', $this->staff()->id)
            ->with('machine')
            ->latest()
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingSqueezer/Reports', [
            'machines' => $machines,
            'myReports' => $myReports,
        ]);
    }

    public function reportMachine(Request $request)
    {
        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'issue' => 'required|string',
        ]);

        MachineReport::create([
            'machine_id' => $validated['machine_id'],
            'reported_by' => $this->staff()->id,
            'issue' => $validated['issue'],
            'status' => 'pending',
        ]);

        return redirect()->back()->with('message', 'Machine issue reported.');
    }
}
