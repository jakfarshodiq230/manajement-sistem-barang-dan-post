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
            $laba       = $omset - $cogs;
            $jumlahTx   = $sales->count();

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
                $kumulatifLaba  += $laba;
            }

            $rows[] = [
                'bulan'            => $monthNames[$m],
                'bulan_num'        => $m,
                'tahun'            => $year,
                'is_future'        => $isFuture,
                'is_current'       => ($year == $now->year && $m == $now->month),

                // Penjualan
                'jumlah_transaksi' => $isFuture ? null : $jumlahTx,
                'omset'            => $isFuture ? null : (float) $omset,
                'modal_cogs'       => $isFuture ? null : (float) $cogs,
                'laba_bersih'      => $isFuture ? null : (float) $laba,

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
        $summary = [
            'total_transaksi'  => $sales ? 0 : 0, // calculated below
            'total_omset'      => $kumulatifOmset,
            'total_laba'       => $kumulatifLaba,
            'margin'           => $kumulatifOmset > 0 ? round(($kumulatifLaba / $kumulatifOmset) * 100, 1) : 0,
        ];

        // Recalculate proper year totals
        $yearSales = Sale::where('status', 'completed')->whereYear('date', $year)->get();
        $yearCogs  = 0;
        foreach ($yearSales as $s) {
            $yearCogs += DB::table('sale_items')->where('sale_id', $s->id)->sum(DB::raw('cost_price * qty'));
        }
        $summary['total_transaksi'] = $yearSales->count();
        $summary['total_omset']     = (float) $yearSales->sum('total_amount');
        $summary['total_laba']      = (float) ($yearSales->sum('total_amount') - $yearCogs);
        $summary['margin']          = $summary['total_omset'] > 0
            ? round(($summary['total_laba'] / $summary['total_omset']) * 100, 1)
            : 0;

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

            $sales = Sale::where('status', 'completed')->whereDate('date', $dateStr)->get();
            $omset = $sales->sum('total_amount');
            $cogs  = 0;
            foreach ($sales as $sale) {
                $cogs += DB::table('sale_items')->where('sale_id', $sale->id)->sum(DB::raw('cost_price * qty'));
            }

            $closing = CashReconciliation::whereDate('date', $dateStr)->sum('variance');

            $rows[] = [
                'tanggal'          => $dateStr,
                'hari'             => $dateObj->locale('id')->isoFormat('dddd'),
                'is_future'        => false,
                'jumlah_transaksi' => $sales->count(),
                'omset'            => (float) $omset,
                'laba'             => (float) ($omset - $cogs),
                'stok_masuk'       => (int) StockMovement::whereDate('created_at', $dateStr)->where('type', 'in')->sum('quantity'),
                'stok_keluar'      => (int) StockMovement::whereDate('created_at', $dateStr)->where('type', '!=', 'in')->sum(DB::raw('ABS(quantity)')),
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
