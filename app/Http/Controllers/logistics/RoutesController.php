<?php

namespace App\Http\Controllers\logistics;

use App\Http\Controllers\Controller;
use App\Models\logistics\Route;
use Illuminate\Http\Request;

class RoutesController extends Controller
{
    public function index()
    {
        $routes = Route::all();
        return inertia('Dashboard/Logistics/Routes', ['routes' => $routes]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'origin' => 'required|string',
            'destination' => 'required|string',
            'distance_km' => 'required|numeric|min:0',
            'estimated_minutes' => 'required|integer|min:1',
            'waypoints' => 'nullable|array',
        ]);
        Route::create($validated);
        return back()->with('success', 'Route added.');
    }

    public function update(Request $request, Route $route)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'origin' => 'sometimes|string',
            'destination' => 'sometimes|string',
            'distance_km' => 'sometimes|numeric|min:0',
            'estimated_minutes' => 'sometimes|integer|min:1',
            'waypoints' => 'nullable|array',
        ]);
        $route->update($validated);
        return back()->with('success', 'Route updated.');
    }

    public function destroy(Route $route)
    {
        $route->delete();
        return back()->with('success', 'Route deleted.');
    }
}