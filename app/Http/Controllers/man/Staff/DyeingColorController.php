<?php

namespace App\Http\Controllers\man\Staff;

use App\Models\DyeJob;
use App\Models\Fabric;
use App\Models\Machine;
use App\Models\MachineReport;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DyeingColorController extends ManufacturingStaffController
{
    public function index()
    {
        $pendingCount = Fabric::where('status', 'dyeing')->count();
        $recentJobs = DyeJob::with('fabric')
            ->where('operator_id', $this->staff()->id)
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingColor/Index', [
            'stats' => [
                'pending' => $pendingCount,
                'total_today' => DyeJob::whereDate('processed_at', today())->count(),
            ],
            'recentJobs' => $recentJobs,
        ]);
    }

    public function dyeingColor()
    {
        $fabrics = Fabric::with('machine')
            ->where('status', 'dyeing')
            ->get();

        $machines = Machine::where('type', 'dyeing')
            ->where('status', 'available')
            ->get(['id', 'machine_no']);

        return Inertia::render('Dashboard/MAN/Employee/DyeingColor/DyeingColor', [
            'fabrics' => $fabrics,
            'machines' => $machines,
        ]);
    }

    public function storeDye(Request $request)
    {
        $validated = $request->validate([
            'fabric_id' => 'required|exists:fabrics,id',
            'machine_id' => 'required|exists:machines,id',
            'dye_type' => 'required|string|max:255',
            'chemical_no' => 'required|string|max:255',
            'remarks' => 'nullable|string',
        ]);

        $fabric = Fabric::findOrFail($validated['fabric_id']);
        $fabric->update(['status' => 'softener']); // after dyeing, fabric goes to softener

        DyeJob::create([
            'fabric_id' => $validated['fabric_id'],
            'machine_id' => $validated['machine_id'],
            'dye_type' => $validated['dye_type'],
            'chemical_no' => $validated['chemical_no'],
            'remarks' => $validated['remarks'],
            'operator_id' => $this->staff()->id,
            'shift' => $this->getShift(),
            'code' => $this->generateCode('CHEM', DyeJob::class),
            'processed_at' => now(),
        ]);

        return redirect()->back()->with('message', 'Dyeing recorded successfully.');
    }

    public function reports()
    {
        $machines = Machine::where('type', 'dyeing')->get(['id', 'machine_no', 'status']);
        $myReports = MachineReport::where('reported_by', $this->staff()->id)
            ->with('machine')
            ->latest()
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingColor/Reports', [
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
