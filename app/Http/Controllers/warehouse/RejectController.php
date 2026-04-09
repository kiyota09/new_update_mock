<?php

namespace App\Http\Controllers\warehouse;

use App\Http\Controllers\Controller;
use App\Models\WarehouseReject;
use Inertia\Inertia;

class RejectController extends Controller
{
    public function index()
    {
        $rejects = WarehouseReject::with('warehouse')->orderByDesc('created_at')->get();
        return Inertia::render('Dashboard/Warehouse/Rejects', ['rejects' => $rejects]);
    }
}