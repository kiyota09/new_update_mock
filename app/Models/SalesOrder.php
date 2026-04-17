<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $fillable = [
    'purchase_order_id', 'client_id', 'jo_number', 'control_number', 
    'color', 'quantity', 'yarn_type', 'design', 'status'
]; 

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
