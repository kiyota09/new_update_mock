<?php

namespace App\Models\inv; // Updated to INV (Caps) for Hostinger compatibility

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category',
        'status',
        'colors', // 👈 Add this to store the array of colors
        'product_id',
        'sku',
    ];

    // ─── CRITICAL FOR JSON STORAGE ───
    protected $casts = [
        'colors' => 'array', // 👈 This automatically converts JSON to a PHP Array
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            // Generate Product ID if not set
            if (!$product->product_id) {
                $product->product_id = 'PRD-' . strtoupper(Str::random(6));
            }
            // Generate SKU based on name
            if (!$product->sku) {
                $product->sku = strtoupper(substr(Str::slug($product->name), 0, 3)) . '-' . strtoupper(Str::random(4));
            }
        });
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
}