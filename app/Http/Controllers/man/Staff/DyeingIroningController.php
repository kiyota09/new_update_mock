<?php

namespace App\Http\Controllers\man\Staff;

use App\Models\IronJob;
use App\Models\MachineReport;
use App\Models\SqueezerJob;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DyeingIroningController extends ManufacturingStaffController
{
    public function index()
    {
        $pendingCount = SqueezerJob::whereHas('ironJob', function ($q) {
            $q->whereNull('id');
        })->count(); // or use a status field on squeezer jobs

        $recentJobs = IronJob::with('squeezerJob.softenerJob.fabric')
            ->where('operator_id', $this->staff()->id)
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingIroning/Index', [
            'stats' => [
                'pending' => $pendingCount,
                'total_today' => IronJob::whereDate('processed_at', today())->count(),
            ],
            'recentJobs' => $recentJobs,
        ]);
    }

    public function dyeingIroning()
    {
        $squeezerJobs = SqueezerJob::with('softenerJob.fabric')
            ->whereDoesntHave('ironJob')
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingIroning/DyeingIroning', [
            'squeezerJobs' => $squeezerJobs,
        ]);
    }

    public function storeIron(Request $request)
    {
        $validated = $request->validate([
            'squeezer_job_id' => 'required|exists:squeezer_jobs,id',
            'remarks' => 'nullable|string',
        ]);

        IronJob::create([
            'squeezer_job_id' => $validated['squeezer_job_id'],
            'remarks' => $validated['remarks'],
            'operator_id' => $this->staff()->id,
            'shift' => $this->getShift(),
            'code' => $this->generateCode('IRON', IronJob::class),
            'processed_at' => now(),
        ]);

        return redirect()->back()->with('message', 'Ironing recorded successfully.');
    }

    public function reports()
    {
        $myReports = MachineReport::where('reported_by', $this->staff()->id)
            ->with('machine')
            ->latest()
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingIroning/Reports', [
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
