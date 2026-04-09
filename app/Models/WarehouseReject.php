<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseReject extends Model
{
    protected $fillable = [
        'rejectable_type', 'rejectable_id', 'source', 'warehouse_id',
        'quantity', 'unit', 'reason', 'status'
    ];

    public function rejectable() { return $this->morphTo(); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
}