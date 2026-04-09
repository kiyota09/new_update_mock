<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fabric extends Model
{
    protected $fillable = [
        'code',
        'manufacturing_order_id',
        'machine_id',
        'yarn_type',
        'roll_no',
        'weight',
        'remarks',
        'operator_id',
        'shift',
        'processed_at',
        'status',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'processed_at' => 'datetime',
        'status' => 'string',
    ];

    public function manufacturingOrder()
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function dyeJob()
    {
        return $this->hasOne(DyeJob::class);
    }

    public function softenerJob()
    {
        return $this->hasOne(SoftenerJob::class);
    }
}
