<?php

namespace App\Http\Controllers\logistics;

use App\Http\Controllers\Controller;
use App\Models\logistics\Delivery;
use Inertia\Inertia;

class ProofController extends Controller
{
    public function index()
    {
        $deliveries = Delivery::where('status', 'delivered')
            ->with(['proofOfDelivery', 'driver.user', 'truck'])
            ->latest()
            ->get();
        return Inertia::render('Dashboard/Logistics/Proof', ['deliveries' => $deliveries]);
    }
}