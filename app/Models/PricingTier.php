<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingTier extends Model
{
    protected $fillable = [
        'name',
        'discount_percentage',
        'min_quantity',
        'status'];
}
