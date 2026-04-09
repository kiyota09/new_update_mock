<?php

namespace App\Http\Controllers\inv;

use App\Http\Controllers\Controller;
use App\Models\BomRecord;
use App\Models\Client;
use App\Models\inv\Material;
use App\Models\inv\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BomController extends Controller
{
    /**
     * Display a listing of BOM records.
     */
    public function index()
    {
        $boms = BomRecord::with('client', 'product')->get();
        $clients = Client::all();
        $products = Product::all();
        $materials = Material::all();

        return Inertia::render('Dashboard/Inventory/Bom', [
            'boms' => $boms,
            'clients' => $clients,
            'products' => $products,
            'materials' => $materials,
        ]);
    }

    /**
     * Store a newly created BOM record.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'product_id' => 'required|exists:products,id',
            'yarn_type' => 'required|string',
            'dye_color' => 'required|string',
            'weave_design' => 'required|string',
            'materials' => 'required|array',
        ]);

        BomRecord::updateOrCreate(
            ['client_id' => $data['client_id'], 'product_id' => $data['product_id']],
            $data
        );

        return redirect()->back()->with('success', 'BOM saved successfully.');
    }

    /**
     * Update the specified BOM record.
     */
    public function update(Request $request, $id)
    {
        $bom = BomRecord::findOrFail($id);

        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'product_id' => 'required|exists:products,id',
            'yarn_type' => 'required|string',
            'dye_color' => 'required|string',
            'weave_design' => 'required|string',
            'materials' => 'required|array',
        ]);

        $bom->update($data);

        return redirect()->back()->with('success', 'BOM updated successfully.');
    }

    /**
     * Remove the specified BOM record.
     */
    public function destroy($id)
    {
        $bom = BomRecord::findOrFail($id);
        $bom->delete();

        return redirect()->back()->with('success', 'BOM deleted successfully.');
    }
}