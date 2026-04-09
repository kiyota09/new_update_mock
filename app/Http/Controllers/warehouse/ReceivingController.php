<?php

namespace App\Http\Controllers\warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\WarehouseReceiving;
use App\Models\WarehouseReceivingItem;
use App\Models\WarehouseStockItem;
use App\Models\inv\Material;
use App\Models\Scm\ScmPurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReceivingController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $warehouses = $user->role === 'CEO' ? Warehouse::all() : Warehouse::where('supervisor_id', $user->id)->get();
        
        $receivings = WarehouseReceiving::with(['warehouse', 'items.material'])
            ->whereIn('warehouse_id', $warehouses->pluck('id'))
            ->orderByDesc('received_at')
            ->get();

        $pendingPOs = ScmPurchaseOrder::with(['items.material'])
            ->where('status', 'shipping')
            ->get();

        $materials = Material::all();

        return Inertia::render('Dashboard/Warehouse/Receiving', [
            'receivings' => $receivings,
            'pendingPOs' => $pendingPOs,
            'materials' => $materials,
            'warehouses' => $warehouses,
        ]);
    }

    public function receive(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'po_id' => 'nullable|exists:scm_purchase_orders,id',
            'items' => 'required|array',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.expected_qty' => 'required|numeric|min:0',
            'items.*.received_qty' => 'required|numeric|min:0',
            'items.*.rejected_qty' => 'nullable|numeric|min:0',
            'items.*.reject_reason' => 'nullable|string',
        ]);

        $receivingNumber = 'RCV-'.date('Ymd').'-'.str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
        $receiving = WarehouseReceiving::create([
            'receiving_number' => $receivingNumber,
            'warehouse_id' => $data['warehouse_id'],
            'scm_purchase_order_id' => $data['po_id'],
            'received_at' => now(),
            'received_by' => auth()->id(),
            'status' => 'pending',
        ]);

        foreach ($data['items'] as $item) {
            $received = $item['received_qty'] ?? 0;
            $rejected = $item['rejected_qty'] ?? 0;
            $status = 'pending';
            if ($received > 0 && $rejected == 0) $status = 'accepted';
            elseif ($rejected > 0 && $received == 0) $status = 'rejected';
            elseif ($received > 0 && $rejected > 0) $status = 'partial';

            WarehouseReceivingItem::create([
                'receiving_id' => $receiving->id,
                'material_id' => $item['material_id'],
                'expected_qty' => $item['expected_qty'],
                'received_qty' => $received,
                'rejected_qty' => $rejected,
                'status' => $status,
                'reject_reason' => $item['reject_reason'] ?? null,
            ]);

            if ($received > 0) {
                $control = 'MAT-'.date('Y-m-d').'-'.str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
                $material = Material::find($item['material_id']);
                WarehouseStockItem::create([
                    'control_number' => $control,
                    'warehouse_id' => $data['warehouse_id'],
                    'shelf_id' => null,
                    'material_id' => $item['material_id'],
                    'quantity' => $received,
                    'unit' => $material->unit,
                    'received_at' => now(),
                    'received_by' => auth()->id(),
                    'purchase_order_id' => $data['po_id'],
                    'status' => 'in_stock',
                ]);
            }
        }

        $receiving->update(['status' => 'completed']);

        if ($data['po_id']) {
            $po = ScmPurchaseOrder::find($data['po_id']);
            if ($po && $po->status === 'shipping') {
                $po->update(['status' => 'delivered']);
            }
        }

        return redirect()->back()->with('success', 'Delivery received and stock added.');
    }
}