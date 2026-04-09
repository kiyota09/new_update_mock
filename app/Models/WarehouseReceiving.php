<?php

namespace App\Models;

use App\Models\inv\Material;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseReceiving extends Model
{
    use HasFactory;

    protected $table = 'warehouse_receivings';

    protected $fillable = [
        'receiving_number',
        'warehouse_id',
        'scm_purchase_order_id',
        'received_at',
        'received_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(ScmPurchaseOrder::class, 'scm_purchase_order_id');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Items associated with this receiving.
     * The foreign key is 'receiving_id' on the warehouse_receiving_items table.
     */
    public function items()
    {
        return $this->hasMany(WarehouseReceivingItem::class, 'receiving_id');
    }
}