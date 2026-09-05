<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan & Neraca Saldo - {{ $startDate }} s/d {{ $endDate }}</title>
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
            background-color: #f8fafc;
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
        .val-danger  { color: #dc2626; }
        .val-info    { color: #2563eb; }

        /* Report Table */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .report-table th {
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
        .report-table td {
            border: 1px solid #e2e8f0;
            padding: 4px 6px;
            font-size: 8.5px;
            vertical-align: middle;
            color: #1e293b;
        }
        .row-even {
            background-color: #fafbfc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .font-bold { font-weight: 700; }

        .section-header {
            background-color: #e2e8f0;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 8.5px;
            color: #0f172a;
            padding: 5px 8px;
        }

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
                <div class="doc-title">LAPORAN KEUANGAN & NERACA SALDO</div>
                <div class="doc-subtitle">
                    Periode: <strong>{{ $startDate }}</strong> s/d <strong>{{ $endDate }}</strong><br>
                    Status: <strong>FINAL & TERVERIFIKASI</strong>
                </div>
            </td>
        </tr>
    </table>

    <!-- 4 KPI Summary Cards -->
    <table class="summary-table">
        <tr>
            <td style="width: 25%;">
                <div class="summary-card">
                    <div class="summary-title">TOTAL AKTIVA (ASET)</div>
                    <div class="summary-value val-info">Rp {{ number_format($totalAssets, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="summary-card">
                    <div class="summary-title">TOTAL KEWAJIBAN (HUTANG)</div>
                    <div class="summary-value val-danger">Rp {{ number_format($totalLiabilities, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="summary-card">
                    <div class="summary-title">TOTAL EKUITAS (MODAL)</div>
                    <div class="summary-value val-warning">Rp {{ number_format($totalEquity, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="summary-card">
                    <div class="summary-title">LABA / RUGI BERSIH PERIODE</div>
                    <div class="summary-value {{ $netIncome >= 0 ? 'val-success' : 'val-danger' }}">
                        Rp {{ number_format($netIncome, 0, ',', '.') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Trial Balance / Neraca Saldo Table -->
    <div style="font-size: 10px; font-weight: 800; color: #0f172a; text-transform: uppercase; margin-bottom: 6px;">
        1. NERACA SALDO (TRIAL BALANCE)
    </div>
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 12%;">KODE AKUN</th>
                <th style="width: 38%;">NAMA REKENING AKUN</th>
                <th style="width: 16%;">TIPE / KLASIFIKASI</th>
                <th style="width: 17%;">DEBIT (RP)</th>
                <th style="width: 17%;">KREDIT (RP)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $tbDebit = 0;
                $tbCredit = 0;
            @endphp
            @foreach($trialBalance as $index => $row)
                @php
                    $tbDebit += $row['debit'];
                    $tbCredit += $row['credit'];
                @endphp
                <tr class="{{ $index % 2 == 1 ? 'row-even' : '' }}">
                    <td class="font-mono font-bold text-center" style="color: #0369a1;">{{ $row['code'] }}</td>
                    <td style="font-weight: 600;">{{ $row['name'] }}</td>
                    <td class="text-center" style="text-transform: uppercase; font-size: 7.5px; color: #64748b;">
                        {{ $row['type'] }}
                    </td>
                    <td class="text-right font-mono" style="{{ $row['debit'] > 0 ? 'font-weight: 700;' : 'color: #94a3b8;' }}">
                        {{ $row['debit'] > 0 ? number_format($row['debit'], 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-right font-mono" style="{{ $row['credit'] > 0 ? 'font-weight: 700;' : 'color: #94a3b8;' }}">
                        {{ $row['credit'] > 0 ? number_format($row['credit'], 0, ',', '.') : '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: 800;">
                <td colspan="3" class="text-right" style="padding: 6px; font-size: 8.5px; text-transform: uppercase;">
                    TOTAL NERACA SALDO:
                </td>
                <td class="text-right font-mono font-bold" style="padding: 6px; font-size: 8.5px; color: #16a34a;">
                    Rp {{ number_format($tbDebit, 0, ',', '.') }}
                </td>
                <td class="text-right font-mono font-bold" style="padding: 6px; font-size: 8.5px; color: #2563eb;">
                    Rp {{ number_format($tbCredit, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Digital Signatures Footer -->
    <table class="footer-table">
        <tr>
            <td style="width: 30%; vertical-align: top;">
                <div class="sign-box">
                    <div class="sign-title">Disiapkan / Akuntan Perusahaan</div>
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
                        PENGESAHAN LAPORAN KEUANGAN
                    </div>
                    <div style="font-size: 7px; color: #64748b; line-height: 1.3;">
                        Laporan neraca dan laba rugi ini telah disesuaikan dan mencerminkan seluruh mutasi operasional serta transaksi cabang secara transparan dan akuntabel.
                    </div>
                </div>
            </td>
            <td style="width: 30%; vertical-align: top;">
                <div class="sign-box">
                    <div class="sign-title">Disahkan / Direktur Utama & Owner</div>
                    <div style="padding: 3px 0;">
                        @if(!empty($documentQrCode))
                            <img src="data:image/svg+xml;base64,{{ $documentQrCode }}" style="width: 55px; height: 55px;">
                        @else
                            <div class="sign-space"></div>
                        @endif
                    </div>
                    <div class="sign-name">{{ $ownerName ?? 'Kepala Keuangan & Owner' }}</div>
                    <div class="sign-role">Otorisasi & Pengesahan Laporan</div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
