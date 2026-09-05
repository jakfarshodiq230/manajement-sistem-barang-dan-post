<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Buku Besar - {{ $account->code }} - {{ $account->name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 10mm 10mm 10mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9px;
            line-height: 1.35;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        
        /* Header Table */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .company-name {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .branch-info {
            font-size: 8.5px;
            color: #64748b;
            line-height: 1.3;
        }
        .doc-title {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            text-align: right;
            color: #0f172a;
            letter-spacing: 0.8px;
        }
        .doc-subtitle {
            font-size: 8.5px;
            text-align: right;
            color: #64748b;
            margin-top: 2px;
        }

        /* Account Info Box */
        .info-card {
            width: 100%;
            margin-bottom: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            background-color: #f8fafc;
            padding: 6px 10px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            vertical-align: top;
            padding: 2px 4px;
            font-size: 9px;
        }
        .label {
            font-weight: 600;
            color: #64748b;
            width: 110px;
        }
        .val {
            color: #0f172a;
            font-weight: 700;
        }
        
        /* 4-KPI Summary Cards */
        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 12px;
        }
        .summary-card {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            text-align: center;
            background-color: #ffffff;
        }
        .summary-title {
            font-size: 7.5px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .summary-value {
            font-size: 10.5px;
            font-weight: 700;
            font-family: 'Courier New', Courier, monospace;
        }
        .val-primary { color: #0f172a; }
        .val-success { color: #16a34a; }
        .val-warning { color: #d97706; }
        .val-info    { color: #2563eb; }

        /* Ledger Table */
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .ledger-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: center;
            letter-spacing: 0.3px;
        }
        .ledger-table td {
            border: 1px solid #e2e8f0;
            padding: 4px 6px;
            font-size: 8.5px;
            vertical-align: top;
            color: #1e293b;
        }
        .row-even {
            background-color: #fafbfc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .font-bold { font-weight: 700; }

        /* Signatures Footer */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            page-break-inside: avoid;
        }
        .sign-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px;
            text-align: center;
            background-color: #ffffff;
        }
        .sign-title {
            font-size: 8px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }
        .sign-space {
            height: 45px;
        }
        .sign-name {
            font-size: 8.5px;
            font-weight: 700;
            color: #0f172a;
            border-top: 1px solid #cbd5e1;
            padding-top: 3px;
        }
        .sign-role {
            font-size: 7.5px;
            color: #64748b;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="company-name">PT DUMAI MANAJEMEN SISTEM</div>
                <div class="branch-info">
                    <strong>Cabang / Unit:</strong> {{ $branchName }}<br>
                    <strong>Sistem:</strong> Akuntansi & Pembukuan Double-Entry Terpadu<br>
                    <strong>Dicetak Oleh:</strong> {{ $userName }} ({{ $userRole }}) • {{ date('d/m/Y H:i:s') }}
                </div>
            </td>
            <td style="width: 45%; vertical-align: top; text-align: right;">
                <div class="doc-title">BUKU BESAR (GENERAL LEDGER)</div>
                <div class="doc-subtitle">
                    Periode: <strong>{{ $startDate }}</strong> s/d <strong>{{ $endDate }}</strong><br>
                    Status: <strong>POSTED (TERCATAT & TERVERIFIKASI)</strong>
                </div>
            </td>
        </tr>
    </table>

    <!-- Account Details Info Card -->
    <div class="info-card">
        <table class="info-table">
            <tr>
                <td class="label">Kode Akun:</td>
                <td class="val font-mono" style="color: #0369a1; font-size: 10px;">{{ $account->code }}</td>
                <td class="label">Tipe / Klasifikasi:</td>
                <td class="val" style="text-transform: uppercase;">{{ $account->type }} ({{ $account->category }})</td>
            </tr>
            <tr>
                <td class="label">Nama Rekening:</td>
                <td class="val">{{ $account->name }}</td>
                <td class="label">Posisi Saldo Normal:</td>
                <td class="val" style="color: {{ $account->normal_balance === 'debit' ? '#16a34a' : '#d97706' }}; text-transform: uppercase;">
                    {{ strtoupper($account->normal_balance) }}
                </td>
            </tr>
        </table>
    </div>

    <!-- 4 KPI Summary Cards -->
    <table class="summary-table">
        <tr>
            <td style="width: 25%;">
                <div class="summary-card">
                    <div class="summary-title">SALDO AWAL PERIODE</div>
                    <div class="summary-value val-primary">Rp {{ number_format($beginningBalance, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="summary-card">
                    <div class="summary-title">TOTAL MUTASI DEBIT</div>
                    <div class="summary-value val-success">Rp {{ number_format($totalDebit, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="summary-card">
                    <div class="summary-title">TOTAL MUTASI KREDIT</div>
                    <div class="summary-value val-warning">Rp {{ number_format($totalCredit, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="summary-card">
                    <div class="summary-title">SALDO AKHIR BERJALAN</div>
                    <div class="summary-value val-info">Rp {{ number_format($endingBalance, 0, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Ledger Mutation Table -->
    <table class="ledger-table">
        <thead>
            <tr>
                <th style="width: 10%;">TANGGAL</th>
                <th style="width: 14%;">NO. JURNAL</th>
                <th style="width: 32%;">KETERANGAN / RINCIAN MUTASI</th>
                <th style="width: 12%;">CABANG</th>
                <th style="width: 10%;">DEBIT (RP)</th>
                <th style="width: 10%;">KREDIT (RP)</th>
                <th style="width: 12%;">SALDO AKHIR (RP)</th>
            </tr>
        </thead>
        <tbody>
            <!-- Beginning Balance Row -->
            <tr style="background-color: #f1f5f9; font-weight: 700;">
                <td class="text-center font-mono">{{ $startDate }}</td>
                <td class="text-center font-mono">-</td>
                <td>SALDO AWAL PERIODE PEMBUKUAN</td>
                <td class="text-center">-</td>
                <td class="text-right font-mono">-</td>
                <td class="text-right font-mono">-</td>
                <td class="text-right font-mono font-bold" style="color: #0369a1;">
                    {{ number_format($beginningBalance, 0, ',', '.') }}
                </td>
            </tr>

            @forelse($transactions as $index => $row)
                <tr class="{{ $index % 2 == 1 ? 'row-even' : '' }}">
                    <td class="text-center font-mono">{{ $row['entry_date'] }}</td>
                    <td class="font-mono font-bold text-center" style="color: #0369a1;">
                        {{ $row['entry_number'] }}
                    </td>
                    <td>
                        <div>{{ $row['notes'] ?: '-' }}</div>
                        @if(!empty($row['reference_type']))
                            <div style="font-size: 7.5px; color: #64748b;">Ref: {{ $row['reference_type'] }}</div>
                        @endif
                    </td>
                    <td class="text-center" style="font-size: 8px; color: #64748b;">
                        {{ $row['branch_name'] }}
                    </td>
                    <td class="text-right font-mono" style="{{ $row['debit'] > 0 ? 'font-weight: 700; color: #16a34a;' : 'color: #94a3b8;' }}">
                        {{ $row['debit'] > 0 ? number_format($row['debit'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-right font-mono" style="{{ $row['credit'] > 0 ? 'font-weight: 700; color: #d97706;' : 'color: #94a3b8;' }}">
                        {{ $row['credit'] > 0 ? number_format($row['credit'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-right font-mono font-bold" style="color: #0f172a;">
                        {{ number_format($row['balance'], 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 14px; color: #64748b;">
                        Tidak ada mutasi transaksi pada periode yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: 800;">
                <td colspan="4" class="text-right" style="padding: 6px; font-size: 8.5px; text-transform: uppercase;">
                    TOTAL MUTASI & SALDO AKHIR:
                </td>
                <td class="text-right font-mono font-bold" style="padding: 6px; font-size: 8.5px; color: #16a34a;">
                    Rp {{ number_format($totalDebit, 0, ',', '.') }}
                </td>
                <td class="text-right font-mono font-bold" style="padding: 6px; font-size: 8.5px; color: #d97706;">
                    Rp {{ number_format($totalCredit, 0, ',', '.') }}
                </td>
                <td class="text-right font-mono font-bold" style="padding: 6px; font-size: 9px; color: #0369a1;">
                    Rp {{ number_format($endingBalance, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Digital Signatures Footer -->
    <table class="footer-table">
        <tr>
            <td style="width: 30%; vertical-align: top;">
                <div class="sign-box">
                    <div class="sign-title">Disiapkan / Akuntan</div>
                    <div style="padding: 3px 0;">
                        @if(!empty($signerQrCode))
                            <img src="data:image/svg+xml;base64,{{ $signerQrCode }}" style="width: 55px; height: 55px;">
                        @else
                            <div class="sign-space"></div>
                        @endif
                    </div>
                    <div class="sign-name">{{ $userName }}</div>
                    <div class="sign-role">{{ $userRole }}</div>
                </div>
            </td>
            <td style="width: 40%; vertical-align: middle; text-align: center; padding: 0 10px;">
                <div style="border: 1px dashed #cbd5e1; border-radius: 4px; padding: 8px; background-color: #f8fafc;">
                    <div style="font-size: 7.5px; font-weight: 700; color: #0f172a; text-transform: uppercase; margin-bottom: 2px;">
                        VERIFIKASI BUKU BESAR AKUNTANSI
                    </div>
                    <div style="font-size: 7px; color: #64748b; line-height: 1.3;">
                        Buku besar ini memuat seluruh mutasi debit dan kredit akun {{ $account->code }} - {{ $account->name }} secara kronologis sesuai standar akuntansi keuangan Indonesia.
                    </div>
                </div>
            </td>
            <td style="width: 30%; vertical-align: top;">
                <div class="sign-box">
                    <div class="sign-title">Diperiksa / Direksi & Owner</div>
                    <div style="padding: 3px 0;">
                        @if(!empty($documentQrCode))
                            <img src="data:image/svg+xml;base64,{{ $documentQrCode }}" style="width: 55px; height: 55px;">
                        @else
                            <div class="sign-space"></div>
                        @endif
                    </div>
                    <div class="sign-name">{{ $ownerName ?? 'Kepala Keuangan & Owner' }}</div>
                    <div class="sign-role">Otorisasi Pembukuan & Audit</div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
