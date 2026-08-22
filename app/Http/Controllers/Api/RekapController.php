<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\CashReconciliation;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class RekapController extends Controller
{
    private function getTahunanData(int $year)
    {
        $now   = Carbon::now();

        $monthNames = [
            1  => 'Januari',   2  => 'Februari', 3  => 'Maret',
            4  => 'April',     5  => 'Mei',       6  => 'Juni',
            7  => 'Juli',      8  => 'Agustus',   9  => 'September',
            10 => 'Oktober',   11 => 'November',  12 => 'Desember',
        ];

        $rows = [];
        $kumulatifOmset  = 0;
        $kumulatifLaba   = 0;

        for ($m = 1; $m <= 12; $m++) {
            $isFuture = ($year > $now->year) || ($year == $now->year && $m > $now->month);

            // --- Penjualan (Sales) ---
            $sales = Sale::where('status', 'completed')
                ->whereYear('date', $year)
                ->whereMonth('date', $m)
                ->get();

            $omset = $sales->sum('total_amount');

            // COGS calculation
            $cogs = 0;
            foreach ($sales as $sale) {
                $cogs += DB::table('sale_items')
                    ->where('sale_id', $sale->id)
                    ->sum(DB::raw('cost_price * qty'));
            }
            $labaKotor   = $omset - $cogs;
            $expenses    = \App\Models\PettyCash::whereYear('date', $year)->whereMonth('date', $m)->sum('amount');
            $labaBersih  = $labaKotor - $expenses;
            $jumlahTx    = $sales->count();

            // --- Kas / Closing Harian ---
            $closings = CashReconciliation::whereYear('date', $year)
                ->whereMonth('date', $m)
                ->get();

            $totalKasMasuk  = $closings->where('variance', '>', 0)->sum('variance');
            $totalKasKurang = $closings->where('variance', '<', 0)->sum('variance');
            $selisihKas     = $closings->sum('variance');
            $jumlahClosing  = $closings->count();

            // --- Mutasi Stok ---
            $mutasiMasuk  = StockMovement::whereYear('created_at', $year)
                ->whereMonth('created_at', $m)
                ->where('type', 'in')
                ->sum('quantity');
            $mutasiKeluar = StockMovement::whereYear('created_at', $year)
                ->whereMonth('created_at', $m)
                ->where('type', '!=', 'in')
                ->sum(DB::raw('ABS(quantity)'));

            // --- Kumulatif ---
            if (!$isFuture) {
                $kumulatifOmset += $omset;
                $kumulatifLaba  += $labaBersih;
            }

            $rows[] = [
                'bulan'            => $monthNames[$m],
                'bulan_num'        => $m,
                'tahun'            => $year,
                'is_future'        => $isFuture,
                'is_current'       => ($year == $now->year && $m == $now->month),

                // Penjualan & Kas Kecil
                'jumlah_transaksi' => $isFuture ? null : $jumlahTx,
                'omset'            => $isFuture ? null : (float) $omset,
                'modal_cogs'       => $isFuture ? null : (float) $cogs,
                'laba_kotor'       => $isFuture ? null : (float) $labaKotor,
                'beban_operasional'=> $isFuture ? null : (float) $expenses,
                'laba_bersih'      => $isFuture ? null : (float) $labaBersih,

                // Kas
                'jumlah_closing'   => $isFuture ? null : $jumlahClosing,
                'kas_lebih'        => $isFuture ? null : (float) $totalKasMasuk,
                'kas_kurang'       => $isFuture ? null : (float) $totalKasKurang,
                'selisih_kas'      => $isFuture ? null : (float) $selisihKas,

                // Stok
                'stok_masuk'       => $isFuture ? null : (int) $mutasiMasuk,
                'stok_keluar'      => $isFuture ? null : (int) $mutasiKeluar,

                // Kumulatif (YTD)
                'kumulatif_omset'  => $isFuture ? null : (float) $kumulatifOmset,
                'kumulatif_laba'   => $isFuture ? null : (float) $kumulatifLaba,
            ];
        }

        // Year summary
        $yearSales = Sale::where('status', 'completed')->whereYear('date', $year)->get();
        $yearCogs  = 0;
        foreach ($yearSales as $s) {
            $yearCogs += DB::table('sale_items')->where('sale_id', $s->id)->sum(DB::raw('cost_price * qty'));
        }
        $yearExpenses = (float) \App\Models\PettyCash::whereYear('date', $year)->sum('amount');
        $yearOmset    = (float) $yearSales->sum('total_amount');
        $yearGross    = $yearOmset - $yearCogs;
        $yearNet      = $yearGross - $yearExpenses;

        $summary = [
            'total_transaksi'  => $yearSales->count(),
            'total_omset'      => $yearOmset,
            'total_cogs'       => (float) $yearCogs,
            'total_laba_kotor' => (float) $yearGross,
            'total_beban'      => $yearExpenses,
            'total_laba'       => (float) $yearNet,
            'margin'           => $yearOmset > 0 ? round(($yearNet / $yearOmset) * 100, 1) : 0,
        ];

        // Available years (from first sale to current year + 1)
        $firstYear = Sale::min(DB::raw('YEAR(date)')) ?? date('Y');
        $years = range((int) $firstYear, (int) date('Y'));

        return [
            'year'    => $year,
            'years'   => $years,
            'summary' => $summary,
            'months'  => $rows,
        ];
    }

    public function tahunan(Request $request)
    {
        $year = (int) $request->query('year', date('Y'));
        $data = $this->getTahunanData($year);

        return response()->json([
            'success'  => true,
            'data'     => $data,
        ]);
    }

    public function exportPdfTahunan(Request $request)
    {
        $year = (int) $request->query('year', date('Y'));
        $data = $this->getTahunanData($year);
        
        $monthlyDetails = [];
        for ($m = 1; $m <= 12; $m++) {
            // Only fetch if month is not strictly in the future
            $now = Carbon::now();
            if ($year > $now->year || ($year == $now->year && $m > $now->month)) {
                continue; // Skip future months
            }
            $monthlyDetails[$m] = $this->getBulananData($year, $m);
        }
        $data['monthly_details'] = $monthlyDetails;

        $pdf = Pdf::loadView('pdf.rekap_tahunan', $data);
        return $pdf->download('rekap_tahunan_' . $year . '.pdf');
    }

    private function getBulananData(int $year, int $month)
    {
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        // Bulk load month sales
        $salesList = Sale::where('status', 'completed')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $salesByDate = $salesList->groupBy(function ($s) {
            return Carbon::parse($s->date)->format('Y-m-d');
        });

        $saleIds = $salesList->pluck('id');
        $cogsBySaleId = [];
        if ($saleIds->isNotEmpty()) {
            $cogsBySaleId = DB::table('sale_items')
                ->whereIn('sale_id', $saleIds)
                ->select('sale_id', DB::raw('SUM(cost_price * qty) as total_cogs'))
                ->groupBy('sale_id')
                ->pluck('total_cogs', 'sale_id')
                ->toArray();
        }

        // Bulk load expenses (Petty Cash)
        $expensesByDate = \App\Models\PettyCash::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->select(DB::raw('DATE(date) as dt'), DB::raw('SUM(amount) as total_exp'))
            ->groupBy(DB::raw('DATE(date)'))
            ->pluck('total_exp', 'dt')
            ->toArray();

        // Bulk load cash variance
        $closingsByDate = CashReconciliation::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->select(DB::raw('DATE(date) as dt'), DB::raw('SUM(variance) as total_var'))
            ->groupBy(DB::raw('DATE(date)'))
            ->pluck('total_var', 'dt')
            ->toArray();

        // Bulk load stock movements
        $stockInByDate = StockMovement::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('type', 'in')
            ->select(DB::raw('DATE(created_at) as dt'), DB::raw('SUM(quantity) as total_qty'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total_qty', 'dt')
            ->toArray();

        $stockOutByDate = StockMovement::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('type', '!=', 'in')
            ->select(DB::raw('DATE(created_at) as dt'), DB::raw('SUM(ABS(quantity)) as total_qty'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total_qty', 'dt')
            ->toArray();

        $rows = [];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $dateObj = Carbon::createFromFormat('Y-m-d', $dateStr);

            if ($dateObj->isFuture() && !$dateObj->isToday()) {
                $rows[] = [
                    'tanggal'          => $dateStr,
                    'hari'             => $dateObj->locale('id')->isoFormat('dddd'),
                    'is_future'        => true,
                    'jumlah_transaksi' => null,
                    'omset'            => null,
                    'laba'             => null,
                    'stok_masuk'       => null,
                    'stok_keluar'      => null,
                    'selisih_kas'      => null,
                ];
                continue;
            }

            $daySales = $salesByDate->get($dateStr, collect());
            $omset = $daySales->sum('total_amount');
            $cogs  = 0;
            foreach ($daySales as $sale) {
                $cogs += (float) ($cogsBySaleId[$sale->id] ?? 0);
            }
            $dayExpense = (float) ($expensesByDate[$dateStr] ?? 0);
            $grossProfit = $omset - $cogs;
            $netProfit = $grossProfit - $dayExpense;
            $closing = (float) ($closingsByDate[$dateStr] ?? 0);

            $rows[] = [
                'tanggal'          => $dateStr,
                'hari'             => $dateObj->locale('id')->isoFormat('dddd'),
                'is_future'        => false,
                'jumlah_transaksi' => $daySales->count(),
                'omset'            => (float) $omset,
                'modal_cogs'       => (float) $cogs,
                'beban_operasional'=> (float) $dayExpense,
                'laba_kotor'       => (float) $grossProfit,
                'laba'             => (float) $netProfit,
                'stok_masuk'       => (int) ($stockInByDate[$dateStr] ?? 0),
                'stok_keluar'      => (int) ($stockOutByDate[$dateStr] ?? 0),
                'selisih_kas'      => (float) $closing,
            ];
        }

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return [
            'year'       => $year,
            'month'      => $month,
            'month_name' => $monthNames[$month] ?? '',
            'days'       => $rows,
        ];
    }

    /**
     * GET /apps/rekap/bulanan?year=2026&month=8
     * Returns daily breakdown for a specific month.
     */
    public function bulanan(Request $request)
    {
        $year  = (int) $request->query('year',  date('Y'));
        $month = (int) $request->query('month', date('n'));

        $data = $this->getBulananData($year, $month);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
