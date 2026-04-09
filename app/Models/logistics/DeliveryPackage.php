<?php

namespace App\Models\logistics;

use Illuminate\Database\Eloquent\Relations\Pivot;

class DeliveryPackage extends Pivot
{
    protected $table = 'delivery_packages';
}