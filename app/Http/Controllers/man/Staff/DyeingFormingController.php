<?php

namespace App\Http\Controllers\man\Staff;

use App\Models\FormJob;
use App\Models\IronJob;
use App\Models\Machine;
use App\Models\MachineReport;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DyeingFormingController extends ManufacturingStaffController
{
    public function index()
    {
        $pendingCount = IronJob::whereDoesntHave('formJob')->count();
        $recentJobs = FormJob::with('ironJob.squeezerJob.softenerJob.fabric', 'product')
            ->where('operator_id', $this->staff()->id)
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingForming/Index', [
            'stats' => [
                'pending' => $pendingCount,
                'total_today' => FormJob::whereDate('processed_at', today())->count(),
            ],
            'recentJobs' => $recentJobs,
        ]);
    }

    public function dyeingForming()
    {
        $ironJobs = IronJob::with('squeezerJob.softenerJob.fabric')
            ->whereDoesntHave('formJob')
            ->get();

        $machines = Machine::where('type', 'forming')
            ->where('status', 'available')
            ->get(['id', 'machine_no']);

        return Inertia::render('Dashboard/MAN/Employee/DyeingForming/DyeingForming', [
            'ironJobs' => $ironJobs,
            'machines' => $machines,
        ]);
    }

    public function storeForm(Request $request)
    {
        $validated = $request->validate([
            'iron_job_id' => 'required|exists:iron_jobs,id',
            'machine_id' => 'required|exists:machines,id',
            'quantity' => 'required|integer|min:1',
            'product_id' => 'required|exists:products,id',
            'remarks' => 'nullable|string',
        ]);

        FormJob::create([
            'iron_job_id' => $validated['iron_job_id'],
            'machine_id' => $validated['machine_id'],
            'quantity' => $validated['quantity'],
            'product_id' => $validated['product_id'],
            'remarks' => $validated['remarks'],
            'operator_id' => $this->staff()->id,
            'shift' => $this->getShift(),
            'code' => $this->generateCode('FORM', FormJob::class),
            'processed_at' => now(),
        ]);

        return redirect()->back()->with('message', 'Forming recorded successfully.');
    }

    public function reports()
    {
        $machines = Machine::where('type', 'forming')->get(['id', 'machine_no', 'status']);
        $myReports = MachineReport::where('reported_by', $this->staff()->id)
            ->with('machine')
            ->latest()
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingForming/Reports', [
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
