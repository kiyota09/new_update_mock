<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EcoQuotationItem extends Model
{
    use HasFactory;

    // Specifies exactly which table to use
    protected $table = 'eco_quotation_items';

    protected $fillable = [
        'eco_quotation_id',
        'product_id',
        'fabric',
        'design',
        'color',
        'kilos',
        'price'
    ];

    protected $casts = [
        'kilos' => 'float',
        'price' => 'float',
    ];

    /**
     * Relationship: This item belongs back to the main Quotation.
     */
    public function quotation()
    {
        return $this->belongsTo(EcoQuotation::class, 'eco_quotation_id');
    }

    /**
     * Relationship: Link to the master product in inventory.
     */
    public function product()
    {
        return $this->belongsTo(\App\Models\inv\Product::class, 'product_id');
    }
}