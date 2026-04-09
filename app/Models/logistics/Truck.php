<?php

namespace App\Models\logistics;

use Illuminate\Database\Eloquent\Model;

class Truck extends Model
{
    protected $fillable = ['truck_number', 'plate_number', 'model', 'year', 'mileage', 'status', 'remarks'];

    protected $casts = [
        'mileage' => 'decimal:2',
    ];

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }
}