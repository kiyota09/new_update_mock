<?php

namespace App\Models;

use App\Models\inv\Material;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseReceivingItem extends Model
{
    use HasFactory;

    protected $table = 'warehouse_receiving_items';

    protected $fillable = [
        'receiving_id',
        'material_id',
        'expected_qty',
        'received_qty',
        'rejected_qty',
        'status',
        'reject_reason',
    ];

    public function receiving()
    {
        return $this->belongsTo(WarehouseReceiving::class, 'receiving_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}