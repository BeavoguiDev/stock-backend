<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 5); 
        $search = $request->query('search');

        $query = Supplier::with('products');

        if (!empty($search)) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        // 👉 C’est ici qu’on ajoute ton filtre
        if ($request->has('takes_back_returns')) {
            $query->where(
                'takes_back_returns',
                filter_var($request->query('takes_back_returns'), FILTER_VALIDATE_BOOLEAN)
            );
        }

        // ✅ Paginer les fournisseurs
        $suppliers = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // ✅ Ajouter le champ calculé "on_the_way" à chaque fournisseur
        $suppliers->getCollection()->transform(function ($supplier) {
            $onTheWay = $supplier->purchaseOrders()
                ->whereNotIn('status', ['Delivered', 'Returned'])
                ->sum('quantity');

            $supplier->on_the_way = $onTheWay;
            return $supplier;
        });

        return response()->json([
            'data' => $suppliers->items(),
            'meta' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'total' => $suppliers->total()
            ]
        ]);
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
            'phone' => 'required|string|min:9|max:13',
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

        // ✅ Supprimer toutes les commandes non livrées liées à ce fournisseur
        $supplier->purchaseOrders()
            ->whereNotIn('status', ['Delivered', 'Returned'])
            ->delete();

        $supplier->delete();

        return response()->json(null, 204);
    }

}
