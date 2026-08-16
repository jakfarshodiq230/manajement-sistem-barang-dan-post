<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductBranch;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = StockMovement::with(['productBranch.product', 'productBranch.branch']);
        
        if ($request->has('product_branch_id')) {
            $query->where('product_branch_id', $request->product_branch_id);
        }
        
        $query->orderBy('created_at', 'desc');

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_branch_id' => 'required|exists:product_branches,id',
            'type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $productBranch = ProductBranch::findOrFail($request->product_branch_id);
            
            // Adjust stock
            if ($request->type === 'in') {
                $productBranch->stock += $request->quantity;
                // Update cost price average if needed, here we just update if provided
                if ($request->has('unit_cost') && $request->unit_cost > 0) {
                    $productBranch->cost_price = $request->unit_cost;
                }
            } elseif ($request->type === 'out') {
                if ($productBranch->stock < $request->quantity) {
                    return response()->json(['message' => 'Stok tidak mencukupi'], 400);
                }
                $productBranch->stock -= $request->quantity;
            } elseif ($request->type === 'adjustment') {
                // Assuming adjustment means replacing the stock or adjusting by negative/positive,
                // But since quantity is validated as min:1, let's treat adjustment as addition for now,
                // Or you can implement specific adjustment logic.
                $productBranch->stock = $request->quantity; // direct set for adjustment
            }
            
            $productBranch->save();

            $movement = StockMovement::create([
                'product_branch_id' => $request->product_branch_id,
                'type' => $request->type,
                'quantity' => $request->quantity,
                'unit_cost' => $request->unit_cost ?? 0,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Mutasi stok berhasil dicatat',
                'movement' => $movement,
                'current_stock' => $productBranch->stock
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan saat memproses stok', 'error' => $e->getMessage()], 500);
        }
    }
}
