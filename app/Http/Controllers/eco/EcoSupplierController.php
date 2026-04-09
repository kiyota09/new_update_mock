<?php

namespace App\Http\Controllers\eco;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierConversation;
use App\Models\SupplierMessage;
use App\Models\SupplierRequest;
use App\Models\SupplierRequestItem;
use App\Models\CreditAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EcoSupplierController extends Controller
{
    /**
     * Display a list of active suppliers (for the conversation list).
     */
    public function index()
    {
        $suppliers = Supplier::where('status', 'approved')->get();

        // Load latest message for each supplier (optional)
        foreach ($suppliers as $supplier) {
            $supplier->latest_message = SupplierMessage::where('supplier_id', $supplier->id)
                ->latest()
                ->first();
        }

        return Inertia::render('Dashboard/ECO/SupplierList', [
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Show the conversation with a specific supplier.
     */
    public function conversation(Supplier $supplier)
    {
        $messages = SupplierMessage::where('supplier_id', $supplier->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $requests = SupplierRequest::where('supplier_id', $supplier->id)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Dashboard/ECO/EcoConversation', [
            'supplier' => $supplier,
            'messages' => $messages,
            'requests' => $requests,
        ]);
    }

    /**
     * Send a new message to the supplier.
     */
    public function sendMessage(Request $request, Supplier $supplier)
    {
        $request->validate([
            'message' => 'required|string',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('supplier-messages', 'public');
        }

        SupplierMessage::create([
            'supplier_id' => $supplier->id,
            'sender_type' => 'eco', // 'eco' or 'supplier'
            'message' => $request->message,
            'attachment' => $attachmentPath,
        ]);

        return back()->with('success', 'Message sent.');
    }

    /**
     * Schedule a meeting with the supplier.
     */
    public function setMeeting(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'scheduled_at' => 'required|date',
            'location' => 'required|string',
            'type' => 'required|string',
        ]);

        SupplierMessage::create([
            'supplier_id' => $supplier->id,
            'sender_type' => 'eco',
            'message' => "Meeting scheduled: {$data['type']} at {$data['location']} on {$data['scheduled_at']}",
            'meeting_data' => $data,
            'is_system_event' => true,
        ]);

        return back()->with('success', 'Meeting invite sent.');
    }

    /**
     * Check supplier's credit status (if applicable).
     */
    public function creditCheck(Supplier $supplier)
    {
        // If you have a credit_accounts table linked to suppliers (or use a different logic)
        $credit = CreditAccount::where('supplier_id', $supplier->id)->first();
        $outstanding = $credit ? $credit->outstanding_balance : 0;
        $isGood = $credit ? $credit->is_good_payer : true;

        return response()->json([
            'outstanding' => $outstanding,
            'is_good_payer' => $isGood,
        ]);
    }

    /**
     * Send a quotation / purchase request to the supplier.
     */
    public function sendRequest(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.material_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.specs' => 'nullable|string',
            'delivery_date' => 'required|date',
            'payment_terms' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $requestNumber = 'RQ-'.date('Ymd').'-'.str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            $supplierRequest = SupplierRequest::create([
                'request_number' => $requestNumber,
                'supplier_id' => $supplier->id,
                'delivery_date' => $validated['delivery_date'],
                'payment_terms' => $validated['payment_terms'],
                'notes' => $validated['notes'],
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                SupplierRequestItem::create([
                    'supplier_request_id' => $supplierRequest->id,
                    'material_name' => $item['material_name'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'unit_price' => $item['unit_price'] ?? 0,
                    'specs' => $item['specs'] ?? null,
                ]);
            }

            // Add system message
            SupplierMessage::create([
                'supplier_id' => $supplier->id,
                'sender_type' => 'eco',
                'message' => "Request {$requestNumber} has been sent. Please provide your quotation.",
                'is_system_event' => true,
            ]);

            DB::commit();
            return back()->with('success', "Request {$requestNumber} sent to supplier.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to send request: '.$e->getMessage()]);
        }
    }
}