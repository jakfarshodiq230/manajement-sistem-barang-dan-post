<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = \App\Models\Sale::with(['branch', 'user', 'approver', 'items.productBranch.product', 'receivable.payments']);
        
        $search = $request->query('search');
        $itemsPerPage = $request->query('itemsPerPage', 15);
        $page = $request->query('page', 1);

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = \Carbon\Carbon::parse($request->start_date)->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        $query->orderBy('created_at', 'desc');

        if ($itemsPerPage == -1) {
            $sales = $query->get();
            $paginated = null;
        } else {
            $paginated = $query->paginate($itemsPerPage, ['*'], 'page', $page);
            $sales = $paginated->items();
        }
        
        $summaryQuery = clone $query;
        if (!$request->has('start_date') && !$request->has('end_date')) {
            $summaryQuery->whereDate('date', now()->toDateString());
        }
        $summaryQuery->reorder(); // Remove orderBy to prevent GROUP BY error
        $summaryQuery->limit(PHP_INT_MAX)->offset(0); // Remove limit/offset from paginate
        $summaryData = $summaryQuery->select('payment_method', \Illuminate\Support\Facades\DB::raw("SUM(total_amount) as total_net"))
                                    ->where('status', '!=', 'cancelled')
                                    ->groupBy('payment_method')
                                    ->get();
        
        $summary = [
            'cash' => 0,
            'transfer' => 0,
            'qris' => 0,
            'tempo' => 0,
        ];

        foreach ($summaryData as $row) {
            $method = $row->payment_method;
            if (array_key_exists($method, $summary)) {
                $summary[$method] = $row->total_net;
            }
        }

        $response = [
            'data' => $sales,
            'summary' => $summary,
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
        if (!request()->user()->can('Kasir (POS) Create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'date' => 'required|date',
            'discount' => 'numeric|min:0',
            'approved_by' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_branch_id' => 'required|exists:product_branches,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'payment_method' => 'nullable|in:cash,transfer,qris,tempo',
            'paid_amount' => 'nullable|numeric|min:0',
            'change_amount' => 'nullable|numeric|min:0',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'bank_account_name' => 'nullable|string',
            'transfer_phone_number' => 'nullable|string',
            'payment_proof' => 'nullable|image|max:5120', // max 5MB
            'customer_id' => 'nullable',
            'customer_name' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'dp_amount' => 'nullable|numeric|min:0',
            'dp_payment_method' => 'nullable|in:cash,transfer,qris',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();

        $requestedDate = $request->date;
        $isRequestedDateClosed = \App\Models\CashReconciliation::where('branch_id', $request->branch_id)
            ->where('date', $requestedDate)
            ->exists();
            
        if ($isRequestedDateClosed) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Gagal: Cabang ini sudah melakukan Closing Kasir untuk tanggal ' . $requestedDate . '. Anda tidak bisa menambah transaksi penjualan baru!'], 400);
        }

        // Check if there are any previous days with sales that haven't been closed
        $today = \Carbon\Carbon::today()->toDateString();
        $unclosedDates = \Illuminate\Support\Facades\DB::table('sales')
            ->where('branch_id', $request->branch_id)
            ->where('user_id', $request->user()->id)
            ->where('date', '<', $today)
            ->groupBy('date')
            ->pluck('date');
            
        foreach ($unclosedDates as $pastDate) {
            $hasClosed = \App\Models\CashReconciliation::where('branch_id', $request->branch_id)
                ->where('user_id', $request->user()->id)
                ->where('date', $pastDate)
                ->exists();
                
            if (!$hasClosed) {
                \Illuminate\Support\Facades\DB::rollBack();
                return response()->json(['message' => 'Gagal: Anda belum melakukan Closing Harian Kasir untuk tanggal ' . $pastDate . '. Harap selesaikan closing terlebih dahulu!'], 400);
            }
        }
        try {
            $invoice_number = 'INV-' . date('YmdHis') . '-' . rand(100, 999);

            $finalCustomerId = $request->customer_id;
            if (!$finalCustomerId && $request->filled('customer_name')) {
                $cust = \App\Models\Customer::firstOrCreate(
                    ['name' => trim($request->customer_name)],
                    ['is_active' => true]
                );
                $finalCustomerId = $cust->id;
            } elseif ($finalCustomerId && !is_numeric($finalCustomerId)) {
                $cust = \App\Models\Customer::firstOrCreate(
                    ['name' => trim($finalCustomerId)],
                    ['is_active' => true]
                );
                $finalCustomerId = $cust->id;
            }
            
            $sale = \App\Models\Sale::create([
                'invoice_number' => $invoice_number,
                'branch_id' => $request->branch_id,
                'user_id' => $request->user()->id,
                'approved_by' => $request->approved_by,
                'date' => $request->date,
                'subtotal' => 0,
                'discount' => $request->discount ?? 0,
                'total_amount' => 0,
                'status' => 'completed',
                'notes' => $request->notes,
                'payment_method' => $request->payment_method ?? 'cash',
                'paid_amount' => $request->payment_method === 'tempo' ? ($request->dp_amount ?? 0) : ($request->paid_amount ?? 0),
                'change_amount' => $request->change_amount ?? 0,
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_account_name' => $request->bank_account_name,
                'transfer_phone_number' => $request->transfer_phone_number,
                'customer_id' => $finalCustomerId,
                'due_date' => $request->payment_method === 'tempo' ? $request->due_date : null,
            ]);

            if ($request->hasFile('payment_proof')) {
                $path = $request->file('payment_proof')->store('payment_proofs', 'public');
                $sale->update(['payment_proof' => $path]);
            }

            $subtotal = 0;
            $total_tax = 0;

            foreach ($request->items as $item) {
                $productBranch = \App\Models\ProductBranch::lockForUpdate()->findOrFail($item['product_branch_id']);
                
                if ($productBranch->stock < $item['qty']) {
                    throw new \Exception("Stok tidak mencukupi untuk barang: " . $productBranch->product->name);
                }

                $item_subtotal = $item['qty'] * $item['price'];
                
                $tax_type = $productBranch->product->tax_type ?? 'Exclude PPN';
                $tax_percentage = $productBranch->tax_percentage ?? 0;
                $tax_amount = 0;

                if ($tax_type === 'Exclude PPN') {
                    $tax_amount = ($item_subtotal * $tax_percentage) / 100;
                    $total_tax += $tax_amount;
                } else if ($tax_type === 'Include PPN') {
                    // For Include PPN, we extract the tax amount from the subtotal, but it doesn't increase the customer's total bill
                    $tax_amount = $item_subtotal - ($item_subtotal / (1 + ($tax_percentage / 100)));
                }

                $subtotal += $item_subtotal;

                // Validate minimum nego price
                $minNegoPrice = 0;
                if (isset($item['batch_id']) && $item['batch_id']) {
                    $specificBatch = \App\Models\ProductBatch::find($item['batch_id']);
                    $minNegoPrice = $specificBatch ? $specificBatch->min_nego_price : $productBranch->min_nego_price;
                } else {
                    $activeBatch = $productBranch->active_batch;
                    $minNegoPrice = $activeBatch ? $activeBatch->min_nego_price : $productBranch->min_nego_price;
                }

                if ($item['price'] < $minNegoPrice) {
                    throw new \Exception("Harga " . $productBranch->product->name . " tidak boleh kurang dari batas minimum Rp " . number_format($minNegoPrice, 0, ',', '.'));
                }

                \App\Models\SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_branch_id' => $productBranch->id,
                    'qty' => $item['qty'],
                    'original_price' => $productBranch->price, // Original selling price
                    'price' => $item['price'], // Price might be negotiated
                    'cost_price' => $productBranch->cost_price, // Snapshot cost
                    'tax_percentage' => $tax_percentage,
                    'tax_amount' => $tax_amount,
                    'subtotal' => $item_subtotal,
                ]);

                // Create Stock Movement (Out)
                \App\Models\StockMovement::create([
                    'product_branch_id' => $productBranch->id,
                    'user_id' => $request->user()->id,
                    'type' => 'out',
                    'quantity' => $item['qty'],
                    'unit_cost' => $productBranch->cost_price,
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'notes' => 'Penjualan Bon: ' . $invoice_number,
                ]);

                // FIFO / LIFO / FEFO Logic OR Specific Batch (Option B)
                $qtyToDeduct = $item['qty'];

                if (isset($item['batch_id']) && $item['batch_id']) {
                    $specificBatch = \App\Models\ProductBatch::lockForUpdate()->find($item['batch_id']);
                    if ($specificBatch && $specificBatch->qty >= $qtyToDeduct) {
                        $specificBatch->decrement('qty', $qtyToDeduct);
                        $qtyToDeduct = 0;
                    } else {
                        throw new \Exception("Stok spesifik (Batch #" . $item['batch_id'] . ") tidak mencukupi untuk barang: " . $productBranch->product->name);
                    }
                } else {
                    $stockMethod = $productBranch->product->stock_method ?? 'fifo';
                    
                    $batchQuery = \App\Models\ProductBatch::where('product_branch_id', $productBranch->id)
                        ->where('qty', '>', 0);
                        
                    if ($stockMethod === 'fefo') {
                        // Sort by expiration date ascending (nulls last)
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
                }

                if ($qtyToDeduct > 0) {
                     throw new \Exception("Stok tidak mencukupi untuk barang: " . $productBranch->product->name . " (Sisa Qty: " . $qtyToDeduct . ")");
                }

                // Deduct overall branch stock (cache)
                $productBranch->decrement('stock', $item['qty']);
            }

            $total_amount = max(0, $subtotal + $total_tax - ($request->discount ?? 0));

            $paymentMethod = $request->payment_method ?? 'cash';
            
            if ($paymentMethod === 'cash' && $request->paid_amount !== null && $request->paid_amount < $total_amount) {
                throw new \Exception("Uang bayar tidak boleh kurang dari total tagihan.");
            }
            
            $sale->update([
                'subtotal' => $subtotal,
                'discount' => $request->discount ?? 0,
                'total_tax' => $total_tax,
                'total_amount' => $total_amount
            ]);

            // Jika tempo atau split payment dengan piutang, catat di piutang
            if ($paymentMethod === 'tempo' || $paymentMethod === 'split' || ($request->has_receivable && $request->credit_amount > 0)) {
                if (!$finalCustomerId) {
                    throw new \Exception("Pelanggan wajib dipilih untuk pembayaran tempo/piutang.");
                }
                
                $dpAmount = $paymentMethod === 'tempo' ? ($request->dp_amount ?? 0) : ($request->paid_cash_amount ?? $request->paid_amount ?? 0);

                if ($dpAmount > $total_amount) {
                    throw new \Exception("Uang Muka/Bayar Tunai tidak boleh lebih dari total tagihan.");
                }

                $receivable = \App\Models\Receivable::create([
                    'sale_id' => $sale->id,
                    'customer_id' => $finalCustomerId,
                    'branch_id' => $request->branch_id,
                    'amount_due' => $total_amount,
                    'amount_paid' => $dpAmount,
                    'due_date' => $request->due_date ?? Carbon::now()->addDays(30)->toDateString(),
                    'status' => $dpAmount >= $total_amount ? 'paid' : ($dpAmount > 0 ? 'partial' : 'unpaid')
                ]);

                // Jika ada DP/pembayaran awal, masukkan ke tabel receivable_payments
                if ($dpAmount > 0) {
                    \App\Models\ReceivablePayment::create([
                        'receivable_id' => $receivable->id,
                        'user_id' => $request->user()->id,
                        'amount' => $dpAmount,
                        'payment_date' => $request->date,
                        'payment_method' => $request->dp_payment_method ?? 'cash',
                        'payment_proof' => $request->hasFile('payment_proof') ? $sale->payment_proof : null,
                    ]);
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'message' => 'Transaksi berhasil',
                'sale' => $sale->load('items.productBranch.product', 'approver', 'customer'),
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('SaleController Store Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Gagal memproses transaksi: ' . $e->getMessage(), 'error' => $e->getMessage()], 400);
        }
    }

    public function show($id)
    {
        $sale = \App\Models\Sale::with(['branch', 'user', 'approver', 'items.productBranch.product'])->findOrFail($id);
        return response()->json($sale);
    }

    public function update(Request $request, $id)
    {
        if (!request()->user()->can('Kasir (POS) Write')) {
            abort(403, 'Unauthorized action.');
        }

        return response()->json(['message' => 'Not implemented'], 501);
    }

    public function destroy(Request $request, $id)
    {
        if (!request()->user()->can('Kasir (POS) Delete')) {
            abort(403, 'Unauthorized action.');
        }

        // Validate PIN
        $request->validate([
            'pin' => 'required|string',
        ]);

        $user = request()->user();
        if (!\Illuminate\Support\Facades\Hash::check($request->pin, $user->pin)) {
            return response()->json(['message' => 'PIN salah! Tidak dapat membatalkan transaksi.'], 403);
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $sale = \App\Models\Sale::with('items.productBranch')->findOrFail($id);

            if ($sale->status === 'cancelled') {
                return response()->json(['message' => 'Transaksi sudah dibatalkan sebelumnya.'], 400);
            }

            // Handle Receivable if exists
            $receivable = \App\Models\Receivable::where('sale_id', $sale->id)->first();
            if ($receivable) {
                // Delete payments manually in case cascade is not set
                \App\Models\ReceivablePayment::where('receivable_id', $receivable->id)->delete();
                $receivable->delete();
            }

            // Restore stock for each item
            foreach ($sale->items as $item) {
                if ($item->productBranch) {
                    // Create Stock Movement (In) for void
                    \App\Models\StockMovement::create([
                        'product_branch_id' => $item->product_branch_id,
                        'user_id' => request()->user()->id,
                        'type' => 'in',
                        'quantity' => $item->qty,
                        'unit_cost' => $item->cost_price,
                        'reference_type' => 'void_sale',
                        'reference_id' => $sale->id,
                        'notes' => 'Pembatalan (Void) Bon: ' . $sale->invoice_number,
                    ]);

                    // Add stock back (cache)
                    $item->productBranch->increment('stock', $item->qty);

                    // Add stock back to the active batch (FIFO logic)
                    $pb = $item->productBranch;
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
                            'cost_price' => $item->cost_price,
                            'price' => $item->price,
                            'min_nego_price' => $pb->min_nego_price,
                            'entry_date' => now(),
                        ]);
                    }
                }
            }

            $sale->update(['status' => 'cancelled']);

            \Illuminate\Support\Facades\DB::commit();

            return response()->json(['message' => 'Transaksi berhasil dibatalkan dan stok telah dikembalikan.']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['message' => 'Gagal membatalkan transaksi', 'error' => $e->getMessage()], 400);
        }
    }
}
