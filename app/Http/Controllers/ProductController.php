<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
     public function index(Request $request)
    {
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 5);
        $categoryId = $request->has('category_id')
            ? (int) $request->query('category_id')
            : null;
        $stockFilter = $request->query('stock'); // rupture | low | in

        $query = Product::with(['category', 'supplier']); 

        if (!empty($search)) {
            $query->where('name', 'like', '%' . $search . '%');
        }
        if (!empty($categoryId)) {
            $query->where('category_id', $categoryId);
        }

        if (!empty($stockFilter)) {
            if ($stockFilter === 'rupture') {
                $query->where('quantity', '=', 0);
            } elseif ($stockFilter === 'low') {
                $query->whereColumn('quantity', '<=', 'threshold')
                    ->where('quantity', '>', 0);
            } elseif ($stockFilter === 'in') {
                $query->whereColumn('quantity', '>', 'threshold');
            }
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }




    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|integer',
            'supplier_id' => 'nullable|exists:suppliers,id', 
            'buying_price' => 'required|numeric',
            'selling_price' => 'required|numeric',
            'quantity' => 'required|integer',
            'threshold' => 'required|integer',
            'expiry_date' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image'] = $path;
        }

        $product = Product::create($validated);

        return response()->json($product, 201);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::with('category', 'supplier')->find($id);

        if (! $product) {
            return response()->json(['message' => 'Produit introuvable'], 404);
        }

        return $product;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'category_id' => 'required|integer',
            'supplier_id' => 'nullable|exists:suppliers,id', 
            'buying_price' => 'required|numeric',
            'selling_price' => 'required|numeric',
            'quantity' => 'required|integer',
            'threshold' => 'required|integer',
            'expiry_date' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $product = Product::findOrFail($id);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image'] = $path;
        }

        $product->update($validated);
        \Log::info('Requête update', $request->all());

        return response()->json($product);
    }

    /**
     * Remove the specified resource from storage.
     */
     public function destroy($id)
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json(['message' => 'Produit introuvable'], 404);
        }

        $product->delete();

        return response()->json(null, 204);
    }
}
