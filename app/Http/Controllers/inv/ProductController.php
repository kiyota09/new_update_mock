<?php

namespace App\Http\Controllers\inv; // Keep an eye on this if you renamed the folder to INV!

use App\Http\Controllers\Controller;
use App\Models\inv\Material;
use App\Models\inv\Product;
use App\Models\inv\ProductBom;
use App\Models\inv\ProductImage;
use App\Models\inv\ProductSize;
use App\Models\inv\ProductSpec;
use App\Models\inv\Warehouse;
use App\Models\inv\WarehouseMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function product()
    {
        $products = Product::with(['images'])
            ->orderBy('id', 'desc') // Changed to desc so your newly created products show up first!
            ->get()
            ->map(function (Product $p) {
                
                // 💥 BULLETPROOF COLOR PARSING 💥
                // This guarantees Vue receives a clean Array, no matter what happens in the DB.
                $colorsArray = [];
                if (!empty($p->colors)) {
                    // If it's a string, decode it. If it's already an array, just use it.
                    $colorsArray = is_string($p->colors) ? json_decode($p->colors, true) : $p->colors;
                }

                return [
                    'id' => $p->id,
                    'product_id' => $p->product_id,
                    'sku' => $p->sku,
                    'name' => $p->name,
                    'category' => $p->category,
                    'status' => $p->status,
                    
                    // The safely parsed array
                    'colors' => $colorsArray, 
                    
                    // Legacy support so your old products don't break Vue 
                    'colorName' => $p->color_name ?? null,
                    'colorHex' => $p->color_hex ?? null,
                    
                    'images' => $p->images->map(fn($img) => [
                        'id' => $img->id,
                        'url' => $img->url,
                    ])->toArray(),
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Dashboard/Inventory/Products', [
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        // dd($request->all()); // Debugging line to inspect incoming data structure, especially for colors and images.
        // 1. Validation: Changed to 'nullable' because FormData strips empty arrays!
        $request->validate([
            'name' => 'required|string|max:255',
            'colors' => 'nullable|array', 
            'category' => 'nullable|string',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // 2. Create Product
        $product = Product::create([
            'name' => $request->name,
            'colors' => $request->colors ?? [], // Default to empty array if null
            'category' => $request->category ?? 'Uncategorized',
            'status' => 'Active',
        ]);

        // 3. Handle images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $product->images()->create([
                    'url' => '/storage/' . $path  
                ]);
            }
        }

        return back()->with('success', 'Product created with color availability!');
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // 1. Update Validation: Changed to 'nullable' to prevent silent failures
        $request->validate([
            'name' => 'required|string|max:255',
            'colors' => 'nullable|array', 
            'new_images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        // 2. Update Database
        $product->update([
            'name' => $request->name,
            'colors' => $request->colors ?? [], 
        ]);

        // 3. Handle newly added images
        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $image) {
                $path = $image->store('products', 'public');
                $product->images()->create([
                    'url' => '/storage/' . $path 
                ]);
            }
        }

        return back()->with('success', 'Product updated successfully.');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        foreach ($product->images as $image) {
            $storagePath = str_replace('/storage/', '', $image->url);
            Storage::disk('public')->delete($storagePath);
        }

        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    public function destroyImage($imageId)
    {
        $image = ProductImage::findOrFail($imageId);

        $storagePath = str_replace('/storage/', '', $image->url);
        Storage::disk('public')->delete($storagePath);

        $image->delete();

        return back()->with('success', 'Image removed.');
    }
}