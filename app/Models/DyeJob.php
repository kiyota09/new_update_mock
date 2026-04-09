<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DyeJob extends Model
{
    protected $fillable = [
        'fabric_id',
        'machine_id',
        'dye_type',
        'chemical_no',
        'remarks',
        'operator_id',
        'shift',
        'code',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function fabric()
    {
        return $this->belongsTo(Fabric::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
