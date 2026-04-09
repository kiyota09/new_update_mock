<?php

namespace App\Http\Controllers\inv;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvAccessController extends Controller
{
    /**
     * Display the inventory access control page.
     */
    public function index()
    {
        // Get all users who can be granted inventory access (secretary, general_manager, manager, supervisor)
        $users = User::whereIn('position', ['secretary', 'general_manager', 'manager', 'supervisor'])->get();
        $warehouses = Warehouse::all();

        // Get current inventory access permissions per user (warehouse ids they can access)
        $inventoryAccess = [];
        foreach ($users as $user) {
            $inventoryAccess[$user->id] = [
                'dashboard' => $user->hasInventoryAccess(),  // simplified: if any inventory access exists
                'materials' => $user->hasInventoryAccess(),
                'products' => $user->hasInventoryAccess(),
                'bom' => $user->hasInventoryAccess(),
                'checker' => $user->hasInventoryAccess(),
                // For more granular control, you could store specific page permissions in a pivot table
            ];
        }

        return Inertia::render('Dashboard/Inventory/Access', [
            'users' => $users,
            'warehouses' => $warehouses,
            'permissions' => $inventoryAccess,
        ]);
    }

    /**
     * Update inventory access permissions for a user.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'permissions' => 'required|array',
            'permissions.dashboard' => 'boolean',
            'permissions.materials' => 'boolean',
            'permissions.products' => 'boolean',
            'permissions.bom' => 'boolean',
            'permissions.checker' => 'boolean',
        ]);

        $user = User::findOrFail($data['user_id']);

        // For now, we simplify: if any permission is true, we grant inventory access by syncing all warehouses
        // In a more granular system, you would store page-specific permissions.
        $hasAny = array_filter($data['permissions']);

        if (!empty($hasAny)) {
            // Grant access to all warehouses (or a specific set, depending on your logic)
            $warehouseIds = \App\Models\Warehouse::pluck('id')->toArray();
            $user->inventoryAccess()->sync($warehouseIds);
        } else {
            $user->inventoryAccess()->detach();
        }

        return redirect()->back()->with('success', 'Inventory access updated successfully.');
    }
}