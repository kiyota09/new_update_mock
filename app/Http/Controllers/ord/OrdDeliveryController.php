<?php

namespace App\Http\Controllers\ord;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Inertia\Inertia;

class OrdDeliveryController extends Controller
{
    public function index()
    {
        // Orders that are in shipping/delivery stage (status = 'shipped' or 'delivery')
        $deliveries = PurchaseOrder::with('client')
            ->whereIn('status', ['shipped', 'delivery', 'in_transit'])
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'po_number' => $order->po_number,
                    'client_name' => $order->client->company_name,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'delivery_date' => optional($order->delivery_date)->format('Y-m-d'),
                    'tracking_number' => $order->tracking_number ?? 'N/A',
                ];
            });

        return Inertia::render('Dashboard/ORD/Delivery', [
            'deliveries' => $deliveries,
        ]);
    }

    // Placeholder for future live tracking (Logistics module)
    public function track($id)
    {
        // Will integrate with Logistics module later
        return response()->json(['message' => 'Tracking endpoint - coming soon']);
    }
}