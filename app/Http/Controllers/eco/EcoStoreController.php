<?php

namespace App\Http\Controllers\eco;

use App\Http\Controllers\Controller;
use App\Models\inv\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EcoStoreController extends Controller
{
    public function index()
    {
        // Load only active products with their images
        $products = Product::where('status', 'Active')
            ->with(['images' => function ($query) {
                // Order by ID since sort_order is gone
                $query->orderBy('id', 'asc');
            }])
            ->get()
            ->map(function ($product) {
                
                // FLATTEN IMAGES: Convert objects to a simple array of URL strings
                $images = $product->images->map(function ($image) {
                    // 1. Get the raw path from the DB (checking both 'url' and 'path' columns)
                    $rawPath = $image->url ?? $image->path;

                    // 2. Clean the path: remove '/storage/' or 'storage/' if it already exists 
                    // to prevent URLs like 'storage/storage/products/img.jpg'
                    $cleanPath = ltrim(str_replace('storage/', '', $rawPath), '/');

                    // 3. Return the full public URL string
                    return asset('storage/' . $cleanPath);
                })->values()->toArray();

                return [
                    'id' => $product->id,
                    'product_id' => $product->product_id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category' => $product->category,
                    'subcategory' => $product->subcategory,
                    'description' => $product->description,
                    'unit_cost' => $product->unit_cost, // Needed for Margin calculation in Vue
                    'selling_price' => $product->selling_price,
                    'stock_on_hand' => $product->stock_on_hand,
                    'moq' => $product->moq,
                    'weight' => $product->weight,      // Added for UI icons
                    'dimensions' => $product->dimensions, // Added for UI icons
                    'images' => $images, // Now this is ['url1', 'url2'] - Vue will love this
                ];
            });

        return Inertia::render('Dashboard/ECO/Store', [
            'products' => $products,
        ]);
    }
}