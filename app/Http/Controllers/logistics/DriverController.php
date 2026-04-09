<?php

namespace App\Http\Controllers\logistics;

use App\Http\Controllers\Controller;
use App\Models\logistics\Delivery;
use Illuminate\Support\Facades\Auth;

class DriverController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $driver = $user->driver;

        if (!$driver) {
            abort(403, 'You are not a registered driver.');
        }

        $deliveries = Delivery::where('driver_id', $driver->id)
            ->whereIn('status', ['dispatched', 'in_transit'])
            ->with(['packages', 'route', 'conductor1.user', 'conductor2.user'])
            ->get();

        return inertia('Dashboard/Logistics/DriverPortal', ['deliveries' => $deliveries]);
    }

    public function markInTransit(Delivery $delivery)
    {
        $this->authorizeDriver($delivery);
        $delivery->update(['status' => 'in_transit', 'actual_departure' => now()]);
        return back()->with('success', 'Trip started.');
    }

    public function uploadProof(Request $request, Delivery $delivery)
    {
        $this->authorizeDriver($delivery);
        $request->validate([
            'image' => 'required|image|max:5120',
            'notes' => 'nullable|string',
        ]);

        $path = $request->file('image')->store('proof_of_delivery', 'public');

        \App\Models\logistics\ProofOfDelivery::create([
            'delivery_id' => $delivery->id,
            'image_path' => $path,
            'notes' => $request->notes,
            'delivered_at' => now(),
        ]);

        $delivery->update(['status' => 'delivered', 'arrival_time' => now()]);

        // Update package statuses to delivered
        foreach ($delivery->packages as $package) {
            $package->update(['status' => 'delivered']);
        }

        // Free resources
        if ($delivery->truck) $delivery->truck->update(['status' => 'available']);
        if ($delivery->driver) $delivery->driver->update(['is_available' => true]);
        if ($delivery->conductor1) $delivery->conductor1->update(['is_available' => true]);
        if ($delivery->conductor2) $delivery->conductor2->update(['is_available' => true]);

        return back()->with('success', 'Delivery completed and proof uploaded.');
    }

    private function authorizeDriver(Delivery $delivery)
    {
        $driver = Auth::user()->driver;
        if (!$driver || $delivery->driver_id !== $driver->id) {
            abort(403);
        }
    }
}