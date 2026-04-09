<?php

namespace App\Models\Scm;

use App\Models\inv\Material;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScmPurchaseOrderItem extends Model
{
    use HasFactory;

    protected $table = 'scm_purchase_order_items';

    protected $fillable = [
        'scm_purchase_order_id',
        'material_id',
        'material_name',
        'qty',
        'received_qty',
        'unit',
        'unit_price',
        'total',
    ];

    /**
     * Relationship to the purchase order.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(ScmPurchaseOrder::class, 'scm_purchase_order_id');
    }

    /**
     * Relationship to the material (inventory).
     */
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}