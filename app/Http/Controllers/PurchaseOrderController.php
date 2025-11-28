<?php

namespace App\Http\Controllers;

use App\Models\Product; 
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 5);

        $query = PurchaseOrder::with('product.category', 'supplier');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $query->orderBy('order_date', 'desc');

        return $query->paginate($perPage);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'quantity' => 'required|integer|min:1',
            'expected_date' => 'nullable|date',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // Vérifier cohérence fournisseur
        if ($product->supplier_id && $product->supplier_id != $validated['supplier_id']) {
            return response()->json(['error' => 'Supplier mismatch'], 422);
        }

        $orderValue = $validated['quantity'] * $product->buying_price;

        $order = PurchaseOrder::create([
            ...$validated,
            'order_value' => $orderValue,
            'status' => 'Confirmed',
            'order_date' => now(),
        ]);

        return response()->json($order->load('product','supplier'), 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $order = PurchaseOrder::findOrFail($id);

        // ✅ Validation complète des champs modifiables
        $validated = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'quantity' => 'sometimes|integer|min:1',
            'expected_date' => 'sometimes|date',
            'status' => 'required|string'
        ]);

        // ✅ Mise à jour des champs
        $order->update($validated);

        // ✅ Recharger les relations pour la réponse
        $order->load(['product', 'supplier']);

        return response()->json([
            'message' => 'Commande mise à jour avec succès',
            'data' => $order
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $order = PurchaseOrder::findOrFail($id);

        if ($order->received) {
            return response()->json(['error' => 'Impossible de supprimer une commande livrée'], 400);
        }

        $order->delete();

        return response()->json(['message' => 'Commande supprimée']);
    }

}
