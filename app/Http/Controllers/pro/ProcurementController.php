<?php

namespace App\Http\Controllers\pro;

use App\Http\Controllers\Controller;
use App\Models\inv\Warehouse;
use App\Models\Scm\MaterialRequest;
use App\Models\Scm\PurchaseInvoice;
use App\Models\Scm\RequestForQuotation;
use App\Models\Scm\RfqResponse;
use App\Models\Scm\ScmPurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProcurementController extends Controller
{
    public function materialRequests()
    {
        $warehouses = Warehouse::orderBy('name')->get()->map(fn ($w) => [
            'id' => $w->id,
            'name' => $w->name,
            'location' => $w->location,
        ]);

        $suppliers = Supplier::whereHas('vendorRegistration', fn ($q) => $q->where('status', 'approved'))
            ->with('vendorRegistration.requirements')
            ->orderBy('business_name')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'business_name' => $s->business_name,
                'representative_name' => $s->representative_name ?? '',
                'email' => $s->email,
                'phone_number' => $s->phone_number,
                'address' => $s->address ?? '',
                'rating' => $s->rating ?? 5.0,
                'status' => 'Official Vendor',
                'categories' => $s->categories ? json_decode($s->categories, true) : ['Fabric', 'Trim', 'Chemicals', 'Packaging'],
                'requirements' => collect($s->vendorRegistration?->requirements ?? [])->map(fn ($req) => [
                    'name' => $req->requirement_name,
                    'value' => $req->value,
                ])->toArray(),
            ]);

        $requests = MaterialRequest::with('material')
            ->where('status', 'forwarded')
            ->orderByRaw("FIELD(urgency, 'High', 'Medium', 'Low')")
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'req_number' => $r->req_number,
                'material_name' => $r->material?->name ?? $r->material_name,
                'category' => $r->material?->category ?? $r->category,
                'unit' => $r->material?->unit ?? $r->unit,
                'required_qty' => $r->required_qty,
                'urgency' => $r->urgency,
                'requested_by' => $r->requested_by,
                'requested_at' => $r->requested_at,
                'notes' => $r->notes,
                'status' => $r->status,
            ]);

        return Inertia::render('Dashboard/PRO/mat_req', [
            'materialRequests' => $requests,
            'warehouses' => $warehouses,
            'suppliers' => $suppliers,
            'stats' => ['total' => $requests->count()],
        ]);
    }

    public function createRFQ(Request $request)
    {
        $validated = $request->validate([
            'mr_id' => 'required|exists:material_requests,id',
            'deadline' => 'required|date|after_or_equal:today',
            'delivery_address' => 'required|string',
            'payment_terms' => 'required|string',
            'notes' => 'nullable|string',
            'selected_suppliers' => 'required|array|min:1',
            'selected_suppliers.*' => 'exists:suppliers,id',
        ]);

        DB::beginTransaction();
        try {
            $mr = MaterialRequest::findOrFail($validated['mr_id']);

            RequestForQuotation::create([
                'rfq_number' => $this->generateRFQNumber(),
                'mr_ref' => $mr->req_number,
                'mr_id' => $mr->id,
                'material_id' => $mr->material_id,
                'material_name' => $mr->material_name,
                'category' => $mr->category,
                'unit' => $mr->unit,
                'required_qty' => $mr->required_qty,
                'deadline' => $validated['deadline'],
                'delivery_address' => $validated['delivery_address'],
                'payment_terms' => $validated['payment_terms'],
                'notes' => $validated['notes'],
                'sent_at' => now(),
                'status' => 'sent',
                'supplier_ids' => json_encode($validated['selected_suppliers']),
            ]);

            $mr->update(['status' => 'rfq_sent']);

            DB::commit();

            return redirect()->back()->with('success', 'RFQ sent to selected suppliers.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('RFQ creation failed: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to create RFQ.']);
        }
    }

    public function supplierQuotations()
    {
        $rfqs = RequestForQuotation::with(['responses.supplier'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($rfq) => [
                'id' => $rfq->id,
                'rfq_number' => $rfq->rfq_number,
                'mr_ref' => $rfq->mr_ref,
                'material_name' => $rfq->material_name,
                'category' => $rfq->category,
                'unit' => $rfq->unit,
                'required_qty' => $rfq->required_qty,
                'deadline' => $rfq->deadline,
                'delivery_address' => $rfq->delivery_address,
                'sent_at' => $rfq->sent_at,
                'status' => $rfq->status,
                'notes' => $rfq->notes,
                'responses' => $rfq->responses->map(fn ($res) => [
                    'id' => $res->id,
                    'supplier_id' => $res->supplier_id,
                    'supplier_name' => $res->supplier?->business_name ?? $res->supplier_name,
                    'unit_price' => $res->unit_price,
                    'total_price' => $res->total_price,
                    'lead_time' => $res->lead_time,
                    'validity_date' => $res->validity_date,
                    'payment_terms' => $res->payment_terms,
                    'notes' => $res->notes,
                    'status' => $res->status,
                    'submitted_at' => $res->submitted_at,
                ]),
            ]);

        return Inertia::render('Dashboard/PRO/supp_quo', ['rfqs' => $rfqs]);
    }

    public function acceptQuotation(Request $request, $responseId)
    {
        $validated = $request->validate(['rfq_id' => 'required|exists:request_for_quotations,id']);

        DB::beginTransaction();
        try {
            $response = RfqResponse::with('rfq')->findOrFail($responseId);
            $rfq = $response->rfq;
            $supplier = Supplier::findOrFail($response->supplier_id);

            $response->update(['status' => 'accepted']);
            RfqResponse::where('rfq_id', $rfq->id)
                ->where('id', '!=', $responseId)
                ->where('status', 'pending_review')
                ->update(['status' => 'declined']);

            $subtotal = $response->total_price;
            $taxRate = 10;
            $taxAmount = $subtotal * ($taxRate / 100);
            $grandTotal = $subtotal + $taxAmount;

            $po = ScmPurchaseOrder::create([
                'po_number' => $this->generatePONumber(),
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->business_name,
                'rfq_ref' => $rfq->rfq_number,
                'rfq_id' => $rfq->id,
                'issued_date' => now(),
                'expected_delivery' => $response->lead_time,
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'notes' => '',
                'received' => false,
            ]);

            $po->items()->create([
                'material_id' => $rfq->material_id,
                'material_name' => $rfq->material_name,
                'qty' => $rfq->required_qty,
                'unit' => $rfq->unit,
                'unit_price' => $response->unit_price,
                'total' => $response->total_price,
            ]);

            $rfq->update(['status' => 'responded']);

            DB::commit();

            return redirect()->back()->with('success', 'Quotation accepted. Purchase order created as draft.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Accept quotation failed: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to accept quotation.']);
        }
    }

    public function declineQuotation(Request $request, $responseId)
    {
        $validated = $request->validate(['reason' => 'nullable|string|max:500']);
        try {
            $response = RfqResponse::findOrFail($responseId);
            $response->update(['status' => 'declined', 'decline_reason' => $validated['reason']]);

            return redirect()->back()->with('success', 'Quotation declined.');
        } catch (\Exception $e) {
            Log::error('Decline quotation failed: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to decline quotation.']);
        }
    }

    public function receipt()
    {
        $purchaseOrders = ScmPurchaseOrder::with('items')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($po) => [
                'id' => $po->id,
                'po_number' => $po->po_number,
                'supplier_name' => $po->supplier_name,
                'rfq_ref' => $po->rfq_ref,
                'issued_date' => $po->issued_date,
                'expected_delivery' => $po->expected_delivery,
                'status' => $po->status,
                'grand_total' => $po->grand_total,
                'items' => $po->items->map(fn ($item) => [
                    'id' => $item->id,
                    'material_name' => $item->material_name,
                    'qty' => $item->qty,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total,
                ]),
            ]);

        $invoices = PurchaseInvoice::orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'po_number' => $inv->po_number,
                'supplier_name' => $inv->supplier_name,
                'invoice_date' => $inv->invoice_date,
                'due_date' => $inv->due_date,
                'amount' => $inv->amount,
                'payment_terms' => $inv->payment_terms,
                'status' => $inv->status,
            ]);

        return Inertia::render('Dashboard/PRO/receipt', [
            'purchaseOrders' => $purchaseOrders,
            'invoices' => $invoices,
        ]);
    }

    public function sendPurchaseOrder($poId)
    {
        try {
            $po = ScmPurchaseOrder::findOrFail($poId);
            $po->update(['status' => 'sent']);

            return redirect()->back()->with('success', 'Purchase Order sent to supplier.');
        } catch (\Exception $e) {
            Log::error('Send PO failed: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Failed to send PO.']);
        }
    }

    private function generateRFQNumber(): string
    {
        $year = now()->format('Y');
        $count = RequestForQuotation::whereYear('created_at', $year)->count() + 1;

        return 'RFQ-'.$year.'-'.str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    private function generatePONumber(): string
    {
        $year = now()->format('Y');
        $count = ScmPurchaseOrder::whereYear('created_at', $year)->count() + 1;

        return 'SCMPO-'.$year.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
