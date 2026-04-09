<?php

namespace App\Http\Controllers\man\Staff;

use App\Models\Fabric;
use App\Models\Machine;
use App\Models\MachineReport;
use App\Models\SoftenerJob;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DyeingFabricSoftenerController extends ManufacturingStaffController
{
    public function index()
    {
        $pendingCount = Fabric::where('status', 'softener')->count();
        $recentJobs = SoftenerJob::with('fabric')
            ->where('operator_id', $this->staff()->id)
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingFabricSoftener/Index', [
            'stats' => [
                'pending' => $pendingCount,
                'total_today' => SoftenerJob::whereDate('processed_at', today())->count(),
            ],
            'recentJobs' => $recentJobs,
        ]);
    }

    public function dyeingFabricSoftener()
    {
        $fabrics = Fabric::with('machine')
            ->where('status', 'softener')
            ->get();

        $machines = Machine::where('type', 'softening')
            ->where('status', 'available')
            ->get(['id', 'machine_no']);

        return Inertia::render('Dashboard/MAN/Employee/DyeingFabricSoftener/DyeingFabricSoftener', [
            'fabrics' => $fabrics,
            'machines' => $machines,
        ]);
    }

    public function storeSoftener(Request $request)
    {
        $validated = $request->validate([
            'fabric_id' => 'required|exists:fabrics,id',
            'machine_id' => 'required|exists:machines,id',
            'softener_type' => 'required|string|max:255',
            'softener_no' => 'required|string|max:255',
            'remarks' => 'nullable|string',
        ]);

        $fabric = Fabric::findOrFail($validated['fabric_id']);
        $fabric->update(['status' => 'squeezer']); // after softener, goes to squeezer

        SoftenerJob::create([
            'fabric_id' => $validated['fabric_id'],
            'machine_id' => $validated['machine_id'],
            'softener_type' => $validated['softener_type'],
            'softener_no' => $validated['softener_no'],
            'remarks' => $validated['remarks'],
            'operator_id' => $this->staff()->id,
            'shift' => $this->getShift(),
            'code' => $this->generateCode('SOFT', SoftenerJob::class),
            'processed_at' => now(),
            'status' => 'softened',
        ]);

        return redirect()->back()->with('message', 'Softening recorded successfully.');
    }

    public function reports()
    {
        $machines = Machine::where('type', 'softening')->get(['id', 'machine_no', 'status']);
        $myReports = MachineReport::where('reported_by', $this->staff()->id)
            ->with('machine')
            ->latest()
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingFabricSoftener/Reports', [
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
