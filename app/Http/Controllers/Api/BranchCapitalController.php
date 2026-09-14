<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\StockTransfer;
use App\Models\ProductBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BranchCapitalController extends Controller
{
    /**
     * Ringkasan eksekutif distribusi modal barang dari Pusat ke seluruh Cabang.
     * Menampilkan total nilai modal barang yang dikirim, estimasi omset, dan sisa stok.
     */
    public function summary(Request $request)
    {
        $period     = $request->input('period');       // format: YYYY-MM (opsional)
        $branchId   = $request->input('branch_id');    // filter per cabang

        $capitalQuery = StockTransfer::where('is_capital_transfer', true)
            ->whereIn('status', ['completed', 'in_transit', 'ready_for_pickup', 'approved']);

        if ($branchId && $branchId !== 'all') {
            $capitalQuery->where('destination_branch_id', $branchId);
        }

        if ($period) {
            [$year, $month] = explode('-', $period);
            $capitalQuery->whereYear('created_at', $year)->whereMonth('created_at', $month);
        }

        $totalCapitalValue = (float) $capitalQuery->sum('capital_value');
        $totalTransactions = $capitalQuery->count();

        // Omset penjualan cabang-cabang pada periode tersebut
        $salesQuery = DB::table('sales')->where('status', '!=', 'cancelled');
        if ($branchId && $branchId !== 'all') {
            $salesQuery->where('branch_id', $branchId);
        }
        if ($period) {
            [$year, $month] = explode('-', $period);
            $salesQuery->whereYear('date', $year)->whereMonth('date', $month);
        }
        $totalSales = (float) $salesQuery->sum('total_amount');

        // Breakdown per cabang
        $branches = Branch::select('id', 'name', 'type', 'status')->get();
        $branchBreakdown = $branches->map(function ($branch) use ($period) {
            $capQuery = StockTransfer::where('is_capital_transfer', true)
                ->where('destination_branch_id', $branch->id)
                ->whereIn('status', ['completed', 'in_transit', 'ready_for_pickup', 'approved']);

            if ($period) {
                [$y, $m] = explode('-', $period);
                $capQuery->whereYear('created_at', $y)->whereMonth('created_at', $m);
            }

            $modalValue     = (float) $capQuery->sum('capital_value');
            $transferCount  = $capQuery->count();

            $sQuery = DB::table('sales')
                ->where('branch_id', $branch->id)
                ->where('status', '!=', 'cancelled');
            if ($period) {
                [$y, $m] = explode('-', $period);
                $sQuery->whereYear('date', $y)->whereMonth('date', $m);
            }
            $omset = (float) $sQuery->sum('total_amount');

            // Estimasi sisa stok nilai (modal - omset, minimum 0)
            $sisaEstimasi = max(0, $modalValue - $omset);
            $pctTerjual   = $modalValue > 0 ? round(min(100, ($omset / $modalValue) * 100), 1) : 0;

            return [
                'branch_id'         => $branch->id,
                'branch_name'       => $branch->name,
                'branch_type'       => $branch->type,
                'modal_value'       => $modalValue,
                'transfer_count'    => $transferCount,
                'omset_penjualan'   => $omset,
                'sisa_estimasi'     => $sisaEstimasi,
                'pct_terjual'       => $pctTerjual,
            ];
        })->filter(fn($b) => $b['modal_value'] > 0 || $b['omset_penjualan'] > 0)->values();

        return response()->json([
            'total_capital_value' => $totalCapitalValue,
            'total_transactions'  => $totalTransactions,
            'total_sales'         => $totalSales,
            'branch_breakdown'    => $branchBreakdown,
        ]);
    }

    /**
     * Daftar distribusi modal barang (stock transfers yang is_capital_transfer = true).
     * Digunakan untuk Tab 1 (Riwayat Pengiriman Modal Barang dari Pusat).
     */
    public function distributions(Request $request)
    {
        $query = StockTransfer::with([
                'sourceBranch:id,name',
                'destinationBranch:id,name',
                'createdBy:id,name',
                'items.product:id,name,sku',
            ])
            ->where('is_capital_transfer', true);

        if ($request->filled('branch_id') && $request->branch_id !== 'all') {
            $query->where('destination_branch_id', $request->branch_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('period')) {
            [$year, $month] = explode('-', $request->period);
            $query->whereYear('created_at', $year)->whereMonth('created_at', $month);
        }

        if ($request->filled('search')) {
            $query->where('reference_no', 'like', '%' . $request->search . '%');
        }

        $perPage = (int) $request->input('per_page', 15);
        $data    = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($data);
    }

    /**
     * Rekonsiliasi Akhir Bulan: Bandingkan nilai modal barang yang dikirim Pusat
     * dengan omset penjualan dan estimasi sisa stok per cabang per periode.
     */
    public function reconciliation(Request $request)
    {
        $period   = $request->input('period', now()->format('Y-m')); // default bulan ini
        $branchId = $request->input('branch_id');

        [$year, $month] = explode('-', $period);
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate   = Carbon::create($year, $month, 1)->endOfMonth();

        $branches = Branch::select('id', 'name', 'type', 'status')->get();
        if ($branchId && $branchId !== 'all') {
            $branches = $branches->where('id', $branchId)->values();
        }

        $result = $branches->map(function ($branch) use ($startDate, $endDate, $year, $month) {
            // 1. Total nilai modal barang yang dikirim Pusat ke cabang ini di periode ini
            $distributions = StockTransfer::with(['items.product:id,name,sku,cost_price'])
                ->where('is_capital_transfer', true)
                ->where('destination_branch_id', $branch->id)
                ->whereIn('status', ['completed', 'in_transit', 'ready_for_pickup', 'approved'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $modalValue    = (float) $distributions->sum('capital_value');
            $totalItemsQty = $distributions->sum(fn($t) => $t->items->sum('qty'));

            // Rincian per produk yang dikirim
            $productDetails = collect();
            foreach ($distributions as $dist) {
                foreach ($dist->items as $item) {
                    $key = $item->product_id;
                    if ($productDetails->has($key)) {
                        $existing = $productDetails->get($key);
                        $existing['qty_dikirim'] += $item->qty;
                        $existing['nilai_modal']  += ($item->qty * ($item->product->cost_price ?? 0));
                        $productDetails->put($key, $existing);
                    } else {
                        $productDetails->put($key, [
                            'product_id'   => $item->product_id,
                            'product_name' => $item->product->name ?? '-',
                            'sku'          => $item->product->sku ?? '-',
                            'qty_dikirim'  => $item->qty,
                            'nilai_modal'  => ($item->qty * ($item->product->cost_price ?? 0)),
                            'qty_terjual'  => 0,
                            'omset'        => 0,
                        ]);
                    }
                }
            }

            // 2. Omset penjualan cabang ini di periode ini
            $salesTotal = (float) DB::table('sales')
                ->where('branch_id', $branch->id)
                ->where('status', '!=', 'cancelled')
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->sum('total_amount');

            // Qty terjual per produk dari sales_items
            $soldItems = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('product_branches', 'sale_items.product_branch_id', '=', 'product_branches.id')
                ->where('sales.branch_id', $branch->id)
                ->where('sales.status', '!=', 'cancelled')
                ->whereYear('sales.date', $year)
                ->whereMonth('sales.date', $month)
                ->select('product_branches.product_id',
                    DB::raw('SUM(sale_items.qty) as qty_terjual'),
                    DB::raw('SUM(sale_items.subtotal) as omset'))
                ->groupBy('product_branches.product_id')
                ->get();

            foreach ($soldItems as $sold) {
                if ($productDetails->has($sold->product_id)) {
                    $p = $productDetails->get($sold->product_id);
                    $p['qty_terjual'] = $sold->qty_terjual;
                    $p['omset']       = (float) $sold->omset;
                    $productDetails->put($sold->product_id, $p);
                }
            }

            // 3. Sisa stok aktual cabang (dari product_branches)
            $stockNilai = (float) ProductBranch::withoutGlobalScopes()
                ->join('products', 'product_branches.product_id', '=', 'products.id')
                ->where('product_branches.branch_id', $branch->id)
                ->where('product_branches.stock', '>', 0)
                ->sum(DB::raw('product_branches.stock * product_branches.cost_price'));

            // 4. Kalkulasi rekonsiliasi
            $selisih    = $modalValue > 0 ? ($salesTotal + $stockNilai) - $modalValue : 0;
            $pctTerjual = $modalValue > 0 ? round(min(100, ($salesTotal / $modalValue) * 100), 1) : 0;

            $status = 'no_data';
            if ($modalValue > 0) {
                if (abs($selisih) < 1000) {
                    $status = 'seimbang';    // Omset + Sisa ≈ Modal → OK
                } elseif ($selisih < 0) {
                    $status = 'selisih';     // Ada susut / hilang
                } else {
                    $status = 'surplus';     // Omset melebihi modal (markup tinggi)
                }
            }

            return [
                'branch_id'       => $branch->id,
                'branch_name'     => $branch->name,
                'period'          => $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT),
                'modal_value'     => $modalValue,
                'total_items_qty' => $totalItemsQty,
                'omset_penjualan' => $salesTotal,
                'sisa_stok_nilai' => $stockNilai,
                'selisih'         => $selisih,
                'pct_terjual'     => $pctTerjual,
                'status'          => $status,
                'product_details' => $productDetails->values(),
            ];
        })->filter(fn($b) => $b['modal_value'] > 0 || $b['omset_penjualan'] > 0)->values();

        return response()->json([
            'period' => $period,
            'data'   => $result,
        ]);
    }

    /**
     * index() — tidak dipakai lagi tapi dipertahankan untuk backward compatibility.
     * Redirect ke distributions().
     */
    public function index(Request $request)
    {
        return $this->distributions($request);
    }

    // store/show/update/destroy tidak relevan lagi (distribusi barang via StockTransfer)
    public function store(Request $request)
    {
        return response()->json(['message' => 'Gunakan endpoint /api/apps/stock-transfers dengan is_capital_transfer=true untuk mendistribusikan modal barang.'], 422);
    }

    public function show($id)
    {
        $transfer = StockTransfer::with([
            'sourceBranch:id,name',
            'destinationBranch:id,name',
            'createdBy:id,name',
            'items.product:id,name,sku,cost_price',
        ])->where('is_capital_transfer', true)->findOrFail($id);

        return response()->json($transfer);
    }

    public function update(Request $request, $id)
    {
        return response()->json(['message' => 'Perbarui melalui modul Mutasi Stok.'], 422);
    }

    public function destroy($id)
    {
        return response()->json(['message' => 'Hapus melalui modul Mutasi Stok.'], 422);
    }
}
