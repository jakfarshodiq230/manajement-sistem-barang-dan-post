<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = \App\Models\PurchaseOrder::with(['supplier', 'branch', 'user', 'items.product']);
        
        $search = $request->query('search');
        $itemsPerPage = $request->query('itemsPerPage', 15);
        $page = $request->query('page', 1);

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('unreceived') && $request->unreceived == 'true') {
            $query->where('status', 'pending')
                  ->where('approval_status', 'approved')
                  ->whereDoesntHave('goodsReceipt', function($q) {
                      $q->whereNotIn('approval_status', ['rejected', 'cancelled']);
                  });
        }

        if ($request->has('approval_status_filter')) {
            $status = $request->approval_status_filter;
            if ($status == 'need_validation') {
                $query->where(function($q) {
                    $q->whereNull('approval_status')
                      ->orWhereIn('approval_status', ['draft', 'pending']);
                });
            } elseif ($status == 'need_approval') {
                $query->where('approval_status', 'validated');
            } elseif ($status == 'approved') {
                $query->where('approval_status', 'approved');
            }
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereDate('date', '>=', $request->start_date)
                  ->whereDate('date', '<=', $request->end_date);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function($s) use ($search) {
                      $s->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        $query->orderBy('created_at', 'desc');

        if ($itemsPerPage == -1) {
            $pos = $query->get();
            $paginated = null;
        } else {
            $paginated = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $pos = $paginated->items();
        }

        $response = [
            'data' => $pos,
        ];

        if ($paginated) {
            $response['current_page'] = $paginated->currentPage();
            $response['last_page'] = $paginated->lastPage();
            $response['per_page'] = $paginated->perPage();
            $response['total'] = $paginated->total();
        }

        return response()->json($response);
    }

    public function store(Request $request)
    {
        if (!request()->user()->can('Purchase Order Create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            $po_number = 'PO-' . date('YmdHis') . '-' . rand(1000, 9999);
            
            $po = \App\Models\PurchaseOrder::create([
                'po_number' => $po_number,
                'branch_id' => $request->branch_id,
                'supplier_id' => $request->supplier_id,
                'user_id' => $request->user()->id,
                'date' => $request->date,
                'status' => 'pending',
                'approval_status' => 'draft',
                'notes' => $request->notes,
                'total_amount' => 0,
            ]);

            $total_amount = 0;

            foreach ($request->items as $item) {
                $total_price = $item['qty'] * $item['unit_cost'];
                $total_amount += $total_price;

                \App\Models\PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit_cost' => $item['unit_cost'],
                    'total_price' => $total_price,
                ]);
            }

            $po->update(['total_amount' => $total_amount]);

            \Illuminate\Support\Facades\DB::commit();

            return response()->json(['message' => 'Purchase Order berhasil dibuat', 'po' => $po], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Gagal membuat Purchase Order', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $po = \App\Models\PurchaseOrder::with(['supplier', 'branch', 'user', 'items.product'])->findOrFail($id);
        return response()->json($po);
    }

    public function update(Request $request, $id)
    {
        if (!request()->user()->can('Purchase Order Write')) {
            abort(403, 'Unauthorized action.');
        }

        // Typically POs are not fully editable after creation unless they are draft,
        // but we'll provide status update for now (e.g. cancelling).
        $po = \App\Models\PurchaseOrder::findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
        ]);
        
        $po->update(['status' => $request->status]);
        return response()->json(['message' => 'Status PO berhasil diperbarui', 'po' => $po]);
    }

    public function destroy($id)
    {
        if (!request()->user()->can('Purchase Order Delete')) {
            abort(403, 'Unauthorized action.');
        }

        $po = \App\Models\PurchaseOrder::findOrFail($id);
        if ($po->status === 'completed') {
            return response()->json(['message' => 'PO yang sudah selesai tidak dapat dihapus'], 400);
        }
        $po->delete();
        return response()->json(['message' => 'PO berhasil dihapus']);
    }
}
