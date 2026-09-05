<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bagan Akun Standar (Chart of Accounts)</title>
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
        
        /* 6-KPI Mini Summary Cards */
        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 4px 0;
            margin-bottom: 12px;
        }
        .summary-card {
            padding: 5px 6px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            text-align: center;
            background-color: #f8fafc;
        }
        .summary-title {
            font-size: 7px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }
        .summary-value {
            font-size: 9.5px;
            font-weight: 700;
            font-family: 'Courier New', Courier, monospace;
        }
        .val-primary { color: #0f172a; }
        .val-success { color: #16a34a; }
        .val-warning { color: #d97706; }
        .val-danger  { color: #dc2626; }
        .val-info    { color: #2563eb; }

        /* Table */
        .coa-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .coa-table th {
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
        .coa-table td {
            border: 1px solid #e2e8f0;
            padding: 4.5px 6px;
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
        
        .badge {
            display: inline-block;
            padding: 1.5px 5px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-asset { background-color: #dbeafe; color: #1d4ed8; }
        .badge-liability { background-color: #fee2e2; color: #b91c1c; }
        .badge-equity { background-color: #fef3c7; color: #b45309; }
        .badge-revenue { background-color: #dcfce7; color: #15803d; }
        .badge-cogs { background-color: #f1f5f9; color: #475569; }
        .badge-expense { background-color: #e0f2fe; color: #0369a1; }

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
                    <strong>Dokumen:</strong> Master Bagan Akun Standar (COA)<br>
                    <strong>Sistem:</strong> Akuntansi & Pembukuan Double-Entry Terpadu<br>
                    <strong>Dicetak Oleh:</strong> {{ $userName }} ({{ $userRole }}) • {{ date('d/m/Y H:i:s') }}
                </div>
            </td>
            <td style="width: 45%; vertical-align: top; text-align: right;">
                <div class="doc-title">BAGAN AKUN PERUSAHAAN (CHART OF ACCOUNTS)</div>
                <div class="doc-subtitle">
                    Total Rekening: <strong>{{ count($accounts) }} Akun Terdaftar</strong><br>
                    Status: <strong>AKTIF & RESMI DIGUNAKAN</strong>
                </div>
            </td>
        </tr>
    </table>

    <!-- 6 KPI Summary Cards -->
    <table class="summary-table">
        <tr>
            <td style="width: 16.6%;">
                <div class="summary-card">
                    <div class="summary-title">ASET (AKTIVA)</div>
                    <div class="summary-value val-info">{{ $counts['asset'] ?? 0 }} Akun</div>
                </div>
            </td>
            <td style="width: 16.6%;">
                <div class="summary-card">
                    <div class="summary-title">KEWAJIBAN</div>
                    <div class="summary-value val-danger">{{ $counts['liability'] ?? 0 }} Akun</div>
                </div>
            </td>
            <td style="width: 16.6%;">
                <div class="summary-card">
                    <div class="summary-title">EKUITAS</div>
                    <div class="summary-value val-warning">{{ $counts['equity'] ?? 0 }} Akun</div>
                </div>
            </td>
            <td style="width: 16.6%;">
                <div class="summary-card">
                    <div class="summary-title">PENDAPATAN</div>
                    <div class="summary-value val-success">{{ $counts['revenue'] ?? 0 }} Akun</div>
                </div>
            </td>
            <td style="width: 16.6%;">
                <div class="summary-card">
                    <div class="summary-title">HPP</div>
                    <div class="summary-value val-primary">{{ $counts['cogs'] ?? 0 }} Akun</div>
                </div>
            </td>
            <td style="width: 16.6%;">
                <div class="summary-card">
                    <div class="summary-title">BEBAN OPERASIONAL</div>
                    <div class="summary-value val-info">{{ $counts['expense'] ?? 0 }} Akun</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- COA Table -->
    <table class="coa-table">
        <thead>
            <tr>
                <th style="width: 12%;">KODE AKUN</th>
                <th style="width: 32%;">NAMA REKENING AKUN</th>
                <th style="width: 18%;">KLASIFIKASI / TIPE</th>
                <th style="width: 12%;">SALDO NORMAL</th>
                <th style="width: 16%;">SALDO BERJALAN (RP)</th>
                <th style="width: 10%;">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @foreach($accounts as $index => $acc)
                <tr class="{{ $index % 2 == 1 ? 'row-even' : '' }}">
                    <td class="font-mono font-bold text-center" style="color: #0369a1;">
                        {{ $acc['code'] }}
                    </td>
                    <td>
                        <span style="{{ !empty($acc['parent_id']) ? 'padding-left: 14px;' : 'font-weight: 700;' }}">
                            @if(!empty($acc['parent_id'])) ↳ @endif {{ $acc['name'] }}
                        </span>
                        @if(!empty($acc['description']))
                            <div style="font-size: 7px; color: #64748b;">{{ $acc['description'] }}</div>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $acc['type'] }}">{{ strtoupper($acc['type']) }} ({{ $acc['category'] }})</span>
                    </td>
                    <td class="text-center font-bold" style="color: {{ $acc['normal_balance'] === 'debit' ? '#16a34a' : '#d97706' }}; text-transform: uppercase;">
                        {{ $acc['normal_balance'] }}
                    </td>
                    <td class="text-right font-mono font-bold" style="color: #0f172a;">
                        Rp {{ number_format($acc['current_balance'], 0, ',', '.') }}
                    </td>
                    <td class="text-center">
                        <span style="font-size: 7.5px; font-weight: 700; color: {{ $acc['is_active'] ? '#16a34a' : '#dc2626' }};">
                            {{ $acc['is_active'] ? 'AKTIF' : 'NON-AKTIF' }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Digital Signatures Footer -->
    <table class="footer-table">
        <tr>
            <td style="width: 30%; vertical-align: top;">
                <div class="sign-box">
                    <div class="sign-title">Disiapkan / Petugas COA</div>
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
                        MASTER BAGAN AKUN PERUSAHAAN
                    </div>
                    <div style="font-size: 7px; color: #64748b; line-height: 1.3;">
                        Bagan akun ini merupakan standar klasifikasi buku besar yang digunakan untuk seluruh pencatatan transaksi POS, kas kecil, pembelian, hutang supplier, dan piutang pelanggan.
                    </div>
                </div>
            </td>
            <td style="width: 30%; vertical-align: top;">
                <div class="sign-box">
                    <div class="sign-title">Disahkan / Manajemen</div>
                    <div style="padding: 3px 0;">
                        @if(!empty($documentQrCode))
                            <img src="data:image/svg+xml;base64,{{ $documentQrCode }}" style="width: 55px; height: 55px;">
                        @else
                            <div class="sign-space"></div>
                        @endif
                    </div>
                    <div class="sign-name">{{ $ownerName ?? 'Kepala Keuangan & Owner' }}</div>
                    <div class="sign-role">Otorisasi Finansial & Standar Akun</div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
