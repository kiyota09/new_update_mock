<?php

namespace App\Http\Controllers\man\Staff;

use App\Models\DyeJob;
use App\Models\Fabric;
use App\Models\FormJob;
use App\Models\IronJob;
use App\Models\ManufacturingOrder;
use App\Models\Package;
use App\Models\SoftenerJob;
use App\Models\SqueezerJob;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckerQualityController extends ManufacturingStaffController
{
    public function index()
    {
        $stats = [
            'pending_orders' => ManufacturingOrder::where('status', 'pending')->count(),
            'fabrics_pending' => Fabric::where('status', 'pending')->count(),
            'dye_pending' => DyeJob::whereDoesntHave('fabric', function ($q) {
                $q->where('status', 'dyeing'); // but we need a proper status check
            })->count(),
            'softener_pending' => SoftenerJob::where('status', 'softened')->count(),
            'squeezer_pending' => SqueezerJob::whereDoesntHave('ironJob')->count(),
            'iron_pending' => IronJob::whereDoesntHave('formJob')->count(),
            'form_pending' => FormJob::whereDoesntHave('packageItems')->count(),
            'packages_pending' => Package::where('status', 'pending')->count(),
        ];

        return Inertia::render('Dashboard/MAN/Employee/CheckerQuality/Index', [
            'stats' => $stats,
        ]);
    }

    /**
     * The main production page with tabs for each stage.
     */
    public function production()
    {
        $orders = ManufacturingOrder::with('purchaseOrder.client')
            ->where('status', 'pending')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'po_number' => $order->purchaseOrder->po_number,
                    'client' => $order->purchaseOrder->client->company_name,
                    'total_quantity' => $order->total_quantity,
                    'remaining_quantity' => $order->remaining_quantity,
                ];
            });

        $fabrics = Fabric::with('machine', 'operator')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        $dyeJobs = DyeJob::with('fabric', 'machine', 'operator')
            ->get();

        $softenerJobs = SoftenerJob::with('fabric', 'machine', 'operator', 'squeezerJob')
            ->get();

        $squeezerJobs = SqueezerJob::with('softenerJob.fabric', 'machine', 'operator', 'ironJob')
            ->get();

        $ironJobs = IronJob::with('squeezerJob.softenerJob.fabric', 'operator', 'formJob')
            ->get();

        $formJobs = FormJob::with('ironJob.squeezerJob.softenerJob.fabric', 'product', 'operator', 'packageItems')
            ->get();

        $packages = Package::with('items.formJob.product', 'operator')
            ->where('status', 'pending')
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/CheckerQuality/Production', [
            'orders' => $orders,
            'fabrics' => $fabrics,
            'dyeJobs' => $dyeJobs,
            'softenerJobs' => $softenerJobs,
            'squeezerJobs' => $squeezerJobs,
            'ironJobs' => $ironJobs,
            'formJobs' => $formJobs,
            'packages' => $packages,
        ]);
    }

    // ========== Order Actions ==========

    public function checkInventory($orderId)
    {
        $order = ManufacturingOrder::findOrFail($orderId);

        // Logic to check inventory against the order's product(s)
        // For now, just return a dummy message.
        return redirect()->back()->with('message', 'Inventory checked. Available: 0 items.');
    }

    public function startProduction($orderId)
    {
        $order = ManufacturingOrder::findOrFail($orderId);
        $order->update(['status' => 'in_progress']);

        return redirect()->back()->with('message', 'Production started.');
    }

    // ========== Fabric Actions ==========

    public function passFabric(Request $request, $fabricId)
    {
        $validated = $request->validate([
            'destination' => 'required|in:dyeing,softener',
        ]);

        $fabric = Fabric::findOrFail($fabricId);
        $fabric->update(['status' => $validated['destination']]);

        return redirect()->back()->with('message', 'Fabric passed to '.$validated['destination']);
    }

    // ========== Dye Actions ==========

    public function passDye(Request $request, $dyeId)
    {
        $validated = $request->validate([
            'action' => 'required|in:quality,reject',
        ]);

        $dye = DyeJob::findOrFail($dyeId);
        $fabric = $dye->fabric;

        if ($validated['action'] === 'quality') {
            // Optionally, you could ask which department to send to
            $fabric->update(['status' => 'softener']);
        } else {
            $fabric->update(['status' => 'rejected']);
            // Log the rejection (maybe store reason)
        }

        return redirect()->back()->with('message', 'Dye job processed.');
    }

    // ========== Softener & Squeezer Actions ==========

    public function passSoftener(Request $request, $softenerId)
    {
        $validated = $request->validate([
            'action' => 'required|in:quality,resoften',
        ]);

        $softener = SoftenerJob::findOrFail($softenerId);

        if ($validated['action'] === 'quality') {
            // Pass to ironing (since squeezer is done automatically)
            $squeezer = $softener->squeezerJob;
            if ($squeezer) {
                // Create iron job? Actually ironing will be triggered by the ironing staff
                // We'll just mark the fabric as ready for ironing
                $softener->fabric->update(['status' => 'iron']);
            }
        } else {
            // Resoften: send back to softener department
            $softener->update(['status' => 'resoften']);
            $softener->fabric->update(['status' => 'softener']);
        }

        return redirect()->back()->with('message', 'Softener processed.');
    }

    public function passSqueezer(Request $request, $squeezerId)
    {
        // Similar to above
        $squeezer = SqueezerJob::findOrFail($squeezerId);

        // The checker will decide if it goes to ironing or needs resoftening
        // This could be done by updating the fabric status
        return redirect()->back()->with('message', 'Squeezer processed.');
    }

    // ========== Iron Actions ==========

    public function passIron(Request $request, $ironId)
    {
        $validated = $request->validate([
            'action' => 'required|in:form,pack',
        ]);

        $iron = IronJob::findOrFail($ironId);

        if ($validated['action'] === 'form') {
            $iron->fabric()->update(['status' => 'forming']);
        } else {
            // Pack directly (for fabrics that are final products)
            $iron->fabric()->update(['status' => 'packed']);
        }

        return redirect()->back()->with('message', 'Ironing processed.');
    }

    // ========== Form Actions ==========

    public function packForm(Request $request, $formId)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $form = FormJob::findOrFail($formId);

        // Create a package item or directly mark for packaging
        // For simplicity, we'll just mark the form as ready for packaging
        // The packaging staff will later create the package.

        $form->update(['status' => 'packed']);

        return redirect()->back()->with('message', 'Form marked for packaging.');
    }

    public function rejectForm(Request $request, $formId)
    {
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $form = FormJob::findOrFail($formId);
        $form->update([
            'status' => 'rejected',
            'remarks' => $validated['reason'],
        ]);

        // Optionally, archive the form

        return redirect()->back()->with('message', 'Form rejected.');
    }

    // ========== Package Actions ==========

    public function assignPackageToOrder(Request $request, $packageId)
    {
        $validated = $request->validate([
            'manufacturing_order_id' => 'required|exists:manufacturing_orders,id',
        ]);

        $package = Package::findOrFail($packageId);
        $order = ManufacturingOrder::findOrFail($validated['manufacturing_order_id']);

        // Check if package items match order requirements (simplified)
        $totalInPackage = $package->items->sum('quantity');
        if ($totalInPackage <= $order->remaining_quantity) {
            $package->update([
                'manufacturing_order_id' => $order->id,
                'status' => 'assigned',
            ]);
            $order->decrement('remaining_quantity', $totalInPackage);
            if ($order->remaining_quantity <= 0) {
                $order->update(['status' => 'completed']);
            }

            return redirect()->back()->with('message', 'Package assigned to order.');
        } else {
            return redirect()->back()->with('error', 'Package quantity exceeds remaining order quantity.');
        }
    }
}
