<?php

namespace App\Http\Controllers\inv;

use App\Http\Controllers\Controller;
use App\Models\inv\Material;
use App\Models\Warehouse;
use App\Models\WarehouseStockItem;
use Inertia\Inertia;

class InvDashboardController extends Controller
{
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

        // Get all materials
        $materials = Material::all();

        // Build materials with stock per warehouse (for the table)
        $materialsWithStock = $materials->map(function ($mat) use ($warehouseIds) {
            $totalStock = WarehouseStockItem::where('material_id', $mat->id)
                ->whereIn('warehouse_id', $warehouseIds)
                ->where('status', 'in_stock')
                ->sum('quantity');

            $status = ($totalStock <= 0) ? 'Out of Stock' : (($totalStock <= $mat->reorder_point) ? 'Low Stock' : 'In Stock');

            // Stock per warehouse (for supervisor toggle)
            $stockPerWarehouse = [];
            foreach ($warehouseIds as $wid) {
                $stockPerWarehouse[$wid] = (float) WarehouseStockItem::where('material_id', $mat->id)
                    ->where('warehouse_id', $wid)
                    ->where('status', 'in_stock')
                    ->sum('quantity');
            }

            return [
                'id' => $mat->id,
                'mat_id' => $mat->mat_id,
                'name' => $mat->name,
                'category' => $mat->category,
                'unit' => $mat->unit,
                'reorder_point' => $mat->reorder_point,
                'unit_cost' => (float) $mat->unit_cost,
                'total_stock' => (float) $totalStock,
                'status' => $status,
                'stock_per_warehouse' => $stockPerWarehouse,
            ];
        })->values();

        // Calculate KPIs
        $totalSkus = $materials->count();
        $inStock = 0;
        $lowStock = 0;
        $outOfStock = 0;
        $totalValue = 0;

        foreach ($materialsWithStock as $mat) {
            $totalQty = $mat['total_stock'];
            $totalValue += $totalQty * $mat['unit_cost'];

            if ($totalQty <= 0) {
                $outOfStock++;
            } elseif ($totalQty <= $mat['reorder_point']) {
                $lowStock++;
            } else {
                $inStock++;
            }
        }

        // Warehouse summary
        $warehouseSummary = [];
        foreach ($warehouses as $wh) {
            $totalUnits = WarehouseStockItem::where('warehouse_id', $wh->id)
                ->where('status', 'in_stock')
                ->sum('quantity');
            $skusCount = WarehouseStockItem::where('warehouse_id', $wh->id)
                ->where('status', 'in_stock')
                ->distinct('material_id')
                ->count('material_id');

            $warehouseSummary[] = [
                'id' => $wh->id,
                'name' => $wh->name,
                'location' => $wh->location,
                'manager' => $wh->manager,
                'supervisor' => $wh->supervisor ? $wh->supervisor->name : null,
                'color' => $wh->color ?? 'blue',
                'total_units' => (float) $totalUnits,
                'skus' => $skusCount,
                'assigned_to_user' => true, // for supervisor filtering; adjust logic if needed
            ];
        }

        // Low stock / out of stock alerts
        $alertItems = [];
        foreach ($materials as $mat) {
            foreach ($warehouses as $wh) {
                $qty = WarehouseStockItem::where('warehouse_id', $wh->id)
                    ->where('material_id', $mat->id)
                    ->where('status', 'in_stock')
                    ->sum('quantity');
                if ($qty <= $mat->reorder_point) {
                    $alertItems[] = [
                        'sku' => $mat->mat_id,
                        'name' => $mat->name,
                        'warehouse' => $wh->name,
                        'qty' => (float) $qty,
                        'reorder' => (float) $mat->reorder_point,
                        'type' => $qty <= 0 ? 'out' : 'low',
                    ];
                }
            }
        }

        // Recent activity
        $recentActivity = WarehouseStockItem::with(['material', 'warehouse', 'receivedBy'])
            ->whereIn('warehouse_id', $warehouseIds)
            ->orderByDesc('received_at')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $qty = (float) $item->quantity;
                $reorder = (float) ($item->material->reorder_point ?? 0);

                if ($qty <= 0) {
                    $action = 'Out of stock flagged';
                    $color = 'red';
                } elseif ($qty <= $reorder) {
                    $action = 'Low stock alert';
                    $color = 'amber';
                } else {
                    $action = 'Stock received';
                    $color = 'emerald';
                }

                return [
                    'time' => $item->received_at ? $item->received_at->diffForHumans() : 'Unknown',
                    'action' => $action,
                    'item' => $item->material->name,
                    'qty' => number_format($qty, 0) . ' ' . $item->material->unit,
                    'color' => $color,
                    'warehouse' => $item->warehouse->name,
                ];
            })
            ->values()
            ->toArray();

        // Category breakdown
        $categoryBreakdown = $materials
            ->groupBy('category')
            ->map(function ($group, $cat) use ($totalSkus) {
                $safeTotal = $totalSkus ?: 1;
                return [
                    'name' => $cat,
                    'count' => $group->count(),
                    'pct' => (int) round(($group->count() / $safeTotal) * 100),
                    'color' => $this->getCategoryColor($cat),
                ];
            })
            ->values()
            ->toArray();

        // Inventory value trend (mock data)
        $valueTrend = [
            'months' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'values' => [2.1, 2.3, 2.5, 2.4, 2.6, 2.7],
        ];

        return Inertia::render('Dashboard/Inventory/Dashboard', [
            'warehouses' => $warehouseSummary,
            'materials' => $materialsWithStock,      // ← Added
            'alertItems' => $alertItems,
            'recentActivity' => $recentActivity,
            'categoryBreakdown' => $categoryBreakdown,
            'valueTrend' => $valueTrend,
            'kpis' => [
                'totalSkus' => $totalSkus,
                'inStock' => $inStock,
                'lowStock' => $lowStock,
                'outOfStock' => $outOfStock,
                'totalWarehouses' => count($warehouseSummary),
                'totalValue' => round($totalValue, 2),
            ],
        ]);
    }

    public function managerDashboard()
    {
        return $this->index();
    }

    private function getCategoryColor($category)
    {
        $palette = [
            'Yarn' => 'bg-blue-500',
            'Dye' => 'bg-violet-500',
            'Supplies' => 'bg-emerald-500',
            'Packaging' => 'bg-amber-500',
        ];
        return $palette[$category] ?? 'bg-slate-400';
    }
}