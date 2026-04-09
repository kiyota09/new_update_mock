<?php

namespace App\Http\Controllers\warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\UserModuleAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WarehouseController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Get warehouses based on user role/permissions
        if ($user->role === 'CEO' || $user->position === 'secretary' || $user->position === 'general_manager') {
            $warehouses = Warehouse::with('supervisor')->get();
        } else {
            $warehouses = Warehouse::where('supervisor_id', $user->id)->get();
        }

        // Get users that have WAR module access (eligible to be warehouse supervisors)
        $warAccessUserIds = UserModuleAccess::where('module', 'WAR')->pluck('user_id')->toArray();
        // Also include users with position secretary or general_manager (they can oversee)
        $elevatedUserIds = User::whereIn('position', ['secretary', 'general_manager'])->pluck('id')->toArray();
        $eligibleIds = array_unique(array_merge($warAccessUserIds, $elevatedUserIds));
        
        // Also include the current CEO if they are managing warehouses (optional)
        if ($user->role === 'CEO') {
            $eligibleIds[] = $user->id;
        }
        
        $users = User::whereIn('id', $eligibleIds)
            ->orderBy('name')
            ->get(['id', 'name', 'position']);

        return Inertia::render('Dashboard/Warehouse/Index', [
            'warehouses' => $warehouses,
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'supervisor_id' => 'nullable|exists:users,id',
            // manager_id removed
        ]);
        
        // Set default color if not provided
        $data['color'] = $data['color'] ?? 'blue';
        
        Warehouse::create($data);
        
        return redirect()->back()->with('success', 'Warehouse created successfully.');
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'supervisor_id' => 'nullable|exists:users,id',
            // manager_id removed
        ]);
        
        $warehouse->update($data);
        
        return redirect()->back()->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();
        return redirect()->back()->with('success', 'Warehouse deleted successfully.');
    }
}