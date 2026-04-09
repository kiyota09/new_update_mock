<?php

namespace App\Http\Controllers\warehouse;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\WarehouseSection;
use App\Models\WarehouseShelf;
use App\Models\WarehouseStockItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MonitorController extends Controller
{
    /**
     * Display the warehouse monitor with grid and stock.
     * Accessible by CEO, Warehouse Supervisor, or Warehouse Manager.
     */
    public function show(Warehouse $warehouse)
    {
        $user = auth()->user();
        
        // Combined access control for all relevant administrative roles
        if ($user->role !== 'CEO' && 
            $warehouse->supervisor_id !== $user->id && 
            $warehouse->manager_id !== $user->id) {
            abort(403, 'You do not have access to this warehouse.');
        }

        $sections = $warehouse->sections()
            ->with(['shelves.stockItems.material'])
            ->get();

        $unassignedStock = WarehouseStockItem::where('warehouse_id', $warehouse->id)
            ->whereNull('shelf_id')
            ->with('material')
            ->get();

        return Inertia::render('Dashboard/Warehouse/Monitor', [
            'warehouse' => $warehouse,
            'sections' => $sections,
            'unassignedStock' => $unassignedStock,
        ]);
    }

    /**
     * Update the grid layout, sections, and shelves.
     * Uses a database transaction to ensure data integrity during rebuild.
     */
    public function updateLayout(Request $request, Warehouse $warehouse)
    {
        $user = auth()->user();
        if ($user->role !== 'CEO' && $warehouse->supervisor_id !== $user->id) {
            abort(403, 'Unauthorized to update warehouse layout.');
        }

        $data = $request->validate([
            'grid_rows' => 'required|integer|min:1|max:20',
            'grid_cols' => 'required|integer|min:1|max:20',
            'sections' => 'array',
            'sections.*.name' => 'required|string|max:255',
            'sections.*.row' => 'required|integer|min:0',
            'sections.*.col' => 'required|integer|min:0',
            'sections.*.capacity' => 'nullable|integer',
            'sections.*.shelves' => 'array',
            'sections.*.shelves.*' => 'string|max:50',
        ]);

        try {
            DB::beginTransaction();

            // Clear existing layout to perform a clean rebuild
            $warehouse->sections()->delete();

            foreach ($data['sections'] as $sec) {
                $section = WarehouseSection::create([
                    'warehouse_id' => $warehouse->id,
                    'name' => $sec['name'],
                    'grid_row' => $sec['row'],
                    'grid_col' => $sec['col'],
                    'capacity' => $sec['capacity'] ?? null,
                ]);

                foreach ($sec['shelves'] as $shelfNumber) {
                    if (!empty($shelfNumber)) {
                        WarehouseShelf::create([
                            'section_id' => $section->id,
                            'shelf_number' => $shelfNumber,
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Warehouse layout updated successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Layout save error: ' . $e->getMessage(), [
                'warehouse_id' => $warehouse->id,
                'data' => $data,
            ]);
            return redirect()->back()->withErrors(['error' => 'Failed to save layout: ' . $e->getMessage()]);
        }
    }

    /**
     * Assign a specific stock item to a shelf.
     */
    public function assignToShelf(Request $request)
    {
        $data = $request->validate([
            'stock_item_id' => 'required|exists:warehouse_stock_items,id',
            'shelf_id' => 'required|exists:warehouse_shelves,id',
        ]);

        $stock = WarehouseStockItem::findOrFail($data['stock_item_id']);
        $warehouse = $stock->warehouse;
        $user = auth()->user();
        
        if ($user->role !== 'CEO' && $warehouse->supervisor_id !== $user->id) {
            abort(403, 'Unauthorized to assign stock.');
        }

        $stock->update(['shelf_id' => $data['shelf_id']]);

        return redirect()->back()->with('success', 'Material successfully assigned to shelf.');
    }

    /**
     * Consume material and send it to a specific manufacturing department.
     */
    public function useMaterial(Request $request, WarehouseStockItem $stockItem)
    {
        $data = $request->validate([
            'quantity' => 'required|numeric|min:0.01|max:' . $stockItem->quantity,
            'manufacturing_department' => 'required|string|in:knitting,dyeing,maintenance,packaging',
        ]);

        $user = auth()->user();
        $warehouse = $stockItem->warehouse;

        if ($user->role !== 'CEO' && $warehouse->supervisor_id !== $user->id) {
            abort(403, 'Unauthorized to process material consumption.');
        }

        if ($data['quantity'] >= $stockItem->quantity) {
            // Full consumption: mark as used and clear location
            $stockItem->update([
                'status' => 'used',
                'quantity' => 0,
                'shelf_id' => null,
            ]);
        } else {
            // Partial consumption
            $stockItem->decrement('quantity', $data['quantity']);
        }

        return redirect()->back()->with('success', "{$data['quantity']} {$stockItem->unit}(s) successfully sent to {$data['manufacturing_department']} department.");
    }
}