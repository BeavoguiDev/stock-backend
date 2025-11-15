<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return Supplier::with('products')->get();
    }

    public function show($id)
    {
        $supplier = Supplier::with('products')->find($id);

        if (! $supplier) {
            return response()->json(['message' => 'Fournisseur introuvable'], 404);
        }

        return $supplier;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:suppliers,email',
            'phone' => 'required|string',
            'takes_back_returns' => 'boolean',
        ]);

        $supplier = Supplier::create($validated);

        return response()->json($supplier, 201);
    }

    public function update(Request $request, $id)
    {
        $supplier = Supplier::find($id);

        if (! $supplier) {
            return response()->json(['message' => 'Fournisseur introuvable'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:suppliers,email,' . $id,
            'phone' => 'required|string',
            'takes_back_returns' => 'boolean',
        ]);

        $supplier->update($validated);

        return response()->json($supplier);
    }

    public function destroy($id)
    {
        $supplier = Supplier::find($id);

        if (! $supplier) {
            return response()->json(['message' => 'Fournisseur introuvable'], 404);
        }

        $supplier->delete();

        return response()->json(null, 204);
    }
}
