<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\ProductBranch;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $query = StockTransfer::with(['sourceBranch', 'destinationBranch', 'createdBy', 'approvedBy', 'items.product']);

        $search = $request->query('search');
        $itemsPerPage = $request->query('itemsPerPage', 15);
        $page = $request->query('page', 1);

        if ($request->has('source_branch_id')) {
            $query->where('source_branch_id', $request->source_branch_id);
        }

        if ($request->has('destination_branch_id')) {
            $query->where('destination_branch_id', $request->destination_branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($search) {
            $query->where('reference_number', 'like', "%{$search}%");
        }

        $query->orderBy('created_at', 'desc');

        if ($itemsPerPage == -1) {
            $transfers = $query->get();
            $paginated = null;
        } else {
            $paginated = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $transfers = $paginated->items();
        }

        $response = [
            'data' => $transfers,
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
        $request->validate([
            'source_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'required|exists:branches,id|different:source_branch_id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Generate reference number
            $count = StockTransfer::whereDate('created_at', date('Y-m-d'))->count() + 1;
            $referenceNo = 'TRF-' . date('Ymd') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            $transfer = StockTransfer::create([
                'reference_no' => $referenceNo,
                'source_branch_id' => $request->source_branch_id,
                'destination_branch_id' => $request->destination_branch_id,
                'status' => 'pending',
                'notes' => $request->notes,
                'created_by' => $request->user()->id ?? null,
            ]);

            foreach ($request->items as $item) {
                // Verify source stock (must bypass global scopes so cross-branch transfers work)
                $sourceProductBranch = ProductBranch::withoutGlobalScopes()
                    ->where('branch_id', $request->source_branch_id)
                    ->where('product_id', $item['product_id'])
                    ->first();

                if (!$sourceProductBranch) {
                    throw new \Exception("Produk ID " . $item['product_id'] . " belum terdaftar di cabang asal.");
                }
                
                if ($sourceProductBranch->stock < $item['qty']) {
                    throw new \Exception("Stok tidak mencukupi untuk produk ID " . $item['product_id'] . " di cabang asal (Sisa stok: " . $sourceProductBranch->stock . ").");
                }

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Mutasi stok berhasil dibuat dan menunggu persetujuan.',
                'data' => $transfer->load('items')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Mutasi Stok Error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Gagal membuat mutasi stok', 'error' => $e->getMessage()], 400);
        }
    }

    public function show($id)
    {
        $transfer = StockTransfer::with(['sourceBranch', 'destinationBranch', 'createdBy', 'approvedBy', 'items.product'])->findOrFail($id);
        return response()->json($transfer);
    }

    public function approve(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $transfer = StockTransfer::with(['items', 'sourceBranch', 'destinationBranch'])->findOrFail($id);

            if ($transfer->status !== 'pending') {
                return response()->json(['message' => 'Mutasi stok ini tidak dalam status pending.'], 400);
            }

            foreach ($transfer->items as $item) {
                // 1. Deduct from source branch
                $sourceProductBranch = ProductBranch::withoutGlobalScopes()
                    ->where('branch_id', $transfer->source_branch_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                if (!$sourceProductBranch || $sourceProductBranch->stock < $item->qty) {
                    throw new \Exception("Stok tidak mencukupi untuk dipindahkan pada produk ID " . $item->product_id);
                }

                $sourceProductBranch->stock -= $item->qty;
                $sourceProductBranch->save();

                $sourceName = $transfer->sourceBranch ? $transfer->sourceBranch->name : 'Cabang Asal';
                $destName = $transfer->destinationBranch ? $transfer->destinationBranch->name : 'Cabang Tujuan';

                StockMovement::create([
                    'product_branch_id' => $sourceProductBranch->id,
                    'type' => 'out',
                    'quantity' => $item->qty,
                    'unit_cost' => $sourceProductBranch->cost_price ?? 0,
                    'notes' => "Mutasi keluar ke {$destName} (Ref: {$transfer->reference_no})",
                ]);

                // 2. Add to destination branch
                $destinationProductBranch = ProductBranch::withoutGlobalScopes()->firstOrCreate(
                    [
                        'branch_id' => $transfer->destination_branch_id,
                        'product_id' => $item->product_id,
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'stock' => 0,
                        'price' => $sourceProductBranch->price, // copy price from source
                        'cost_price' => $sourceProductBranch->cost_price,
                        'min_stock' => 0,
                        'is_active' => true
                    ]
                );

                $destinationProductBranch->stock += $item->qty;
                // Update cost price if it was 0
                if ($destinationProductBranch->cost_price == 0 && $sourceProductBranch->cost_price > 0) {
                    $destinationProductBranch->cost_price = $sourceProductBranch->cost_price;
                }
                $destinationProductBranch->save();

                // 3. Handle Batches (FIFO/LIFO/FEFO Transfer)
                $remainingQty = $item->qty;
                $transferredBatches = [];
                
                // Get stock method from product
                $stockMethod = $sourceProductBranch->product->stock_method ?? 'fifo';
                
                $batchQuery = \App\Models\ProductBatch::where('product_branch_id', $sourceProductBranch->id)
                    ->where('qty', '>', 0);
                    
                if ($stockMethod === 'fefo') {
                    $batchQuery->orderByRaw('expiration_date IS NULL, expiration_date ASC, entry_date ASC');
                } elseif ($stockMethod === 'lifo') {
                    $batchQuery->orderBy('entry_date', 'desc')->orderBy('id', 'desc');
                } else { // fifo
                    $batchQuery->orderBy('entry_date', 'asc')->orderBy('id', 'asc');
                }
                
                $sourceBatches = $batchQuery->get();
                    
                if ($sourceBatches->count() > 0) {
                    foreach ($sourceBatches as $batch) {
                        if ($remainingQty <= 0) break;
                        
                        $deduct = min($batch->qty, $remainingQty);
                        $batch->qty -= $deduct;
                        $batch->save();
                        
                        $remainingQty -= $deduct;
                        
                        $transferredBatches[] = [
                            'entry_date' => $batch->entry_date,
                            'expiration_date' => $batch->expiration_date,
                            'cost_price' => $batch->cost_price,
                            'qty' => $deduct,
                        ];
                    }
                } else {
                    $transferredBatches[] = [
                        'entry_date' => date('Y-m-d'),
                        'expiration_date' => null,
                        'cost_price' => $sourceProductBranch->cost_price,
                        'qty' => $item->qty,
                    ];
                }

                foreach ($transferredBatches as $tBatch) {
                    // Find or create a matching batch at destination
                    $destBatchQuery = \App\Models\ProductBatch::where('product_branch_id', $destinationProductBranch->id)
                        ->where('entry_date', $tBatch['entry_date'])
                        ->where('cost_price', $tBatch['cost_price']);
                        
                    if ($tBatch['expiration_date']) {
                        $destBatchQuery->where('expiration_date', $tBatch['expiration_date']);
                    } else {
                        $destBatchQuery->whereNull('expiration_date');
                    }
                    
                    $destBatch = $destBatchQuery->first();
                    
                    if (!$destBatch) {
                        $destBatch = \App\Models\ProductBatch::create([
                            'product_branch_id' => $destinationProductBranch->id,
                            'qty' => 0,
                            'entry_date' => $tBatch['entry_date'],
                            'expiration_date' => $tBatch['expiration_date'],
                            'cost_price' => $tBatch['cost_price'] ?? 0,
                        ]);
                    }
                    
                    $destBatch->qty += $tBatch['qty'];
                    $destBatch->save();
                }

                StockMovement::create([
                    'product_branch_id' => $destinationProductBranch->id,
                    'type' => 'in',
                    'quantity' => $item->qty,
                    'unit_cost' => $sourceProductBranch->cost_price ?? 0,
                    'notes' => "Mutasi masuk dari {$sourceName} (Ref: {$transfer->reference_no})",
                ]);
            }

            $transfer->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id ?? null
            ]);

            DB::commit();

            return response()->json(['message' => 'Mutasi stok berhasil disetujui dan stok telah dipindahkan.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyetujui mutasi stok', 'error' => $e->getMessage()], 400);
        }
    }

    public function reject(Request $request, $id)
    {
        $transfer = StockTransfer::findOrFail($id);

        if ($transfer->status !== 'pending') {
            return response()->json(['message' => 'Hanya mutasi pending yang bisa ditolak.'], 400);
        }

        $transfer->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id ?? null
        ]);

        return response()->json(['message' => 'Mutasi stok berhasil ditolak.']);
    }
}
