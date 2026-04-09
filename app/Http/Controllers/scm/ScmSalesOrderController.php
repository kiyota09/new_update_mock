<?php

namespace App\Http\Controllers\scm;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\OrderQueue;
use App\Models\MaterialRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScmSalesOrderController extends Controller
{
    public function index()
    {
        // Orders from ECO that are approved and not yet pushed to production
        $orders = PurchaseOrder::with(['client', 'items.product'])
            ->whereHas('queue', function ($q) {
                $q->whereIn('stage', ['eco_approved', 'scm_received', 'inv_check']);
            })
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($order) {
                $queue = $order->queue;
                return [
                    'id' => $order->id,
                    'po_number' => $order->po_number,
                    'client_name' => $order->client->company_name,
                    'total_amount' => $order->total_amount,
                    'created_at' => $order->created_at,
                    'stage' => $queue ? $queue->stage : 'eco_approved',
                    'inv_check_sufficient' => $queue ? $queue->inv_check_sufficient : null,
                    'items' => $order->items->map(fn($item) => [
                        'product_name' => $item->product->name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                    ]),
                ];
            });

        return Inertia::render('Dashboard/SCM/SalesOrder', [
            'orders' => $orders,
        ]);
    }

    public function checkInventory(PurchaseOrder $order)
    {
        // Notify Inventory module (Checker) – we simply update the queue stage to 'inv_check'
        // The actual check will be done by Inventory Checker controller.
        $queue = $order->queue;
        if (!$queue) {
            $queue = OrderQueue::create([
                'purchase_order_id' => $order->id,
                'stage' => 'inv_check',
            ]);
        } else {
            $queue->update(['stage' => 'inv_check']);
        }

        return redirect()->back()->with('success', 'Inventory check requested. Please wait for the Inventory module to respond.');
    }

    public function pushToProduction(PurchaseOrder $order)
    {
        $queue = $order->queue;
        if (!$queue || $queue->stage !== 'inv_checked' || !$queue->inv_check_sufficient) {
            return redirect()->back()->withErrors(['error' => 'Order cannot be pushed to production. Inventory check must be sufficient first.']);
        }

        $queue->update(['stage' => 'man_production']);

        return redirect()->back()->with('success', 'Order pushed to Manufacturing module.');
    }
}