<?php

namespace App\Models\inv;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    protected $table = 'materials';

    protected $fillable = [
        'mat_id',
        'name',
        'category',
        'unit',
        'reorder_point',
        'unit_cost',
    ];

    protected $casts = [
        'unit_cost' => 'float',
        'reorder_point' => 'integer',
    ];

    /**
     * Get the stock items (actual warehouse inventory) for this material.
     */
    public function stockItems(): HasMany
    {
        return $this->hasMany(\App\Models\WarehouseStockItem::class, 'material_id');
    }

    /**
     * Get the BOM entries where this material is used as a component.
     */
    public function productBoms(): HasMany
    {
        return $this->hasMany(\App\Models\ProductBom::class, 'material_id');
    }

    /**
     * Legacy relationship – kept for backward compatibility if needed.
     * For the new system, use stockItems() instead.
     */
    public function warehouseMaterials(): HasMany
    {
        return $this->hasMany(\App\Models\inv\WarehouseMaterial::class);
    }

    /**
     * Legacy relationship – kept for backward compatibility.
     * For the new system, use stockItems() and warehouse access via WarehouseStockItem.
     */
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Warehouse::class, 'warehouse_materials')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Auto-generate the next material ID (e.g., MAT-001, MAT-002...).
     */
    public static function nextMatId(): string
    {
        $last = static::orderByDesc('id')->value('mat_id');
        if (! $last) {
            return 'MAT-001';
        }
        $num = (int) substr($last, 4) + 1;
        return 'MAT-'.str_pad($num, 3, '0', STR_PAD_LEFT);
    }
}