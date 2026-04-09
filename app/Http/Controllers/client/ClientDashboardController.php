<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\eco\Inquiry;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClientDashboardController extends Controller
{
    public function index()
    {
        $client = Auth::guard('client')->user();
        $totalOrders = PurchaseOrder::where('client_id', $client->id)->count();
        $pendingInquiries = Inquiry::where('client_id', $client->id)->where('status', 'open')->count();
        $pendingQuotations = \App\Models\ClientQuotation::where('client_id', $client->id)
            ->where('status', 'sent')->count();

        return Inertia::render('Client/Dashboard', [
            'stats' => [
                'totalOrders' => $totalOrders,
                'pendingInquiries' => $pendingInquiries,
                'pendingQuotations' => $pendingQuotations,
            ],
        ]);
    }
}