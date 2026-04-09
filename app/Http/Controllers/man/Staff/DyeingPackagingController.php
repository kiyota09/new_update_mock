<?php

namespace App\Http\Controllers\man\Staff;

use App\Models\FormJob;
use App\Models\Package;
use App\Models\PackageItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DyeingPackagingController extends ManufacturingStaffController
{
    public function index()
    {
        $pendingCount = FormJob::whereDoesntHave('packageItems')->count();
        $recentPackages = Package::with('items.formJob.product')
            ->where('operator_id', $this->staff()->id)
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingPackaging/Index', [
            'stats' => [
                'pending' => $pendingCount,
                'total_today' => Package::whereDate('packaged_at', today())->count(),
            ],
            'recentPackages' => $recentPackages,
        ]);
    }

    public function packaging()
    {
        $availableForms = FormJob::with('product')
            ->whereDoesntHave('packageItems')
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/DyeingPackaging/Packaging', [
            'availableForms' => $availableForms,
        ]);
    }

    public function storePackage(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.form_job_id' => 'required|exists:form_jobs,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $package = Package::create([
            'code' => $this->generateCode('PACKAGE', Package::class),
            'operator_id' => $this->staff()->id,
            'shift' => $this->getShift(),
            'packaged_at' => now(),
            'status' => 'pending',
        ]);

        foreach ($validated['items'] as $item) {
            PackageItem::create([
                'package_id' => $package->id,
                'form_job_id' => $item['form_job_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        return redirect()->back()->with('message', 'Package created successfully.');
    }
}
