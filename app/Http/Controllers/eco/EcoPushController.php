<?php

namespace App\Http\Controllers\eco;

use App\Http\Controllers\Controller;
use App\Models\OrderQueue;
use App\Models\PurchaseOrder;
use App\Models\Client;
use App\Models\SalesOrder; // Using SalesOrder as established
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EcoPushController extends Controller
{
    /**
     * Display the Push Center Dashboard.
     */
    public function index()
    {
        // 1. Pending Push: Shows specific Sales Orders (generated from accepted quotations)
        // We load the purchaseOrder relation to get the formatted PO number and the Client
        $salesOrders = SalesOrder::where('status', 'pending')
            ->with(['purchaseOrder.client'])
            ->latest()
            ->get();
        $client_name = Client::where('id', $salesOrders->first()->client_id ?? null)->value('company_name') ?? 'N/A'; 
        // 2. Already Pushed: Orders that have been dispatched to other modules
        // Mapping them to match the structure expected by your Vue frontend
        $pushedOrders = SalesOrder::where('status', '!=', 'pending')
            ->with(['purchaseOrder.client'])
            ->latest()
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'po_number' => $order->purchase_order_id, // This holds the formatted PO-2026-001 string
                    'client' => $order->purchase_order?->client,
                    'control_number' => $order->control_number,
                    'pushed_to' => 'SCM', // Usually pushed to SCM status
                    'pushed_at' => $order->updated_at,
                ];
            });

        // 3. Created P.O.: Manual uploads (if still required by your internal logic)
        $createdPOs = PurchaseOrder::where('po_number', 'like', 'MPO-%')
            ->with('client')
            ->latest()
            ->get();

        // 4. Data for reference (Modals/Selectors)
        $clients = Client::select('id', 'company_name')->orderBy('company_name')->get();
        
        $allPOs = PurchaseOrder::with('client:id,company_name')
            ->select('id', 'po_number', 'client_id')
            ->get();

        return Inertia::render('Dashboard/ECO/Push', [
            'salesOrders'  => $salesOrders,
            'pushedOrders' => $pushedOrders,
            'createdPOs'   => $createdPOs,
            'clients'      => $clients,
            'allPOs'       => $allPOs,
        ]);
    }

    /**
     * Push a Sales Order to Supply Chain Module (SCM).
     */
    public function pushToSCM($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $order = SalesOrder::findOrFail($id);

                // Update status
                $order->update(['status' => 'pushed_to_scm']);

                // Track in the Order Queue
                OrderQueue::updateOrCreate(
                    ['purchase_order_id' => $order->purchase_order_id],
                    [
                        'stage' => 'scm', 
                        'notes' => "Control No: {$order->control_number} Color: {$order->color} pushed to SCM"
                    ]
                );
            });

            return back()->with('success', "Order successfully pushed to SCM.");
        } catch (\Exception $e) {
            Log::error("Push SCM Error: " . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to push to SCM.']);
        }
    }

    /**
     * Push to Order Management Module.
     */
    public function pushToOrderManagement($id)
    {
        try {
            $order = SalesOrder::findOrFail($id);
            $order->update(['status' => 'pushed_to_ordermgmt']);

            OrderQueue::updateOrCreate(
                ['purchase_order_id' => $order->purchase_order_id],
                ['stage' => 'order_mgmt', 'notes' => "Control No: {$order->control_number} dispatched to Order Mgmt"]
            );

            return back()->with('success', "Order forwarded to Order Management.");
        } catch (\Exception $e) {
            Log::error("Push Order Mgmt Error: " . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to push to Order Management.']);
        }
    }

    /**
     * Manual Store and Manual Job Order methods kept if needed for manual staff overrides
     */
    public function manualStore(Request $request)
    {
        $request->validate([
            'client_id'  => 'required|exists:clients,id',
            'attachment' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        $path = $request->file('attachment')->store('manual_pos', 'public');

        PurchaseOrder::create([
            'client_id'       => $request->client_id,
            'po_number'       => 'MPO-' . strtoupper(Str::random(8)),
            'status'          => 'approved',
            'subtotal'        => 0,
            'total_amount'    => 0,
            'attachment_path' => $path,
            'notes'           => 'Manually uploaded via ECO Push Center',
        ]);

        return back()->with('success', 'Manual P.O. successfully created.');
    }
}