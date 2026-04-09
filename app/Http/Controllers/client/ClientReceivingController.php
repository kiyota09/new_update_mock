<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\OrderQueue;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClientReceivingController extends Controller
{
    public function index()
    {
        $orders = PurchaseOrder::where('client_id', Auth::guard('client')->id())
            ->whereHas('orderQueue', function($q) {
                $q->where('stage', 'completed'); // completed by logistics
            })
            ->get();
        return Inertia::render('Client/Receiving', ['orders' => $orders]);
    }

    public function markReceived(PurchaseOrder $order)
    {
        if ($order->client_id !== Auth::guard('client')->id()) {
            abort(403);
        }
        // Update delivery status – this would be linked to Logistics module
        // For now, just add a note
        $order->update(['notes' => ($order->notes ? $order->notes."\n" : '')."Client marked as received on ".now()]);
        return back()->with('success', 'Delivery confirmed. Thank you!');
    }
}