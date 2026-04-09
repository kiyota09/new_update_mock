<?php

namespace App\Models\logistics;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $fillable = ['name', 'origin', 'destination', 'distance_km', 'estimated_minutes', 'waypoints'];

    protected $casts = ['waypoints' => 'array'];

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }
}