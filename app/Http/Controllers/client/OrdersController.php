<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
// Assuming you have an Order or PurchaseOrder model
// use App\Models\PurchaseOrder; 

class OrdersController extends Controller
{
    /**
     * Display the partner's orders.
     * Route Name: client.orders
     */
    public function orders()
    {
        // Get the currently authenticated B2B client
        $client = Auth::guard('client')->user();

        // Fetch orders belonging to this client
        // $orders = $client->orders()->latest()->get(); 

        return Inertia::render('Client/Orders', [
            'client' => $client,
            // 'orders' => $orders,
        ]);
    }

    /**
     * Handle purchase order acceptance.
     * Route Name: client.orders.accept
     */
    public function acceptPurchaseOrder(Request $request, $id)
    {
        // Add your logic to update order status here
        
        return back()->with('message', 'Order accepted successfully.');
    }
}