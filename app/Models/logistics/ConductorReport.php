<?php

namespace App\Models\logistics;

use Illuminate\Database\Eloquent\Model;

class ConductorReport extends Model
{
    protected $fillable = ['delivery_id', 'conductor_id', 'report_text'];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function conductor()
    {
        return $this->belongsTo(Conductor::class);
    }
}