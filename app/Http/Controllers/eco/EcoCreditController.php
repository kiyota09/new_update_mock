<?php

namespace App\Http\Controllers\eco;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CreditAccount;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EcoCreditController extends Controller
{
    public function index()
    {
        // Clients with their credit accounts and order counts
        $clients = Client::with(['creditAccount', 'purchaseOrders'])->get()->map(function ($client) {
            return [
                'id' => $client->id,
                'company_name' => $client->company_name,
                'contact_person' => $client->contact_person,
                'outstanding_balance' => $client->creditAccount ? $client->creditAccount->outstanding_balance : 0,
                'is_good_payer' => $client->creditAccount ? $client->creditAccount->is_good_payer : true,
                'orders_count' => $client->purchaseOrders->count(),
                'orders' => $client->purchaseOrders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'po_number' => $order->po_number,
                        'total_amount' => $order->total_amount,
                        'status' => $order->status,
                        'created_at' => $order->created_at,
                        'items_count' => $order->items->count(),
                    ];
                }),
            ];
        });

        // Orders pending credit review (status = 'credit_review')
        $pendingCreditReviews = PurchaseOrder::with('client')
            ->where('status', 'credit_review')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'po_number' => $order->po_number,
                    'client' => $order->client,
                    'total_amount' => $order->total_amount,
                    'created_at' => $order->created_at,
                ];
            });

        return Inertia::render('Dashboard/ECO/Credit', [
            'clients' => $clients,
            'pendingCreditReviews' => $pendingCreditReviews,
        ]);
    }

    public function approveCreditReview(PurchaseOrder $order)
    {
        if ($order->status !== 'credit_review') {
            return back()->withErrors(['error' => 'Order is not pending credit review.']);
        }

        DB::beginTransaction();
        try {
            $order->update(['status' => 'approved']);
            // Optionally update credit account or create an order queue entry
            DB::commit();
            return back()->with('success', "Order {$order->po_number} has been approved and is ready for pushing.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve credit review: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to approve order.']);
        }
    }
}