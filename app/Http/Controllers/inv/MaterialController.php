<?php

namespace App\Http\Controllers\inv;

use App\Http\Controllers\Controller;
use App\Models\inv\Material;
use App\Models\Warehouse;
use App\Models\WarehouseStockItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaterialController extends Controller
{
    /**
     * Display a listing of materials.
     */
    public function index()
    {
        $user = auth()->user();

        // Determine which warehouses the user can see
        if ($user->role === 'CEO' || $user->position === 'secretary' || $user->position === 'general_manager') {
            $warehouses = Warehouse::all();
        } else {
            $warehouses = $user->warehouseAccess()->get();
        }

        $warehouseIds = $warehouses->pluck('id')->toArray();

        $materials = Material::orderBy('name')->get();

        $materialsWithStock = $materials->map(function ($material) use ($warehouseIds) {
            $totalStock = WarehouseStockItem::where('material_id', $material->id)
                ->whereIn('warehouse_id', $warehouseIds)
                ->where('status', 'in_stock')
                ->sum('quantity');

            $status = ($totalStock <= 0) ? 'Out of Stock' : (($totalStock <= $material->reorder_point) ? 'Low Stock' : 'In Stock');

            return [
                'id' => $material->id,
                'mat_id' => $material->mat_id,
                'name' => $material->name,
                'category' => $material->category,
                'unit' => $material->unit,
                'reorder_point' => (float) $material->reorder_point,
                'unit_cost' => (float) $material->unit_cost,
                'total_stock' => (float) $totalStock,
                'status' => $status,
            ];
        });

        return Inertia::render('Dashboard/Inventory/Materials', [
            'materials' => $materialsWithStock,
        ]);
    }

    /**
     * Alias for index() to match route expectation.
     */
    public function material()
    {
        return $this->index();
    }

    /**
     * Store a newly created material.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:Yarn,Dye,Supplies,Packaging',
            'unit' => 'required|in:Rolls,Kg,Pcs',
            'reorder_point' => 'required|integer|min:0',
            'unit_cost' => 'required|numeric|min:0',
        ]);

        $material = Material::create([
            'mat_id' => Material::nextMatId(),
            'name' => $validated['name'],
            'category' => $validated['category'],
            'unit' => $validated['unit'],
            'reorder_point' => $validated['reorder_point'],
            'unit_cost' => $validated['unit_cost'],
        ]);

        return redirect()->back()->with('success', 'Material added successfully.');
    }

    /**
     * Remove the specified material.
     */
    public function destroy($id)
    {
        $material = Material::findOrFail($id);

        // Prevent deletion if there is any stock item referencing this material
        $hasStock = WarehouseStockItem::where('material_id', $material->id)->exists();
        if ($hasStock) {
            return redirect()->back()->withErrors(['error' => 'Cannot delete material because it has existing stock records.']);
        }

        $material->delete();

        return redirect()->back()->with('success', 'Material deleted successfully.');
    }
}