<?php

namespace App\Http\Controllers\ord;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class OrdOrdersController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $orders = PurchaseOrder::with('client')
            ->whereNotNull('delivery_date')
            ->whereYear('delivery_date', $year)
            ->whereMonth('delivery_date', $month)
            ->get()
            ->map(function ($order) {
                // Safely format the delivery date
                $deliveryDate = $order->delivery_date;
                $formattedDate = $deliveryDate ? Carbon::parse($deliveryDate)->format('Y-m-d') : null;

                return [
                    'id' => $order->id,
                    'po_number' => $order->po_number,
                    'client_name' => $order->client->company_name,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'delivery_date' => $formattedDate,
                ];
            });

        return Inertia::render('Dashboard/ORD/Orders', [
            'orders' => $orders,
            'currentYear' => (int) $year,
            'currentMonth' => (int) $month,
        ]);
    }
}