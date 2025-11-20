<?php

namespace App\Http\Controllers;

use App\Models\Product; 
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PurchaseOrder::with('product.category', 'supplier');

        // ✅ Filtrage par statut si fourni
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // ✅ Tri par date (optionnel)
        $query->orderBy('order_date', 'desc');

        // ✅ Pagination (10 par page par défaut)
        return $query->paginate(10);
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

        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        $order->status = $validated['status'];

        if ($validated['status'] === 'Delivered' && !$order->received) {
            $product = $order->product;
            $product->quantity += $order->quantity;
            $product->save();

            $order->received = true;
            $order->received_date = now();
        }

        $order->save();

        return response()->json($order->load('product','supplier'));
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $order = PurchaseOrder::findOrFail($id);

        if ($order->received) {
            return response()->json(['error' => 'Cannot delete a delivered order'], 400);
        }

        $order->delete();

        return response()->json(['message' => 'Order deleted']);
    }

}
