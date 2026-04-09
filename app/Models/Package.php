<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'code',
        'operator_id',
        'shift',
        'packaged_at',
        'status',
        'manufacturing_order_id',
    ];

    protected $casts = [
        'packaged_at' => 'datetime',
        'status' => 'string',
    ];

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function manufacturingOrder()
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    public function items()
    {
        return $this->hasMany(PackageItem::class);
    }
}
