<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReturnTransaction;
use App\Models\ReturnItem;
use App\Models\ProductBranch;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = ReturnTransaction::with(['branch', 'user', 'approver', 'items.productBranch.product', 'purchaseOrder', 'sale']);
        
        $search = $request->query('search');
        $itemsPerPage = $request->query('itemsPerPage', 15);
        $page = $request->query('page', 1);

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('reference_type_filter') && $request->reference_type_filter != 'all') {
            $query->where('reference_type', $request->reference_type_filter);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        $query->orderBy('created_at', 'desc');

        if ($itemsPerPage == -1) {
            $returns = $query->get();
            $paginated = null;
        } else {
            $paginated = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $returns = $paginated->items();
        }

        $response = [
            'data' => $returns,
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
        if (!request()->user()->can('Retur Barang Create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'reference_type' => 'required|in:purchase,sale',
            'reference_id' => 'required|integer',
            'return_type' => 'required|in:tukar_barang,pengembalian_uang,pengembalian_dana,potong_hutang',
            'items' => 'required|array|min:1',
            'items.*.product_branch_id' => 'required|exists:product_branches,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            // Generate Return Number
            $datePrefix = date('Ymd');
            $lastReturn = ReturnTransaction::where('return_number', 'like', "RET-{$datePrefix}-%")->orderBy('id', 'desc')->first();
            $nextSeq = 1;
            if ($lastReturn) {
                $lastSeq = (int) substr($lastReturn->return_number, -4);
                $nextSeq = $lastSeq + 1;
            }
            $returnNumber = "RET-{$datePrefix}-" . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

            $totalAmount = 0;
            foreach ($request->items as $item) {
                // Validate if item is returnable
                $productBranch = ProductBranch::with('product')->find($item['product_branch_id']);
                if ($productBranch && $productBranch->product && !$productBranch->product->is_returnable) {
                    throw new \Exception("Produk '{$productBranch->product->name}' tidak termasuk dalam kebijakan pengembalian.");
                }
                
                $totalAmount += ($item['qty'] * $item['unit_price']);
            }

            $returnTx = ReturnTransaction::create([
                'return_number' => $returnNumber,
                'branch_id' => $request->branch_id,
                'user_id' => $request->user()->id,
                'reference_type' => $request->reference_type,
                'reference_id' => $request->reference_id,
                'return_type' => $request->return_type,
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                ReturnItem::create([
                    'return_transaction_id' => $returnTx->id,
                    'product_branch_id' => $item['product_branch_id'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['qty'] * $item['unit_price'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Draft Retur berhasil dibuat dan menunggu persetujuan',
                'return_transaction' => $returnTx->load('items')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal membuat retur', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $returnTransaction = ReturnTransaction::with([
            'branch',
            'user',
            'approver',
            'items.productBranch.product',
            'purchaseOrder.supplier',
            'sale'
        ])->findOrFail($id);

        return response()->json($returnTransaction);
    }

    public function approve(Request $request, $id)
    {
        $returnTransaction = ReturnTransaction::with([
            'items.productBranch.product',
            'purchaseOrder.supplier',
            'sale',
            'branch'
        ])->findOrFail($id);

        if ($returnTransaction->status !== 'pending') {
            return response()->json(['message' => 'Retur sudah diproses sebelumnya'], 400);
        }

        DB::beginTransaction();
        try {
            $approverId = $request->input('approver_id') ?: ($request->user() ? $request->user()->id : null);
            $returnTransaction->update([
                'status' => 'completed',
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            // Process Stock based on reference_type
            foreach ($returnTransaction->items as $item) {
                $pb = ProductBranch::find($item->product_branch_id);
                if (!$pb) continue;

                if ($returnTransaction->reference_type === 'sale') {
                    // Retur Penjualan: Pelanggan mengembalikan barang ke toko -> Stok toko BERTAMBAH (IN)
                    $stockMethod = $pb->product->stock_method ?? 'fifo';
                    $activeBatchQuery = \App\Models\ProductBatch::where('product_branch_id', $pb->id)->where('qty', '>', 0);
                    
                    if ($stockMethod === 'fefo') {
                        $activeBatchQuery->orderByRaw('expiration_date IS NULL, expiration_date ASC, entry_date ASC');
                    } elseif ($stockMethod === 'lifo') {
                        $activeBatchQuery->orderBy('entry_date', 'desc')->orderBy('id', 'desc');
                    } else {
                        $activeBatchQuery->orderBy('entry_date', 'asc')->orderBy('id', 'asc');
                    }
                    
                    $activeBatch = $activeBatchQuery->first();
                    
                    if (!$activeBatch) {
                        $activeBatch = \App\Models\ProductBatch::where('product_branch_id', $pb->id)->orderBy('id', 'desc')->first();
                    }
                    
                    if ($activeBatch) {
                        $activeBatch->increment('qty', $item->qty);
                    } else {
                        \App\Models\ProductBatch::create([
                            'product_branch_id' => $pb->id,
                            'qty' => $item->qty,
                            'cost_price' => $pb->cost_price ?? 0,
                            'price' => $pb->price ?? 0,
                            'min_nego_price' => $pb->min_nego_price ?? 0,
                            'entry_date' => now(),
                        ]);
                    }
                    
                    $pb->increment('stock', $item->qty);
                    
                    $saleInvoice = $returnTransaction->sale ? $returnTransaction->sale->invoice_number : '';
                    $refLabel = $saleInvoice ? " (Faktur: {$saleInvoice})" : '';

                    StockMovement::create([
                        'product_branch_id' => $pb->id,
                        'type' => 'in',
                        'quantity' => $item->qty,
                        'unit_cost' => $pb->cost_price ?? 0,
                        'notes' => "Retur Penjualan: {$returnTransaction->return_number}{$refLabel}",
                        'user_id' => $request->user()->id,
                        'reference_type' => ReturnTransaction::class,
                        'reference_id' => $returnTransaction->id
                    ]);

                } else if ($returnTransaction->reference_type === 'purchase') {
                    // Retur Pembelian: Toko kembalikan barang cacat ke supplier -> Stok toko BERKURANG (OUT)
                    if ($pb->stock < $item->qty) {
                        $prodName = $pb->product ? $pb->product->name : "ID {$pb->product_id}";
                        throw new \Exception("Stok tidak mencukupi untuk diretur ke supplier untuk '{$prodName}' (Sisa stok: {$pb->stock}, Diminta retur: {$item->qty})");
                    }
                    
                    // Deduct from batches using FIFO/FEFO/LIFO
                    $qtyToDeduct = $item->qty;
                    $stockMethod = $pb->product->stock_method ?? 'fifo';
                    
                    $batchQuery = \App\Models\ProductBatch::where('product_branch_id', $pb->id)->where('qty', '>', 0);
                        
                    if ($stockMethod === 'fefo') {
                        $batchQuery->orderByRaw('expiration_date IS NULL, expiration_date ASC, entry_date ASC');
                    } elseif ($stockMethod === 'lifo') {
                        $batchQuery->orderBy('entry_date', 'desc')->orderBy('id', 'desc');
                    } else { // fifo
                        $batchQuery->orderBy('entry_date', 'asc')->orderBy('id', 'asc');
                    }
                    
                    $batches = $batchQuery->lockForUpdate()->get();
                    
                    foreach ($batches as $batch) {
                        if ($qtyToDeduct <= 0) break;
                        
                        if ($batch->qty >= $qtyToDeduct) {
                            $batch->decrement('qty', $qtyToDeduct);
                            $qtyToDeduct = 0;
                        } else {
                            $qtyToDeduct -= $batch->qty;
                            $batch->update(['qty' => 0]);
                        }
                    }

                    if ($qtyToDeduct > 0) {
                        $prodName = $pb->product ? $pb->product->name : "ID {$pb->product_id}";
                        throw new \Exception("Stok Batch tidak mencukupi untuk diretur ke supplier untuk '{$prodName}' (Sisa kurang: {$qtyToDeduct})");
                    }
                    
                    $pb->decrement('stock', $item->qty);
                    
                    $poNumber = $returnTransaction->purchaseOrder ? $returnTransaction->purchaseOrder->po_number : '';
                    $refLabel = $poNumber ? " (PO: {$poNumber})" : '';

                    StockMovement::create([
                        'product_branch_id' => $pb->id,
                        'type' => 'out',
                        'quantity' => $item->qty,
                        'unit_cost' => $pb->cost_price ?? 0,
                        'notes' => "Retur Pembelian ke Supplier: {$returnTransaction->return_number}{$refLabel}",
                        'user_id' => $request->user()->id,
                        'reference_type' => ReturnTransaction::class,
                        'reference_id' => $returnTransaction->id
                    ]);
                }
            }

            // Jika Retur Pembelian bertipe Pengembalian Dana / Potong Hutang -> Terbitkan Saldo Kredit Supplier (Supplier Credit Note)
            if ($returnTransaction->reference_type === 'purchase' && in_array($returnTransaction->return_type, ['pengembalian_dana', 'pengembalian_uang', 'potong_hutang'])) {
                $po = $returnTransaction->purchaseOrder;
                $supplierId = $po ? $po->supplier_id : null;

                if ($supplierId) {
                    $datePrefix = date('Ymd');
                    $crdCount = \App\Models\SupplierCredit::where('credit_number', 'like', "CRD-{$datePrefix}-%")->count() + 1;
                    $creditNumber = "CRD-{$datePrefix}-" . str_pad($crdCount, 4, '0', STR_PAD_LEFT);

                    \App\Models\SupplierCredit::create([
                        'credit_number' => $creditNumber,
                        'supplier_id' => $supplierId,
                        'branch_id' => $returnTransaction->branch_id,
                        'return_transaction_id' => $returnTransaction->id,
                        'purchase_order_id' => $po ? $po->id : null,
                        'amount' => $returnTransaction->total_amount,
                        'used_amount' => 0,
                        'remaining_amount' => $returnTransaction->total_amount,
                        'status' => 'available',
                        'notes' => "Saldo Kredit Retur / Potong Hutang Pembelian Selanjutnya dari Retur #{$returnTransaction->return_number}",
                        'created_by' => $request->user() ? $request->user()->id : 1,
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Retur berhasil disetujui dan stok telah disesuaikan' . ($returnTransaction->reference_type === 'purchase' && in_array($returnTransaction->return_type, ['pengembalian_dana', 'pengembalian_uang', 'potong_hutang']) ? '. Saldo kredit potong hutang supplier telah diterbitkan!' : ''),
                'return_transaction' => $returnTransaction->load(['items.productBranch.product', 'branch', 'user', 'approver'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyetujui retur', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Penerimaan Barang Pengganti dari Supplier untuk Retur bertipe Tukar Barang
     */
    public function receiveReplacement(Request $request, $id)
    {
        $returnTransaction = ReturnTransaction::with([
            'items.productBranch.product',
            'purchaseOrder.supplier',
            'branch'
        ])->findOrFail($id);

        if ($returnTransaction->reference_type !== 'purchase' || $returnTransaction->return_type !== 'tukar_barang') {
            return response()->json(['message' => 'Hanya retur pembelian dengan tipe tukar barang yang dapat menerima barang pengganti.'], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($returnTransaction->items as $item) {
                $pb = ProductBranch::find($item->product_branch_id);
                if (!$pb) continue;

                $pb->increment('stock', $item->qty);

                \App\Models\ProductBatch::create([
                    'product_branch_id' => $pb->id,
                    'qty' => $item->qty,
                    'cost_price' => $pb->cost_price ?? 0,
                    'price' => $pb->price ?? 0,
                    'min_nego_price' => $pb->min_nego_price ?? 0,
                    'entry_date' => now(),
                ]);

                StockMovement::create([
                    'product_branch_id' => $pb->id,
                    'type' => 'in',
                    'quantity' => $item->qty,
                    'unit_cost' => $pb->cost_price ?? 0,
                    'notes' => "Penerimaan Barang Pengganti Supplier (Retur #{$returnTransaction->return_number})",
                    'user_id' => $request->user()->id,
                    'reference_type' => ReturnTransaction::class,
                    'reference_id' => $returnTransaction->id
                ]);
            }

            $returnTransaction->update([
                'notes' => ($returnTransaction->notes ? $returnTransaction->notes . ' | ' : '') . '[Barang pengganti telah diterima fisik pada ' . now()->format('d/m/Y H:i') . ']',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Barang pengganti berhasil diterima! Stok cabang dan nomor batch fisik telah bertambah.',
                'return_transaction' => $returnTransaction->load(['items.productBranch.product', 'branch', 'user', 'approver'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memproses penerimaan barang pengganti: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        if (!request()->user()->can('Retur Barang Delete')) {
            abort(403, 'Unauthorized action.');
        }

        $returnTransaction = ReturnTransaction::findOrFail($id);

        if ($returnTransaction->status === 'completed') {
            return response()->json(['message' => 'Tidak dapat menghapus retur yang sudah selesai'], 400);
        }
        
        $returnTransaction->delete();
        return response()->json(['message' => 'Draft Retur dihapus']);
    }
}
