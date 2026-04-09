<?php

namespace App\Http\Controllers\SUPPLIERS;

use App\Http\Controllers\Controller;
use App\Models\Scm\PurchaseInvoice;
use App\Models\Scm\RequestForQuotation;
use App\Models\Scm\RfqResponse;
use App\Models\Scm\ScmPurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierDashboardController extends Controller
{
    public function index()
    {
        $supplier = auth('supplier')->user();
        $supplierId = $supplier->id;

        $allRfqs = RequestForQuotation::with(['responses' => function ($q) use ($supplierId) {
            $q->where('supplier_id', $supplierId);
        }])
            ->orderBy('created_at', 'desc')
            ->get();

        $rfqs = $allRfqs->filter(function ($rfq) use ($supplierId) {
            $ids = is_string($rfq->supplier_ids) ? json_decode($rfq->supplier_ids, true) : $rfq->supplier_ids;
            if (! is_array($ids)) return false;
            return in_array($supplierId, $ids) || in_array((string) $supplierId, $ids);
        })->values()->map(fn ($rfq) => [
            'id' => $rfq->id,
            'rfq_number' => $rfq->rfq_number,
            'material_name' => $rfq->material_name,
            'category' => $rfq->category,
            'unit' => $rfq->unit,
            'required_qty' => (int) $rfq->required_qty,
            'deadline' => $rfq->deadline,
            'delivery_address' => $rfq->delivery_address ?? 'Main Warehouse',
            'payment_terms' => $rfq->payment_terms,
            'notes' => $rfq->notes,
            'status' => $rfq->status,
            'my_response' => $rfq->responses->first(),
        ]);

        return Inertia::render('SUPPLIER/supplierDashboard', [
            'auth' => [
                'user' => $supplier,
                'supplier' => $supplier,
            ],
            'stats' => [
                'activeRFQs' => $rfqs->where('deadline', '>=', now()->toDateString())->count(),
                'pendingResponses' => $rfqs->whereNull('my_response')->count(),
                'submittedQuotes' => $rfqs->whereNotNull('my_response')->count(),
            ],
            'rfqs' => $rfqs,
        ]);
    }

    public function submitQuotation(Request $request, $rfqId)
    {
        $supplier = auth('supplier')->user();

        $validated = $request->validate([
            'unit_price' => 'required|numeric|min:0.01',
            'lead_time' => 'required|string|max:255',
            'validity_date' => 'required|date|after_or_equal:today',
            'payment_terms' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $rfq = RequestForQuotation::findOrFail($rfqId);

        $totalPrice = $validated['unit_price'] * $rfq->required_qty;

        RfqResponse::create([
            'rfq_id' => $rfq->id,
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->business_name,
            'unit_price' => $validated['unit_price'],
            'total_price' => $totalPrice,
            'lead_time' => $validated['lead_time'],
            'validity_date' => $validated['validity_date'],
            'payment_terms' => $validated['payment_terms'],
            'notes' => $validated['notes'],
            'status' => 'pending_review',
            'submitted_at' => now(),
        ]);

        $existingResponses = RfqResponse::where('rfq_id', $rfq->id)->count();
        $rfq->update(['status' => $existingResponses >= 1 ? 'responded' : 'partial_response']);

        return redirect()->back()->with('success', 'Quotation submitted successfully!');
    }

    public function purchaseOrders()
    {
        $supplier = auth('supplier')->user();

        $orders = ScmPurchaseOrder::where('supplier_id', $supplier->id)
            ->where('status', '!=', 'draft')
            ->with(['items', 'invoices.payments'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('SUPPLIER/supplierOrders', [
            'auth' => [
                'user' => $supplier,
                'supplier' => $supplier,
            ],
            'orders' => $orders,
        ]);
    }

    /**
     * Supplier can only update to 'production' or 'shipping'
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:production,shipping',
        ]);

        $po = ScmPurchaseOrder::where('supplier_id', auth('supplier')->id())->findOrFail($id);
        $po->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', 'Order status updated to '.$validated['status']);
    }

    public function createInvoice(Request $request, $id)
    {
        $validated = $request->validate([
            'invoice_number' => 'required|string|unique:purchase_invoices,invoice_number',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $po = ScmPurchaseOrder::where('supplier_id', auth('supplier')->id())->findOrFail($id);

        PurchaseInvoice::create([
            'invoice_number' => $validated['invoice_number'],
            'po_id' => $po->id,
            'po_number' => $po->po_number,
            'supplier_id' => auth('supplier')->id(),
            'supplier_name' => auth('supplier')->user()->business_name,
            'invoice_date' => $validated['invoice_date'],
            'due_date' => $validated['due_date'],
            'amount' => $validated['amount'],
            'payment_terms' => 'Standard',
            'status' => 'unpaid',
            'received_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Invoice generated and sent to SCM Accounting.');
    }
}