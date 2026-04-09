<?php

namespace App\Http\Controllers\man\Staff;

use App\Models\Fabric;
use App\Models\Machine;
use App\Models\MachineReport;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KnittingYarnController extends ManufacturingStaffController
{
    /**
     * Dashboard: show pending tasks and recent activity.
     */
    public function index()
    {
        $pendingCount = Fabric::where('status', 'pending')->count();
        $recentFabrics = Fabric::with('machine')
            ->where('operator_id', $this->staff()->id)
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/KnittingYarn/Index', [
            'stats' => [
                'pending' => $pendingCount,
                'total_today' => Fabric::whereDate('processed_at', today())->count(),
            ],
            'recentFabrics' => $recentFabrics,
        ]);
    }

    /**
     * Show the main knitting yarn page (list of pending orders? but actually they just record fabric).
     */
    public function knittingYarn()
    {
        $machines = Machine::where('type', 'knitting')
            ->where('status', 'available')
            ->get(['id', 'machine_no']);

        return Inertia::render('Dashboard/MAN/Employee/KnittingYarn/KnittingYarn', [
            'machines' => $machines,
        ]);
    }

    /**
     * Record a new fabric from the knitting machine.
     */
    public function storeFabric(Request $request)
    {
        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'yarn_type' => 'required|string|max:255',
            'roll_no' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $fabric = Fabric::create([
            'code' => $this->generateCode('FABRIC', Fabric::class),
            'manufacturing_order_id' => null, // will be linked later
            'machine_id' => $validated['machine_id'],
            'yarn_type' => $validated['yarn_type'],
            'roll_no' => $validated['roll_no'],
            'weight' => $validated['weight'],
            'remarks' => $validated['remarks'],
            'operator_id' => $this->staff()->id,
            'shift' => $this->getShift(),
            'processed_at' => now(),
            'status' => 'pending', // will be picked up by checker
        ]);

        return redirect()->back()->with('message', 'Fabric recorded successfully.');
    }

    /**
     * Show list of reported machines and allow reporting.
     */
    public function reports()
    {
        $machines = Machine::where('type', 'knitting')->get(['id', 'machine_no', 'status']);
        $myReports = MachineReport::where('reported_by', $this->staff()->id)
            ->with('machine')
            ->latest()
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/KnittingYarn/Reports', [
            'machines' => $machines,
            'myReports' => $myReports,
        ]);
    }

    /**
     * Report a machine issue.
     */
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
