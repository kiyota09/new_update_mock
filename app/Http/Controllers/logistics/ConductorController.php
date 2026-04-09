<?php

namespace App\Http\Controllers\logistics;

use App\Http\Controllers\Controller;
use App\Models\logistics\Delivery;
use App\Models\logistics\ConductorReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConductorController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $conductor = $user->conductor;

        if (!$conductor) {
            abort(403);
        }

        $deliveries = Delivery::where(function ($q) use ($conductor) {
            $q->where('conductor1_id', $conductor->id)
              ->orWhere('conductor2_id', $conductor->id);
        })->whereIn('status', ['dispatched', 'in_transit', 'delivered'])
          ->with(['driver.user', 'route', 'packages'])
          ->get();

        return inertia('Dashboard/Logistics/ConductorPortal', ['deliveries' => $deliveries]);
    }

    public function storeReport(Request $request, Delivery $delivery)
    {
        $user = Auth::user();
        $conductor = $user->conductor;

        if (!$conductor || ($delivery->conductor1_id !== $conductor->id && $delivery->conductor2_id !== $conductor->id)) {
            abort(403);
        }

        $request->validate(['report' => 'required|string']);

        ConductorReport::create([
            'delivery_id' => $delivery->id,
            'conductor_id' => $conductor->id,
            'report_text' => $request->report,
        ]);

        return back()->with('success', 'Report submitted.');
    }
}