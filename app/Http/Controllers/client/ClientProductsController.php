<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use App\Models\inv\Product;
use App\Models\eco\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClientProductsController extends Controller
{
    public function index()
    {
        $products = Product::where('status', 'Active')
            ->with('images')
            ->get()
            ->map(function ($p) {
                // Safely parse the colors array just like we did in the Manager controller
                $colorsArray = [];
                if (!empty($p->colors)) {
                    $colorsArray = is_string($p->colors) ? json_decode($p->colors, true) : $p->colors;
                }

                return [
                    'id' => $p->id,
                    'product_id' => $p->product_id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'category' => $p->category ?? 'Uncategorized',
                    
                    'colors' => $colorsArray, // Safely parsed colors for the circles
                    'colorName' => $p->color_name ?? null, // Legacy fallback
                    'colorHex' => $p->color_hex ?? null, // Legacy fallback
                    
                    // FIXED: Returns an array of objects with 'url' so Vue can read product.images[0].url
                    'images' => $p->images->map(fn($img) => [
                        'id' => $img->id,
                        'url' => $img->url 
                    ])->toArray(),
                ];
            });

        return Inertia::render('Client/Products', ['products' => $products]);
    }

    public function inquire(Request $request, Product $product)
    {
        $request->validate(['message' => 'required|string']);
        
        $client = Auth::guard('client')->user();
        
        $inquiry = Inquiry::create([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'initial_message' => $request->message,
            'status' => 'open',
            'last_message_at' => now(),
        ]);
        
        // Also create the first message
        \App\Models\eco\ConversationMessage::create([
            'inquiry_id' => $inquiry->id,
            'sender_type' => 'client',
            'message' => $request->message,
        ]);
        
        return redirect()->route('client.conversation.show', $inquiry->id)
            ->with('success', 'Inquiry sent. You can now continue the conversation.');
    }
}