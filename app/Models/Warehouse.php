<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'manager',
        'supervisor_id',
        'manager_id',
        'color',
    ];

    protected $casts = [
        'supervisor_id' => 'integer',
        'manager_id' => 'integer',
    ];

    /**
     * Get the supervisor user for this warehouse.
     */
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get the manager user for this warehouse.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the sections in this warehouse.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(WarehouseSection::class);
    }

    /**
     * Get all stock items in this warehouse.
     */
    public function stockItems(): HasMany
    {
        return $this->hasMany(WarehouseStockItem::class);
    }

    /**
     * Get all receiving records for this warehouse.
     */
    public function receivings(): HasMany
    {
        return $this->hasMany(WarehouseReceiving::class);
    }

    /**
     * Get all packages in this warehouse.
     */
    public function packages(): HasMany
    {
        return $this->hasMany(WarehousePackage::class);
    }

    /**
     * Get all rejects from this warehouse.
     */
    public function rejects(): HasMany
    {
        return $this->hasMany(WarehouseReject::class);
    }

    /**
     * Users who have access to this warehouse.
     */
    public function accessibleBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_warehouse_access');
    }
}