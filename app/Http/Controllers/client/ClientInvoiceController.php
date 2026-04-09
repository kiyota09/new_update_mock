<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClientInvoiceController extends Controller
{
    public function index()
    {
        $orders = PurchaseOrder::where('client_id', Auth::guard('client')->id())
            ->whereIn('status', ['approved', 'pending_client_approval'])
            ->latest()
            ->get();
        return Inertia::render('Client/Invoice', ['orders' => $orders]);
    }

    // Payment creation will be handled by Finance module later
}