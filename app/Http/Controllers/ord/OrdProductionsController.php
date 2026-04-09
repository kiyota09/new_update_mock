<?php

namespace App\Http\Controllers\ord;

use App\Http\Controllers\Controller;
use App\Models\ManufacturingOrder;
use App\Models\PurchaseOrder;
use Inertia\Inertia;

class OrdProductionsController extends Controller
{
    public function index()
    {
        $manufacturingOrders = ManufacturingOrder::with(['purchaseOrder.client'])
            ->where('status', '!=', 'completed')
            ->get()
            ->map(function ($mo) {
                $totalQty = $mo->total_quantity;
                $completedQty = $totalQty - $mo->remaining_quantity;
                $progress = $totalQty > 0 ? round(($completedQty / $totalQty) * 100) : 0;

                return [
                    'id' => $mo->id,
                    'po_number' => $mo->purchaseOrder->po_number,
                    'client_name' => $mo->purchaseOrder->client->company_name,
                    'total_quantity' => $totalQty,
                    'completed_quantity' => $completedQty,
                    'progress' => $progress,
                    'status' => $mo->status,
                    'created_at' => $mo->created_at->format('Y-m-d H:i'),
                ];
            });

        return Inertia::render('Dashboard/ORD/Productions', [
            'productions' => $manufacturingOrders,
        ]);
    }
}