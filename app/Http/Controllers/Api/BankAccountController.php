<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BankAccount;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BankAccountController extends Controller
{
    /**
     * Display a listing of the resource with Year and Month analytics.
     */
    public function index(Request $request)
    {
        $year = (int) $request->query('year', date('Y'));
        $month = $request->has('month') && $request->month !== '' && $request->month !== null ? (int) $request->month : (int) date('n');

        $query = BankAccount::with('branch:id,name');

        if ($request->has('branch_id') && $request->branch_id !== null && $request->branch_id !== '') {
            $query->where(function ($q) use ($request) {
                $q->where('branch_id', $request->branch_id)
                  ->orWhereNull('branch_id'); // Global accounts available to all branches
            });
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bank_name', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%");
            });
        }

        $accounts = $query->orderBy('is_default', 'desc')
            ->orderBy('is_active', 'desc')
            ->orderBy('bank_name', 'asc')
            ->get();

        // Calculate Monthly & Yearly Stats for each bank account
        foreach ($accounts as $account) {
            // Selected Month Revenue
            $account->month_received = (float) DB::table('sales')
                ->where('bank_account_id', $account->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');

            $account->month_tx_count = DB::table('sales')
                ->where('bank_account_id', $account->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->where('status', '!=', 'cancelled')
                ->count();

            // Selected Year Revenue
            $account->year_received = (float) DB::table('sales')
                ->where('bank_account_id', $account->id)
                ->whereYear('date', $year)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');

            // 12-Month Trend Array for this bank in selected year
            $monthly12 = [];
            for ($m = 1; $m <= 12; $m++) {
                $monthly12[$m] = (float) DB::table('sales')
                    ->where('bank_account_id', $account->id)
                    ->whereYear('date', $year)
                    ->whereMonth('date', $m)
                    ->where('status', '!=', 'cancelled')
                    ->sum('total_amount');
            }
            $account->monthly_trend = array_values($monthly12);
        }

        // Available Years from Sales Data
        $salesYears = DB::table('sales')
            ->selectRaw('DISTINCT YEAR(date) as yr')
            ->whereNotNull('date')
            ->pluck('yr')
            ->toArray();

        $currentYear = (int) date('Y');
        $yearsList = array_unique(array_merge([$currentYear - 1, $currentYear, $currentYear + 1], array_map('intval', $salesYears)));
        rsort($yearsList);

        // Overall Monthly & Yearly Summary KPI
        $selectedMonthReceived = (float) DB::table('sales')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNotNull('bank_account_id')
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $selectedMonthTxCount = DB::table('sales')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereNotNull('bank_account_id')
            ->where('status', '!=', 'cancelled')
            ->count();

        $selectedYearReceived = (float) DB::table('sales')
            ->whereYear('date', $year)
            ->whereNotNull('bank_account_id')
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $totalBalance = (float) $accounts->sum('current_balance');
        $totalInitial = (float) $accounts->sum('initial_balance');

        $summary = [
            'total_accounts'          => $accounts->count(),
            'active_accounts'         => $accounts->where('is_active', true)->count(),
            'total_balance'           => $totalBalance,
            'total_initial_balance'   => $totalInitial,
            'selected_year'           => $year,
            'selected_month'          => $month,
            'selected_month_received' => $selectedMonthReceived,
            'selected_month_tx_count' => $selectedMonthTxCount,
            'selected_year_received'  => $selectedYearReceived,
            'available_years'         => $yearsList,
        ];

        return response()->json([
            'data'    => $accounts,
            'summary' => $summary,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'bank_name'       => 'required|string|max:100',
            'account_number'  => 'nullable|string|max:100',
            'account_name'    => 'nullable|string|max:150',
            'type'            => 'required|in:bank_transfer,qris,edc_debit,edc_credit,cash',
            'branch_id'       => 'nullable|exists:branches,id',
            'initial_balance' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric|min:0',
            'qris_image'      => 'nullable',
            'is_active'       => 'nullable|boolean',
            'is_default'      => 'nullable|boolean',
            'color'           => 'nullable|string|max:20',
            'notes'           => 'nullable|string',
        ]);

        $qrisPath = null;
        if ($request->hasFile('qris_image')) {
            $file = $request->file('qris_image');
            if ($file->isValid()) {
                $qrisPath = '/storage/' . $file->store('bank_qris', 'public');
            }
        }

        $initialBalance = $request->has('initial_balance') ? (float) $request->initial_balance : 0;
        $currentBalance = $request->has('current_balance') ? (float) $request->current_balance : $initialBalance;

        // If this is set as default, reset other accounts
        if ($request->is_default) {
            BankAccount::where('is_default', true)->update(['is_default' => false]);
        }

        $bankAccount = BankAccount::create([
            'bank_name'       => $request->bank_name,
            'account_number'  => $request->account_number,
            'account_name'    => $request->account_name,
            'type'            => $request->type,
            'branch_id'       => $request->branch_id ?: null,
            'initial_balance' => $initialBalance,
            'current_balance' => $currentBalance,
            'qris_image'      => $qrisPath,
            'is_active'       => $request->has('is_active') ? filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN) : true,
            'is_default'      => $request->has('is_default') ? filter_var($request->is_default, FILTER_VALIDATE_BOOLEAN) : false,
            'color'           => $request->color ?: '#0066AE',
            'notes'           => $request->notes,
        ]);

        return response()->json([
            'message' => 'Rekening Bank berhasil ditambahkan.',
            'data'    => $bankAccount->load('branch:id,name'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $bankAccount = BankAccount::with('branch:id,name')->findOrFail($id);

        // Recent sales using this bank account with branch and cashier info
        $recentSales = DB::table('sales')
            ->leftJoin('branches', 'sales.branch_id', '=', 'branches.id')
            ->leftJoin('users', 'sales.user_id', '=', 'users.id')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('sales.bank_account_id', $bankAccount->id)
            ->where('sales.status', '!=', 'cancelled')
            ->select(
                'sales.id',
                'sales.invoice_number',
                'sales.date',
                'sales.total_amount',
                'sales.payment_method',
                'sales.status',
                'sales.created_at',
                'branches.name as branch_name',
                'users.name as cashier_name',
                'customers.name as customer_name'
            )
            ->orderBy('sales.created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'data'         => $bankAccount,
            'recent_sales' => $recentSales,
        ]);
    }

    /**
     * Get official Bank Statement / Mutasi Buku Bank data
     */
    public function statement(Request $request, $id)
    {
        $bankAccount = BankAccount::with('branch:id,name')->findOrFail($id);
        $data = $this->getStatementData($bankAccount, $request);

        return response()->json($data);
    }

    /**
     * Download / Export PDF Rekening Koran (Bank Statement)
     */
    public function exportPdf(Request $request, $id)
    {
        $bankAccount = BankAccount::with('branch:id,name')->findOrFail($id);
        $statementData = $this->getStatementData($bankAccount, $request);

        $branch = $bankAccount->branch ?: \App\Models\Branch::with('owner')->first();
        if ($branch && !$branch->relationLoaded('owner')) {
            $branch->load('owner');
        }

        $user = auth()->user();
        $userName = $user ? $user->name : 'Finance Officer';
        $userRole = ($user && $user->role) ? $user->role->name : 'Petugas Keuangan / Kasir';
        $userNip = $user ? ($user->nip ?? ($user->employee->nik ?? 'EMP-' . str_pad($user->id, 3, '0', STR_PAD_LEFT))) : 'EMP-001';
        $ownerName = $branch->owner->name ?? ($branch->name ?? 'Pimpinan Toko');
        $branchName = $branch->name ?? 'Cabang Utama';

        // 1. QR Code Bukti Data / Keabsahan Dokumen (Terbaca saat di-scan)
        $docVerifyPayload = "VERIFIKASI KEABSAHAN DOKUMEN MS.POS\n"
            . "====================================\n"
            . "Dokumen   : Rekening Koran / Buku Mutasi Bank\n"
            . "Bank      : " . $bankAccount->bank_name . " (" . ($bankAccount->account_number ?: '-') . ")\n"
            . "Atas Nama : " . ($bankAccount->account_name ?: '-') . "\n"
            . "Cabang    : " . $branchName . "\n"
            . "Periode   : " . date('d/m/Y', strtotime($statementData['period']['start_date'])) . " s/d " . date('d/m/Y', strtotime($statementData['period']['end_date'])) . "\n"
            . "Saldo Awal: Rp " . number_format($statementData['summary']['opening_balance'], 0, ',', '.') . "\n"
            . "Total In  : +Rp " . number_format($statementData['summary']['total_credit'], 0, ',', '.') . "\n"
            . "Total Out : -Rp " . number_format($statementData['summary']['total_debit'], 0, ',', '.') . "\n"
            . "Saldo Akhir: Rp " . number_format($statementData['summary']['closing_balance'], 0, ',', '.') . "\n"
            . "Status    : DOKUMEN RESMI SAH & TERVALIDASI SISTEM\n"
            . "Tgl Cetak : " . date('d/m/Y H:i:s');
        $documentQrCode = base64_encode(QrCode::format('svg')->size(75)->generate($docVerifyPayload));

        // 2. QR Code Tanda Tangan Digital Petugas / Kasir (Identitas Penandatangan Lengkap)
        $signerPayload = "TANDA TANGAN DIGITAL RESMI (DIGITAL SIGNATURE)\n"
            . "===============================================\n"
            . "Penandatangan : " . $userName . "\n"
            . "NIP / ID      : " . $userNip . "\n"
            . "Jabatan       : " . $userRole . "\n"
            . "Unit / Cabang : " . $branchName . "\n"
            . "Waktu TTD     : " . date('d/m/Y H:i:s') . "\n"
            . "Keperluan     : Pengesahan Rekening Koran Bank " . $bankAccount->bank_name . "\n"
            . "Status TTD    : TERTANDA DIGITAL SAH (VERIFIED)";
        $signerQrCode = base64_encode(QrCode::format('svg')->size(75)->generate($signerPayload));

        $pdfData = array_merge($statementData, [
            'bankAccount'    => $bankAccount,
            'branch'         => $branch,
            'documentQrCode' => $documentQrCode,
            'signerQrCode'   => $signerQrCode,
            'userName'       => $userName,
            'userRole'       => $userRole,
            'ownerName'      => $ownerName,
        ]);

        $pdf = Pdf::loadView('pdf.bank_statement', $pdfData);
        $pdf->setPaper('a4', 'portrait');

        $cleanBankName = preg_replace('/[^A-Za-z0-9_-]/', '_', $bankAccount->bank_name);
        $cleanAccNum = preg_replace('/[^A-Za-z0-9_-]/', '', $bankAccount->account_number ?: 'All');
        $fileName = 'Rekening_Koran_' . $cleanBankName . '_' . $cleanAccNum . '_' . date('Ymd') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Helper to compute all mutations, opening balance, and running balance for a bank account.
     */
    public function getStatementData(BankAccount $bankAccount, Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $year = $request->query('year');
        $month = $request->query('month');
        $search = $request->query('search');
        $typeFilter = $request->query('type_filter'); // 'all', 'credit', 'debit'

        if ($year && $month && !$startDate && !$endDate) {
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate = date('Y-m-t', strtotime($startDate));
        }

        // 1. Sales (POS) - Credit (+)
        $salesQuery = \App\Models\Sale::with(['customer:id,name', 'user:id,name', 'branch:id,name'])
            ->where('bank_account_id', $bankAccount->id)
            ->where('status', '!=', 'cancelled');

        $sales = $salesQuery->get()->map(function ($s) {
            $amt = $s->payment_method === 'tempo' ? (float)($s->paid_amount ?: 0) : (float)$s->total_amount;
            return [
                'id' => 'sale_' . $s->id,
                'raw_date' => $s->created_at ? $s->created_at->format('Y-m-d H:i:s') : ($s->date . ' 00:00:00'),
                'date' => $s->date ? date('Y-m-d', strtotime($s->date)) : ($s->created_at ? $s->created_at->format('Y-m-d') : date('Y-m-d')),
                'time' => $s->created_at ? $s->created_at->format('H:i') : '-',
                'reference_no' => $s->invoice_number,
                'category' => 'Penjualan Kasir POS',
                'description' => 'Penjualan Bon #' . $s->invoice_number . ($s->customer ? ' - ' . $s->customer->name : ' (Pelanggan Umum)') . ($s->user ? ' [Kasir: ' . $s->user->name . ']' : ''),
                'type' => 'credit',
                'debit' => 0,
                'credit' => $amt,
                'amount' => $amt,
                'channel' => strtoupper($s->payment_method ?: 'NON-TUNAI'),
            ];
        });

        // 2. Receivable Payments - Credit (+)
        $receivablePayments = \App\Models\ReceivablePayment::with(['receivable.customer:id,name', 'receivable.sale:id,invoice_number', 'user:id,name'])
            ->where('bank_account_id', $bankAccount->id)
            ->get()->map(function ($rp) {
                $amt = (float)$rp->amount;
                $inv = $rp->receivable && $rp->receivable->sale ? $rp->receivable->sale->invoice_number : '-';
                $cust = $rp->receivable && $rp->receivable->customer ? $rp->receivable->customer->name : '-';
                return [
                    'id' => 'rec_' . $rp->id,
                    'raw_date' => $rp->created_at ? $rp->created_at->format('Y-m-d H:i:s') : ($rp->payment_date . ' 00:00:00'),
                    'date' => $rp->payment_date ?: ($rp->created_at ? $rp->created_at->format('Y-m-d') : date('Y-m-d')),
                    'time' => $rp->created_at ? $rp->created_at->format('H:i') : '-',
                    'reference_no' => 'PAY-AR-' . $rp->id,
                    'category' => 'Pelunasan Piutang',
                    'description' => 'Pelunasan Piutang Bon #' . $inv . ' - Pelanggan: ' . $cust . ($rp->user ? ' [Kasir: ' . $rp->user->name . ']' : ''),
                    'type' => 'credit',
                    'debit' => 0,
                    'credit' => $amt,
                    'amount' => $amt,
                    'channel' => strtoupper($rp->payment_method ?: 'TRANSFER'),
                ];
            });

        // 3. Branch Capital Injection (Penyaluran Modal ke Cabang via Bank) - Debit (-)
        $injections = \App\Models\BranchCapital::with('branch:id,name')
            ->where('type', 'injection')
            ->where('status', 'approved')
            ->where(function($q) use ($bankAccount) {
                $q->where('bank_account_id', $bankAccount->id)
                  ->orWhere('account_number', $bankAccount->account_number)
                  ->orWhere('bank_name', $bankAccount->bank_name);
            })
            ->get()->map(function ($bc) {
                $amt = (float)$bc->amount;
                $bName = $bc->branch ? $bc->branch->name : 'Cabang';
                return [
                    'id' => 'cap_inj_' . $bc->id,
                    'raw_date' => $bc->created_at ? $bc->created_at->format('Y-m-d H:i:s') : ($bc->date . ' 00:00:00'),
                    'date' => $bc->date ? date('Y-m-d', strtotime($bc->date)) : ($bc->created_at ? $bc->created_at->format('Y-m-d') : date('Y-m-d')),
                    'time' => $bc->created_at ? $bc->created_at->format('H:i') : '-',
                    'reference_no' => $bc->reference_no ?: ('INJ-' . $bc->id),
                    'category' => 'Penyaluran Modal Cabang (Injeksi)',
                    'description' => 'Penyaluran Modal ke Cabang ' . $bName . ' - ' . ($bc->category ?: 'Injeksi Modal Usaha') . ($bc->notes ? ' (' . $bc->notes . ')' : ''),
                    'type' => 'debit',
                    'debit' => $amt,
                    'credit' => 0,
                    'amount' => $amt,
                    'channel' => strtoupper($bc->payment_method ?: 'TRANSFER'),
                ];
            });

        // 4. Payable Payments - Debit (-)
        $payablePayments = \App\Models\PayablePayment::with(['payableStatement.supplier:id,name', 'payable.purchaseOrder.supplier:id,name', 'user:id,name'])
            ->where('bank_account_id', $bankAccount->id)
            ->get()->map(function ($pp) {
                $amt = (float)$pp->amount;
                $sup = '-';
                if ($pp->payableStatement && $pp->payableStatement->supplier) {
                    $sup = $pp->payableStatement->supplier->name;
                } elseif ($pp->payable && $pp->payable->purchaseOrder && $pp->payable->purchaseOrder->supplier) {
                    $sup = $pp->payable->purchaseOrder->supplier->name;
                } elseif ($pp->payableStatement) {
                    $sup = 'Tagihan ' . $pp->payableStatement->statement_number;
                } else {
                    $sup = 'Supplier Tagihan';
                }
                return [
                    'id' => 'pay_' . $pp->id,
                    'raw_date' => $pp->created_at ? $pp->created_at->format('Y-m-d H:i:s') : ($pp->payment_date . ' 00:00:00'),
                    'date' => $pp->payment_date ?: ($pp->created_at ? $pp->created_at->format('Y-m-d') : date('Y-m-d')),
                    'time' => $pp->created_at ? $pp->created_at->format('H:i') : '-',
                    'reference_no' => $pp->payment_number ?: ('PAY-AP-' . $pp->id),
                    'category' => 'Pembayaran Hutang Supplier',
                    'description' => 'Pembayaran Hutang Pengadaan: ' . $sup . ($pp->reference_number ? ' (Ref: ' . $pp->reference_number . ')' : ''),
                    'type' => 'debit',
                    'debit' => $amt,
                    'credit' => 0,
                    'amount' => $amt,
                    'channel' => strtoupper($pp->payment_method ?: 'TRANSFER'),
                ];
            });

        // 5. Petty Cash - Debit (-)
        $pettyCash = \App\Models\PettyCash::where('payment_method', 'bank_transfer')
            ->where('bank_account_id', $bankAccount->id)
            ->get()->map(function ($pc) {
                $amt = (float)$pc->amount;
                return [
                    'id' => 'pc_' . $pc->id,
                    'raw_date' => $pc->created_at ? $pc->created_at->format('Y-m-d H:i:s') : ($pc->date . ' 00:00:00'),
                    'date' => $pc->date ? date('Y-m-d', strtotime($pc->date)) : ($pc->created_at ? $pc->created_at->format('Y-m-d') : date('Y-m-d')),
                    'time' => $pc->created_at ? $pc->created_at->format('H:i') : '-',
                    'reference_no' => 'EXP-' . $pc->id,
                    'category' => 'Kas Kecil (Petty Cash)',
                    'description' => 'Pengeluaran Operasional - ' . $pc->category . ': ' . $pc->description,
                    'type' => 'debit',
                    'debit' => $amt,
                    'credit' => 0,
                    'amount' => $amt,
                    'channel' => 'TRANSFER',
                ];
            });

        // 6. Branch Capital Returns (Setoran Pengembalian Modal / ROI dari Cabang via Bank) - Credit (+)
        $capitalReturns = \App\Models\BranchCapital::with('branch:id,name')
            ->where('type', 'return')
            ->where('status', 'approved')
            ->where(function($q) use ($bankAccount) {
                $q->where('bank_account_id', $bankAccount->id)
                  ->orWhere('account_number', $bankAccount->account_number)
                  ->orWhere('bank_name', $bankAccount->bank_name);
            })
            ->get()->map(function ($bc) {
                $amt = (float)$bc->amount;
                $bName = $bc->branch ? $bc->branch->name : 'Cabang';
                return [
                    'id' => 'cap_ret_' . $bc->id,
                    'raw_date' => $bc->created_at ? $bc->created_at->format('Y-m-d H:i:s') : ($bc->date . ' 00:00:00'),
                    'date' => $bc->date ? date('Y-m-d', strtotime($bc->date)) : ($bc->created_at ? $bc->created_at->format('Y-m-d') : date('Y-m-d')),
                    'time' => $bc->created_at ? $bc->created_at->format('H:i') : '-',
                    'reference_no' => $bc->reference_no ?: ('RET-CAP-' . $bc->id),
                    'category' => 'Pengembalian Modal / ROI Masuk',
                    'description' => 'Setoran Pengembalian Modal / ROI dari Cabang ' . $bName . ' - ' . ($bc->category ?: 'Setoran Modal') . ($bc->notes ? ' (' . $bc->notes . ')' : ''),
                    'type' => 'credit',
                    'debit' => 0,
                    'credit' => $amt,
                    'amount' => $amt,
                    'channel' => strtoupper($bc->payment_method ?: 'TRANSFER'),
                ];
            });

        // 7. Sale Returns Refund - Debit (-)
        $saleReturns = \App\Models\ReturnTransaction::where('reference_type', 'sale')
            ->whereIn('return_type', ['pengembalian_dana', 'pengembalian_uang'])
            ->where('status', 'completed')
            ->whereHas('sale', function($s) use ($bankAccount) {
                $s->where('bank_account_id', $bankAccount->id);
            })
            ->get()->map(function ($rt) {
                $amt = (float)$rt->total_amount;
                return [
                    'id' => 'ret_sale_' . $rt->id,
                    'raw_date' => $rt->created_at ? $rt->created_at->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
                    'date' => $rt->created_at ? $rt->created_at->format('Y-m-d') : date('Y-m-d'),
                    'time' => $rt->created_at ? $rt->created_at->format('H:i') : '-',
                    'reference_no' => $rt->return_number ?: ('RET-' . $rt->id),
                    'category' => 'Retur Penjualan (Refund)',
                    'description' => 'Pengembalian Dana Retur Penjualan #' . $rt->return_number . ($rt->notes ? ' (' . $rt->notes . ')' : ''),
                    'type' => 'debit',
                    'debit' => $amt,
                    'credit' => 0,
                    'amount' => $amt,
                    'channel' => 'TRANSFER',
                ];
            });

        // Merge all transactions
        $allTransactions = collect([])
            ->concat($sales)
            ->concat($receivablePayments)
            ->concat($injections)
            ->concat($payablePayments)
            ->concat($pettyCash)
            ->concat($capitalReturns)
            ->concat($saleReturns)
            ->sortBy('raw_date')
            ->values();

        // Calculate opening balance before $startDate
        $initialBalance = (float) $bankAccount->initial_balance;
        $openingBalance = $initialBalance;

        if ($startDate) {
            $priorTransactions = $allTransactions->filter(function ($t) use ($startDate) {
                return $t['date'] < $startDate;
            });

            foreach ($priorTransactions as $pt) {
                if ($pt['type'] === 'credit') {
                    $openingBalance += $pt['amount'];
                } else {
                    $openingBalance -= $pt['amount'];
                }
            }
        }

        // Filter transactions within period
        $filteredTransactions = $allTransactions;
        if ($startDate) {
            $filteredTransactions = $filteredTransactions->filter(function ($t) use ($startDate) {
                return $t['date'] >= $startDate;
            });
        }
        if ($endDate) {
            $filteredTransactions = $filteredTransactions->filter(function ($t) use ($endDate) {
                return $t['date'] <= $endDate;
            });
        }

        if ($typeFilter && $typeFilter !== 'all') {
            $filteredTransactions = $filteredTransactions->filter(function ($t) use ($typeFilter) {
                return $t['type'] === $typeFilter;
            });
        }

        if ($search) {
            $searchLower = strtolower($search);
            $filteredTransactions = $filteredTransactions->filter(function ($t) use ($searchLower) {
                return str_contains(strtolower($t['reference_no']), $searchLower)
                    || str_contains(strtolower($t['description']), $searchLower)
                    || str_contains(strtolower($t['category']), $searchLower);
            });
        }

        // Calculate running balance and totals
        $runningBalance = $openingBalance;
        $totalCredit = 0;
        $totalDebit = 0;

        $mutations = $filteredTransactions->map(function ($tx) use (&$runningBalance, &$totalCredit, &$totalDebit) {
            if ($tx['type'] === 'credit') {
                $runningBalance += $tx['amount'];
                $totalCredit += $tx['amount'];
            } else {
                $runningBalance -= $tx['amount'];
                $totalDebit += $tx['amount'];
            }
            $tx['running_balance'] = $runningBalance;
            return $tx;
        })->values();

        $closingBalance = $runningBalance;

        return [
            'bank_account'    => $bankAccount->load('branch:id,name'),
            'period'          => [
                'start_date' => $startDate ?: ($mutations->first()['date'] ?? date('Y-m-01')),
                'end_date'   => $endDate ?: ($mutations->last()['date'] ?? date('Y-m-d')),
                'year'       => $year,
                'month'      => $month,
            ],
            'summary'         => [
                'initial_balance' => $initialBalance,
                'opening_balance' => $openingBalance,
                'total_credit'    => $totalCredit,
                'total_debit'     => $totalDebit,
                'closing_balance' => $closingBalance,
                'current_balance' => (float) $bankAccount->current_balance,
                'mutation_count'  => $mutations->count(),
            ],
            'mutations'       => $mutations,
        ];
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $bankAccount = BankAccount::findOrFail($id);

        $request->validate([
            'bank_name'       => 'required|string|max:100',
            'account_number'  => 'nullable|string|max:100',
            'account_name'    => 'nullable|string|max:150',
            'type'            => 'required|in:bank_transfer,qris,edc_debit,edc_credit,cash',
            'branch_id'       => 'nullable|exists:branches,id',
            'initial_balance' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric',
            'qris_image'      => 'nullable',
            'is_active'       => 'nullable|boolean',
            'is_default'      => 'nullable|boolean',
            'color'           => 'nullable|string|max:20',
            'notes'           => 'nullable|string',
        ]);

        $qrisPath = $bankAccount->qris_image;
        if ($request->hasFile('qris_image')) {
            $file = $request->file('qris_image');
            if ($file->isValid()) {
                $qrisPath = '/storage/' . $file->store('bank_qris', 'public');
            }
        }

        if ($request->is_default && !$bankAccount->is_default) {
            BankAccount::where('id', '!=', $bankAccount->id)->where('is_default', true)->update(['is_default' => false]);
        }

        $bankAccount->update([
            'bank_name'       => $request->bank_name,
            'account_number'  => $request->account_number,
            'account_name'    => $request->account_name,
            'type'            => $request->type,
            'branch_id'       => $request->branch_id ?: null,
            'initial_balance' => $request->has('initial_balance') ? (float) $request->initial_balance : $bankAccount->initial_balance,
            'current_balance' => $request->has('current_balance') ? (float) $request->current_balance : $bankAccount->current_balance,
            'qris_image'      => $qrisPath,
            'is_active'       => $request->has('is_active') ? filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN) : $bankAccount->is_active,
            'is_default'      => $request->has('is_default') ? filter_var($request->is_default, FILTER_VALIDATE_BOOLEAN) : $bankAccount->is_default,
            'color'           => $request->color ?: $bankAccount->color,
            'notes'           => $request->notes,
        ]);

        return response()->json([
            'message' => 'Rekening Bank berhasil diperbarui.',
            'data'    => $bankAccount->load('branch:id,name'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $bankAccount = BankAccount::findOrFail($id);

        // Check if there are sales transactions
        $salesCount = DB::table('sales')->where('bank_account_id', $bankAccount->id)->count();
        if ($salesCount > 0) {
            // Soft deactivate instead of hard delete
            $bankAccount->update(['is_active' => false]);
            return response()->json([
                'message' => 'Rekening bank memiliki riwayat ' . $salesCount . ' transaksi dan telah dinonaktifkan.',
            ]);
        }

        $bankAccount->delete();

        return response()->json([
            'message' => 'Rekening bank berhasil dihapus.',
        ]);
    }
}
