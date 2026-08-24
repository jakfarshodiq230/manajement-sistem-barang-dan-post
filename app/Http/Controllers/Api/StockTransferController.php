<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\ProductBranch;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StockTransferController extends Controller
{
    public function statusCounts(Request $request)
    {
        $query = StockTransfer::query();

        if ($request->has('source_branch_id') && $request->source_branch_id) {
            $query->where('source_branch_id', $request->source_branch_id);
        }

        if ($request->has('destination_branch_id') && $request->destination_branch_id) {
            $query->where('destination_branch_id', $request->destination_branch_id);
        }

        $counts = $query->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status IN ('ready_for_pickup', 'approved') THEN 1 ELSE 0 END) as ready_for_pickup,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status IN ('rejected', 'cancelled') THEN 1 ELSE 0 END) as rejected_cancelled
        ")->first();

        return response()->json([
            'total' => (int) ($counts->total ?? 0),
            'pending' => (int) ($counts->pending ?? 0),
            'ready_for_pickup' => (int) ($counts->ready_for_pickup ?? 0),
            'completed' => (int) ($counts->completed ?? 0),
            'rejected_cancelled' => (int) ($counts->rejected_cancelled ?? 0),
        ]);
    }

    public function index(Request $request)
    {
        $query = StockTransfer::select([
            'id',
            'reference_no',
            'source_branch_id',
            'destination_branch_id',
            'status',
            'picked_up_by_name',
            'pickup_notes',
            'created_at',
            'updated_at',
        ])->with([
            'sourceBranch:id,name',
            'destinationBranch:id,name',
        ]);

        $search = $request->query('search');
        $itemsPerPage = $request->query('itemsPerPage', 15);
        $page = $request->query('page', 1);

        if ($request->has('source_branch_id') && $request->source_branch_id) {
            $query->where('source_branch_id', $request->source_branch_id);
        }

        if ($request->has('destination_branch_id') && $request->destination_branch_id) {
            $query->where('destination_branch_id', $request->destination_branch_id);
        }

        if ($request->has('status') && $request->status) {
            if ($request->status === 'rejected_cancelled') {
                $query->whereIn('status', ['rejected', 'cancelled']);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($search) {
            $query->where('reference_no', 'like', "%{$search}%");
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
                // Ensure product branch records exist for both source and destination
                ProductBranch::withoutGlobalScopes()->firstOrCreate(
                    [
                        'branch_id' => $request->source_branch_id,
                        'product_id' => $item['product_id'],
                    ],
                    [
                        'stock' => 0,
                    ]
                );

                ProductBranch::withoutGlobalScopes()->firstOrCreate(
                    [
                        'branch_id' => $request->destination_branch_id,
                        'product_id' => $item['product_id'],
                    ],
                    [
                        'stock' => 0,
                    ]
                );

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Permintaan mutasi stok berhasil dibuat dan menunggu konfirmasi cabang asal.',
                'data' => $transfer->load(['items.product', 'sourceBranch', 'destinationBranch'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mutasi Stok Store Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal membuat mutasi stok', 'error' => $e->getMessage()], 400);
        }
    }

    public function show($id)
    {
        $transfer = StockTransfer::with([
            'sourceBranch',
            'destinationBranch',
            'createdBy',
            'preparedBy',
            'approvedBy',
            'receivedBy',
            'items.product'
        ])->findOrFail($id);

        // Include source current stock for each item for fulfillment verification
        foreach ($transfer->items as $item) {
            $sourceProductBranch = ProductBranch::withoutGlobalScopes()
                ->where('branch_id', $transfer->source_branch_id)
                ->where('product_id', $item->product_id)
                ->first();
            $item->source_current_stock = $sourceProductBranch ? $sourceProductBranch->stock : 0;
        }

        return response()->json($transfer);
    }

    /**
     * Tahap 2: Cabang Asal / Pusat menyetujui request & menyiapkan barang.
     * Mendukung verifikasi per item (checklist ada/kosong, isi qty kirim, dan alasan batal).
     */
    public function prepare(Request $request, $id)
    {
        $user = $request->user();
        if ($user && !$user->can('Mutasi Stok Approve') && !$user->can('Mutasi Stok Validate') && !$user->can('Mutasi Stok Write') && !$user->can('manage all')) {
            return response()->json(['message' => 'Anda tidak memiliki hak akses (permission) untuk memvalidasi / menyiapkan mutasi stok.'], 403);
        }

        try {
            DB::beginTransaction();

            $transfer = StockTransfer::with(['items.product', 'sourceBranch', 'destinationBranch'])->findOrFail($id);

            if ($transfer->status !== 'pending') {
                return response()->json(['message' => 'Mutasi stok ini tidak dalam status pending (hanya status pending yang dapat disiapkan).'], 400);
            }

            $inputItems = $request->input('items', []);
            $inputItemsById = collect($inputItems)->keyBy('id');

            $totalPrepared = 0;
            $sourceName = $transfer->sourceBranch ? $transfer->sourceBranch->name : 'Cabang Asal';
            $destName = $transfer->destinationBranch ? $transfer->destinationBranch->name : 'Cabang Tujuan';

            foreach ($transfer->items as $item) {
                $payload = $inputItemsById->get($item->id);
                
                // If payload specifies is_available === false or qty_prepared === 0
                $isAvailable = $payload ? (bool) ($payload['is_available'] ?? true) : true;
                $qtyPrepared = $payload ? (int) ($payload['qty_prepared'] ?? $item->qty) : (int) $item->qty;
                $cancelReason = $payload ? ($payload['cancel_reason'] ?? 'Barang Kosong / Habis di Unit Asal') : null;

                if (!$isAvailable || $qtyPrepared <= 0) {
                    // Mark this item as cancelled / out of stock
                    $item->qty_prepared = 0;
                    $item->status = 'cancelled';
                    $item->cancel_reason = $cancelReason ?: 'Barang Kosong / Habis di Unit Asal';
                    $item->batches_data = [];
                    $item->save();
                    continue;
                }

                // 1. Deduct from source branch
                $sourceProductBranch = ProductBranch::withoutGlobalScopes()
                    ->where('branch_id', $transfer->source_branch_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                if (!$sourceProductBranch || $sourceProductBranch->stock < $qtyPrepared) {
                    $prodName = $item->product->name ?? "ID " . $item->product_id;
                    $available = $sourceProductBranch ? $sourceProductBranch->stock : 0;
                    throw new \Exception("Stok tidak mencukupi untuk '{$prodName}' di unit asal (Diminta disiapkan: {$qtyPrepared}, Sisa stok: {$available}). Anda dapat mengurangi jumlah kirim atau tandai barang kosong.");
                }

                $sourceProductBranch->stock -= $qtyPrepared;
                $sourceProductBranch->save();

                // Record stock movement OUT at source
                StockMovement::create([
                    'product_branch_id' => $sourceProductBranch->id,
                    'type' => 'out',
                    'quantity' => $qtyPrepared,
                    'unit_cost' => $sourceProductBranch->cost_price ?? 0,
                    'notes' => "Disiapkan untuk mutasi ke {$destName} (Ref: {$transfer->reference_no})",
                ]);

                // 2. Handle Batches at source (FIFO / LIFO / FEFO)
                $remainingQty = $qtyPrepared;
                $transferredBatches = [];
                
                $stockMethod = $sourceProductBranch->product->stock_method ?? 'fifo';
                
                $batchQuery = ProductBatch::where('product_branch_id', $sourceProductBranch->id)
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
                            'batch_id' => $batch->id,
                            'entry_date' => $batch->entry_date,
                            'expiration_date' => $batch->expiration_date,
                            'cost_price' => $batch->cost_price,
                            'qty' => $deduct,
                        ];
                    }
                } else {
                    $transferredBatches[] = [
                        'batch_id' => null,
                        'entry_date' => date('Y-m-d'),
                        'expiration_date' => null,
                        'cost_price' => $sourceProductBranch->cost_price ?? 0,
                        'qty' => $qtyPrepared,
                    ];
                }

                // Save allocated batch details to transfer item
                $item->qty_prepared = $qtyPrepared;
                $item->status = 'prepared';
                $item->cancel_reason = null;
                $item->batches_data = $transferredBatches;
                $item->save();

                $totalPrepared += $qtyPrepared;
            }

            $userId = $request->user()->id ?? null;

            // If all items were cancelled (totalPrepared === 0)
            if ($totalPrepared === 0) {
                $transfer->update([
                    'status' => 'rejected',
                    'approved_by' => $userId,
                    'notes' => ($transfer->notes ? $transfer->notes . ' | ' : '') . 'Dibatalkan: Semua barang kosong di unit asal.',
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'Semua barang kosong / tidak tersedia. Permintaan mutasi otomatis ditolak/dibatalkan.',
                    'data' => $transfer->fresh(['sourceBranch', 'destinationBranch', 'items.product'])
                ]);
            }

            $transfer->update([
                'status' => 'ready_for_pickup',
                'prepared_by' => $userId,
                'prepared_at' => now(),
                'approved_by' => $userId, // backwards compatibility
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Barang berhasil disiapkan. Status sekarang: Siap Dijemput.',
                'data' => $transfer->fresh(['sourceBranch', 'destinationBranch', 'preparedBy', 'items.product'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mutasi Stok Prepare Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menyiapkan barang mutasi stok', 'error' => $e->getMessage()], 400);
        }
    }

    // Alias for approve (backwards compatible)
    public function approve(Request $request, $id)
    {
        return $this->prepare($request, $id);
    }

    /**
     * Tahap 3: Cabang Pemohon menjemput barang di unit asal dan konfirmasi penerimaan.
     * Stok masuk ke Cabang Pemohon (Destination Branch) dan status berubah menjadi 'completed'.
     */
    public function receive(Request $request, $id)
    {
        $user = $request->user();
        if ($user && !$user->can('Mutasi Stok Approve') && !$user->can('Mutasi Stok Validate') && !$user->can('Mutasi Stok Write') && !$user->can('manage all')) {
            return response()->json(['message' => 'Anda tidak memiliki hak akses (permission) untuk mengonfirmasi penerimaan mutasi stok.'], 403);
        }

        try {
            DB::beginTransaction();

            $transfer = StockTransfer::with(['items.product', 'sourceBranch', 'destinationBranch'])->findOrFail($id);

            if (!in_array($transfer->status, ['ready_for_pickup', 'approved'])) {
                return response()->json(['message' => 'Hanya mutasi yang sudah disiapkan / siap dijemput yang dapat dikonfirmasi penerimaannya.'], 400);
            }

            $sourceName = $transfer->sourceBranch ? $transfer->sourceBranch->name : 'Cabang Asal';
            $destName = $transfer->destinationBranch ? $transfer->destinationBranch->name : 'Cabang Tujuan';

            foreach ($transfer->items as $item) {
                // Skip cancelled / empty items
                if ($item->status === 'cancelled' || ($item->qty_prepared !== null && $item->qty_prepared <= 0)) {
                    continue;
                }

                $qtyToReceive = $item->qty_prepared ?? $item->qty;
                if ($qtyToReceive <= 0) continue;

                $sourceProductBranch = ProductBranch::withoutGlobalScopes()
                    ->where('branch_id', $transfer->source_branch_id)
                    ->where('product_id', $item->product_id)
                    ->first();

                $sourcePrice = $sourceProductBranch ? $sourceProductBranch->price : 0;
                $sourceCostPrice = $sourceProductBranch ? $sourceProductBranch->cost_price : 0;

                // Add to destination branch
                $destinationProductBranch = ProductBranch::withoutGlobalScopes()->firstOrCreate(
                    [
                        'branch_id' => $transfer->destination_branch_id,
                        'product_id' => $item->product_id,
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'stock' => 0,
                        'price' => $sourcePrice,
                        'cost_price' => $sourceCostPrice,
                        'min_stock' => 0,
                        'is_active' => true
                    ]
                );

                $destinationProductBranch->stock += $qtyToReceive;
                if ($destinationProductBranch->cost_price == 0 && $sourceCostPrice > 0) {
                    $destinationProductBranch->cost_price = $sourceCostPrice;
                }
                $destinationProductBranch->save();

                // Add batches to destination branch
                $batches = $item->batches_data;
                if (empty($batches) || !is_array($batches)) {
                    $batches = [[
                        'entry_date' => date('Y-m-d'),
                        'expiration_date' => null,
                        'cost_price' => $sourceCostPrice,
                        'qty' => $qtyToReceive,
                    ]];
                }

                foreach ($batches as $tBatch) {
                    $destBatchQuery = ProductBatch::where('product_branch_id', $destinationProductBranch->id)
                        ->where('entry_date', $tBatch['entry_date'] ?? date('Y-m-d'))
                        ->where('cost_price', $tBatch['cost_price'] ?? 0);
                        
                    if (!empty($tBatch['expiration_date'])) {
                        $destBatchQuery->where('expiration_date', $tBatch['expiration_date']);
                    } else {
                        $destBatchQuery->whereNull('expiration_date');
                    }
                    
                    $destBatch = $destBatchQuery->first();
                    
                    if (!$destBatch) {
                        $destBatch = ProductBatch::create([
                            'product_branch_id' => $destinationProductBranch->id,
                            'qty' => 0,
                            'entry_date' => $tBatch['entry_date'] ?? date('Y-m-d'),
                            'expiration_date' => $tBatch['expiration_date'] ?? null,
                            'cost_price' => $tBatch['cost_price'] ?? 0,
                        ]);
                    }
                    
                    $destBatch->qty += $tBatch['qty'];
                    $destBatch->save();
                }

                // Record stock movement IN at destination
                StockMovement::create([
                    'product_branch_id' => $destinationProductBranch->id,
                    'type' => 'in',
                    'quantity' => $qtyToReceive,
                    'unit_cost' => $sourceCostPrice,
                    'notes' => "Diterima dari penjemputan di {$sourceName} (Ref: {$transfer->reference_no})",
                ]);
            }

            $transfer->update([
                'status' => 'completed',
                'received_by' => $request->user()->id ?? null,
                'received_at' => now(),
                'picked_up_by_name' => $request->picked_up_by_name ? trim($request->picked_up_by_name) : null,
                'pickup_notes' => $request->pickup_notes ? trim($request->pickup_notes) : null,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Barang telah berhasil dijemput dan stok telah masuk ke unit tujuan.',
                'data' => $transfer->fresh(['sourceBranch', 'destinationBranch', 'receivedBy', 'items.product'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mutasi Stok Receive Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal menerima/menjemput barang mutasi', 'error' => $e->getMessage()], 400);
        }
    }

    public function reject(Request $request, $id)
    {
        $user = $request->user();
        if ($user && !$user->can('Mutasi Stok Approve') && !$user->can('Mutasi Stok Validate') && !$user->can('Mutasi Stok Write') && !$user->can('manage all')) {
            return response()->json(['message' => 'Anda tidak memiliki hak akses untuk menolak mutasi stok.'], 403);
        }

        $transfer = StockTransfer::findOrFail($id);

        if ($transfer->status !== 'pending') {
            return response()->json(['message' => 'Hanya mutasi berstatus pending yang bisa ditolak.'], 400);
        }

        $transfer->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id ?? null,
            'notes' => ($transfer->notes ? $transfer->notes . ' | ' : '') . ($request->reason ? "Alasan Penolakan: {$request->reason}" : 'Permintaan Ditolak.'),
        ]);

        return response()->json(['message' => 'Permintaan mutasi stok berhasil ditolak.']);
    }

    /**
     * Membatalkan mutasi stok.
     * Jika sudah 'ready_for_pickup', kembalikan stok dan batch ke cabang asal.
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        if ($user && !$user->can('Mutasi Stok Approve') && !$user->can('Mutasi Stok Validate') && !$user->can('Mutasi Stok Write') && !$user->can('manage all')) {
            return response()->json(['message' => 'Anda tidak memiliki hak akses untuk membatalkan mutasi stok.'], 403);
        }

        try {
            DB::beginTransaction();

            $transfer = StockTransfer::with(['items', 'sourceBranch', 'destinationBranch'])->findOrFail($id);

            if (!in_array($transfer->status, ['pending', 'ready_for_pickup', 'approved'])) {
                return response()->json(['message' => 'Mutasi yang sudah selesai atau ditolak tidak dapat dibatalkan.'], 400);
            }

            // If stock was already deducted (ready_for_pickup / approved), restore it to source
            if (in_array($transfer->status, ['ready_for_pickup', 'approved'])) {
                $destName = $transfer->destinationBranch ? $transfer->destinationBranch->name : 'Cabang Tujuan';

                foreach ($transfer->items as $item) {
                    // Only restore items that were actually prepared
                    if ($item->status === 'cancelled' || ($item->qty_prepared !== null && $item->qty_prepared <= 0)) {
                        continue;
                    }

                    $qtyToRestore = $item->qty_prepared ?? $item->qty;
                    if ($qtyToRestore <= 0) continue;

                    $sourceProductBranch = ProductBranch::withoutGlobalScopes()
                        ->where('branch_id', $transfer->source_branch_id)
                        ->where('product_id', $item->product_id)
                        ->first();

                    if ($sourceProductBranch) {
                        $sourceProductBranch->stock += $qtyToRestore;
                        $sourceProductBranch->save();

                        // Restore batches if recorded
                        $batches = $item->batches_data;
                        if (!empty($batches) && is_array($batches)) {
                            foreach ($batches as $b) {
                                if (!empty($b['batch_id'])) {
                                    $batchModel = ProductBatch::find($b['batch_id']);
                                    if ($batchModel) {
                                        $batchModel->qty += $b['qty'];
                                        $batchModel->save();
                                        continue;
                                    }
                                }

                                // If original batch not found, match or create
                                $bQuery = ProductBatch::where('product_branch_id', $sourceProductBranch->id)
                                    ->where('entry_date', $b['entry_date'] ?? date('Y-m-d'))
                                    ->where('cost_price', $b['cost_price'] ?? 0);
                                    
                                if (!empty($b['expiration_date'])) {
                                    $bQuery->where('expiration_date', $b['expiration_date']);
                                } else {
                                    $bQuery->whereNull('expiration_date');
                                }
                                
                                $matchedBatch = $bQuery->first();
                                if ($matchedBatch) {
                                    $matchedBatch->qty += $b['qty'];
                                    $matchedBatch->save();
                                } else {
                                    ProductBatch::create([
                                        'product_branch_id' => $sourceProductBranch->id,
                                        'qty' => $b['qty'],
                                        'entry_date' => $b['entry_date'] ?? date('Y-m-d'),
                                        'expiration_date' => $b['expiration_date'] ?? null,
                                        'cost_price' => $b['cost_price'] ?? 0,
                                    ]);
                                }
                            }
                        }

                        // Record movement IN to source
                        StockMovement::create([
                            'product_branch_id' => $sourceProductBranch->id,
                            'type' => 'in',
                            'quantity' => $qtyToRestore,
                            'unit_cost' => $sourceProductBranch->cost_price ?? 0,
                            'notes' => "Pembatalan mutasi ke {$destName} (Ref: {$transfer->reference_no})",
                        ]);
                    }
                }
            }

            $transfer->update([
                'status' => 'cancelled',
            ]);

            DB::commit();

            return response()->json(['message' => 'Mutasi stok berhasil dibatalkan dan stok dikembalikan ke unit asal.']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mutasi Stok Cancel Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal membatalkan mutasi stok', 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get Delivery Note (Surat Jalan) details for printing
     */
    public function deliveryNote($id)
    {
        $transfer = StockTransfer::withoutGlobalScopes()
            ->with([
                'sourceBranch',
                'destinationBranch',
                'createdBy:id,name',
                'preparedBy:id,name',
                'approvedBy:id,name',
                'receivedBy:id,name',
                'items.product:id,name,sku,unit,barcode',
            ])
            ->findOrFail($id);

        return response()->json([
            'data' => $transfer,
        ]);
    }
}
