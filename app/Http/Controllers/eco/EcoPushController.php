<?php

namespace App\Http\Controllers\eco;

use App\Http\Controllers\Controller;
use App\Models\OrderQueue;
use App\Models\PurchaseOrder;
use App\Models\Client;
use App\Models\JobOrder; // Ensure this model exists and has the new fillable fields
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
        // 1. Pending Push: Now shows specific Job Orders that are waiting to be pushed
        $salesOrders = JobOrder::where('status', 'pending')
            ->with(['purchaseOrder.client'])
            ->latest()
            ->get();

        // 2. Already Pushed: POs that have been dispatched to other modules
        $pushedOrders = PurchaseOrder::whereHas('orderQueue')
            ->with(['client', 'orderQueue'])
            ->latest()
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'po_number' => $order->po_number,
                    'client' => $order->client,
                    'total_amount' => $order->total_amount,
                    'pushed_to' => strtoupper(str_replace('_', ' ', $order->orderQueue->stage)),
                    'pushed_at' => $order->orderQueue->created_at,
                ];
            });

        // 3. Created P.O.: Manual uploads (MPO prefix)
        $createdPOs = PurchaseOrder::where('po_number', 'like', 'MPO-%')
            ->with('client')
            ->latest()
            ->get();

        // 4. Data for Modals
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
     * Handle manual P.O. upload (Manual P.O. Modal).
     */
    public function manualStore(Request $request)
    {
        // dd($request->all()); // Debugging line to inspect incoming data structure for manual P.O. uploads.
        $request->validate([
            'client_id'  => 'required|exists:clients,id',
            'attachment' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        try {
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
        } catch (\Exception $e) {
            Log::error("Manual PO Error: " . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create P.O.']);
        }
    }

    /**
     * Handle Batch Job Order creation (Batch Modal).
     */
    public function manualJobOrder(Request $request)
    {
        $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'yarn_type'         => 'nullable|string',
            'design'            => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.color'     => 'required|string',
            'items.*.quantity'  => 'required|numeric',
            'items.*.description' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($request) {
                // Generate a single unique J.O. number for the entire batch
                $joNumber = 'JO-' . date('Ymd') . '-' . strtoupper(Str::random(4));

                foreach ($request->items as $index => $item) {
                    // Generate a Unique Control Number for every individual color row
                    // Format: CTL-Year-POID-ColorIndex
                    $controlNumber = 'CTL-' . date('y') . $request->purchase_order_id . '-' . ($index + 1);

                    JobOrder::create([
                        'purchase_order_id' => $request->purchase_order_id,
                        'jo_number'         => $joNumber,
                        'control_number'    => $controlNumber,
                        'yarn_type'         => $request->yarn_type,
                        'design'            => $request->design,
                        'color'             => $item['color'],
                        'quantity'          => $item['quantity'],
                        'description'       => $item['description'],
                        'status'            => 'pending'
                    ]);
                }
            });

            return back()->with('success', 'Batch Job Orders created successfully.');
        } catch (\Exception $e) {
            Log::error("JO Creation Error: " . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create Job Orders.']);
        }
    }

    /**
     * Push a Job Order to Supply Chain Module (SCM).
     */
    public function pushToSCM(JobOrder $order)
    {
        DB::transaction(function () use ($order) {
            // Update Job Order status
            $order->update(['status' => 'dispatched']);

            // Add to Order Queue
            OrderQueue::updateOrCreate(
                ['purchase_order_id' => $order->purchase_order_id],
                [
                    'stage' => 'scm', 
                    'notes' => "JO: {$order->jo_number} Color: {$order->color} pushed to SCM"
                ]
            );
        });

        return back()->with('success', "Job Order {$order->jo_number} ({$order->color}) pushed to SCM.");
    }

    /**
     * Push to Order Management Module.
     */
    public function pushToOrderManagement(JobOrder $order)
    {
        $order->update(['status' => 'dispatched']);

        OrderQueue::updateOrCreate(
            ['purchase_order_id' => $order->purchase_order_id],
            ['stage' => 'order_mgmt', 'notes' => "JO: {$order->jo_number} dispatched to Order Mgmt"]
        );

        return back()->with('success', "Job Order forwarded to Order Management.");
    }
}