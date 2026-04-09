<?php

namespace App\Http\Controllers\man\Manager;

use App\Http\Controllers\Controller;
use App\Models\FormJob;
use App\Models\Machine;
use App\Models\ManufacturingOrder;
use App\Models\Package;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ManufacturingManagerController extends Controller
{
    /**
     * Get the current authenticated user (supervisor or legacy manager).
     */
    protected function getCurrentManager()
    {
        return Auth::user();
    }

    /**
     * Filter staff list based on supervisor's department.
     * Excludes the logged-in supervisor from the list.
     */
    protected function getFilteredStaff()
    {
        $user = $this->getCurrentManager();

        // Base query: MAN staff with position 'staff'
        $query = User::where('role', 'MAN')
            ->where('position', 'staff')
            ->select('id', 'name', 'email', 'manufacturing_role', 'is_manufacturing_supervisor');

        // If user is a manufacturing supervisor, filter by department roles and exclude self
        if ($user->is_manufacturing_supervisor && $user->supervisor_department) {
            $supervisedRoles = $user->supervised_roles;
            $query->whereIn('manufacturing_role', $supervisedRoles)
                  ->where('id', '!=', $user->id);
        }

        return $query->get()->map(fn($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'manufacturing_role' => $u->manufacturing_role,
            'is_manufacturing_supervisor' => $u->is_manufacturing_supervisor,
        ]);
    }

    public function index()
    {
        $receivedOrders = PurchaseOrder::whereHas('queue', function ($q) {
            $q->where('stage', 'man_production');
        })->count();

        $inProduction = ManufacturingOrder::where('status', 'in_progress')->count();
        $activeMachines = Machine::where('status', 'available')->count();

        $staff = $this->getFilteredStaff();

        return Inertia::render('Dashboard/MAN/Manager/index', [
            'stats' => [
                'receivedOrders' => $receivedOrders,
                'inProduction' => $inProduction,
                'activeMachines' => $activeMachines,
            ],
            'staff' => $staff,
        ]);
    }

    public function production()
    {
        $orders = PurchaseOrder::with(['client', 'items.product'])
            ->whereHas('queue', function ($q) {
                $q->where('stage', 'man_production');
            })
            ->get()
            ->map(function ($order) {
                $items = $order->items->map(fn($item) => [
                    'product_name' => $item->product->name,
                    'product_sku' => $item->product->sku,
                    'quantity' => $item->quantity,
                ]);
                return [
                    'id' => $order->id,
                    'po_number' => $order->po_number,
                    'client_name' => $order->client->company_name,
                    'total_quantity' => $order->items->sum('quantity'),
                    'items' => $items,
                    'created_at' => $order->created_at,
                ];
            });

        return Inertia::render('Dashboard/MAN/Manager/Production', [
            'orders' => $orders,
        ]);
    }

    public function rejected()
    {
        $user = $this->getCurrentManager();
        $rejectedForms = FormJob::where('status', 'rejected')
            ->with(['ironJob.squeezerJob.softenerJob.fabric', 'product', 'operator']);

        if ($user->is_manufacturing_supervisor && $user->supervisor_department) {
            $supervisedRoles = $user->supervised_roles;
            $rejectedForms->whereHas('operator', function ($q) use ($supervisedRoles) {
                $q->whereIn('manufacturing_role', $supervisedRoles);
            });
        }

        $rejectedForms = $rejectedForms->latest()->get()
            ->map(function ($form) {
                return [
                    'id' => $form->id,
                    'code' => $form->code,
                    'product_name' => $form->product->name,
                    'quantity' => $form->quantity,
                    'rejected_at' => $form->updated_at,
                    'rejected_by' => $form->operator->name,
                    'fabric_code' => $form->ironJob->squeezerJob->softenerJob->fabric->code ?? null,
                    'reason' => $form->remarks ?? 'No reason provided',
                ];
            });

        return Inertia::render('Dashboard/MAN/Manager/Rejected', [
            'rejectedItems' => $rejectedForms,
        ]);
    }

    public function forwardToChecker($orderId)
    {
        $order = PurchaseOrder::findOrFail($orderId);
        $totalQuantity = $order->items->sum('quantity');

        ManufacturingOrder::create([
            'purchase_order_id' => $order->id,
            'total_quantity' => $totalQuantity,
            'remaining_quantity' => $totalQuantity,
            'status' => 'pending',
            'notes' => 'Forwarded to manufacturing by ' . Auth::user()->name,
        ]);

        $order->queue()->update(['stage' => 'man_production']);
        return redirect()->back()->with('message', 'Order forwarded to production successfully.');
    }

    public function updateStaffRole(Request $request, $staffId)
    {
        $user = $this->getCurrentManager();
        $staff = User::findOrFail($staffId);

        if ($user->is_manufacturing_supervisor) {
            if (!$user->canSuperviseRole($staff->manufacturing_role)) {
                return back()->with('error', 'You are not allowed to update this staff member.');
            }
        }

        $validated = $request->validate([
            'manufacturing_role' => 'required|in:knitting_yarn,dyeing_color,dyeing_fabric_softener,dyeing_squeezer,dyeing_ironing,dyeing_forming,dyeing_packaging,maintenance_checker,checker_quality',
        ]);

        $staff->update(['manufacturing_role' => $validated['manufacturing_role']]);
        return redirect()->back()->with('message', "Staff role updated.");
    }

    public function sendToLogistics($packageId)
    {
        $package = Package::findOrFail($packageId);
        $package->update(['status' => 'delivered']);

        if ($package->manufacturing_order_id) {
            $order = $package->manufacturingOrder;
            $remaining = $order->remaining_quantity - $package->items->sum('quantity');
            $order->update(['remaining_quantity' => $remaining]);
            if ($remaining <= 0) {
                $order->update(['status' => 'completed']);
            }
        }

        return redirect()->back()->with('message', 'Package sent to logistics.');
    }
}