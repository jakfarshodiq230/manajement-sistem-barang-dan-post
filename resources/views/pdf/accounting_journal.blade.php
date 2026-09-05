<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Jurnal Transaksi - {{ $startDate }} s/d {{ $endDate }}</title>
    <style>
        @page {
            size: A4 landscape;
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
            font-size: 10px;
            font-weight: 700;
            font-family: 'Courier New', Courier, monospace;
        }
        .val-primary { color: #0f172a; }
        .val-success { color: #16a34a; }
        .val-danger  { color: #dc2626; }
        .val-info    { color: #2563eb; }

        /* Journal Table */
        .journal-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .journal-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #cbd5e1;
            padding: 5px 5px;
            text-align: center;
            letter-spacing: 0.3px;
        }
        .journal-table td {
            border: 1px solid #e2e8f0;
            padding: 4px 5px;
            font-size: 8px;
            vertical-align: top;
            color: #1e293b;
        }
        .row-even {
            background-color: #fafbfc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'Courier New', Courier, monospace; font-size: 8.5px; }
        .font-bold { font-weight: 700; }
        
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-sale { background-color: #dcfce7; color: #15803d; }
        .badge-goodsreceipt { background-color: #dbeafe; color: #1d4ed8; }
        .badge-payablepayment { background-color: #fee2e2; color: #b91c1c; }
        .badge-receivablepayment { background-color: #f3e8ff; color: #7e22ce; }
        .badge-pettycash { background-color: #fef3c7; color: #b45309; }
        .badge-branchcapital { background-color: #e0f2fe; color: #0369a1; }
        .badge-manual { background-color: #f1f5f9; color: #475569; }

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
                <div class="doc-title">BUKU JURNAL UMUM (GENERAL JOURNAL)</div>
                <div class="doc-subtitle">
                    Periode: <strong>{{ $startDate }}</strong> s/d <strong>{{ $endDate }}</strong><br>
                    Status: <strong>POSTED (TERCATAT & SEIMBANG)</strong>
                </div>
            </td>
        </tr>
    </table>

    <!-- 4 KPI Summary Cards -->
    <table class="summary-table">
        <tr>
            <td style="width: 25%;">
                <div class="summary-card">
                    <div class="summary-title">TOTAL VOUCHER JURNAL</div>
                    <div class="summary-value val-primary">{{ number_format($totalJournals, 0, ',', '.') }} Entri</div>
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
                    <div class="summary-value val-info">Rp {{ number_format($totalCredit, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="summary-card">
                    <div class="summary-title">KESEIMBANGAN (BALANCE)</div>
                    <div class="summary-value {{ abs($totalDebit - $totalCredit) < 0.01 ? 'val-success' : 'val-danger' }}">
                        {{ abs($totalDebit - $totalCredit) < 0.01 ? 'SEIMBANG (MATCH)' : 'SELISIH Rp ' . number_format(abs($totalDebit - $totalCredit), 0, ',', '.') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Journal Table -->
    <table class="journal-table">
        <thead>
            <tr>
                <th style="width: 7%;">TANGGAL</th>
                <th style="width: 11%;">NO. JURNAL</th>
                <th style="width: 12%;">SUMBER / CABANG</th>
                <th style="width: 20%;">KETERANGAN / MEMO</th>
                <th style="width: 9%;">KODE AKUN</th>
                <th style="width: 21%;">NAMA REKENING AKUN</th>
                <th style="width: 10%;">DEBIT (RP)</th>
                <th style="width: 10%;">KREDIT (RP)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($journals as $jIndex => $j)
                @php
                    $itemCount = count($j['items']);
                    $rowBg = $jIndex % 2 == 1 ? 'row-even' : '';
                @endphp
                @foreach($j['items'] as $iIndex => $item)
                    <tr class="{{ $rowBg }}">
                        @if($iIndex === 0)
                            <td rowspan="{{ $itemCount }}" class="text-center font-mono font-bold">{{ $j['entry_date'] }}</td>
                            <td rowspan="{{ $itemCount }}" class="font-mono font-bold text-center" style="color: #0369a1;">
                                {{ $j['entry_number'] }}
                            </td>
                            <td rowspan="{{ $itemCount }}">
                                <div><span class="badge badge-{{ strtolower($j['reference_type']) }}">{{ $j['ref_label'] }}</span></div>
                                <div style="font-size: 7.5px; color: #64748b; margin-top: 2px;">{{ $j['branch_name'] }}</div>
                            </td>
                            <td rowspan="{{ $itemCount }}">
                                <div style="font-size: 8px;">{{ $j['notes'] ?: '-' }}</div>
                            </td>
                        @endif
                        <td class="font-mono text-center font-bold">{{ $item['account_code'] }}</td>
                        <td>
                            <span style="{{ $item['credit'] > 0 ? 'padding-left: 12px; color: #64748b;' : 'font-weight: 600;' }}">
                                {{ $item['account_name'] }}
                            </span>
                            @if(!empty($item['memo']))
                                <div style="font-size: 7px; color: #94a3b8; font-style: italic;">{{ $item['memo'] }}</div>
                            @endif
                        </td>
                        <td class="text-right font-mono" style="{{ $item['debit'] > 0 ? 'font-weight: 700; color: #0f172a;' : 'color: #94a3b8;' }}">
                            {{ $item['debit'] > 0 ? number_format($item['debit'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-right font-mono" style="{{ $item['credit'] > 0 ? 'font-weight: 700; color: #0f172a;' : 'color: #94a3b8;' }}">
                            {{ $item['credit'] > 0 ? number_format($item['credit'], 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 16px; color: #64748b;">
                        Tidak ada entri jurnal transaksi pada rentang periode dan filter ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($journals) > 0)
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: 800;">
                <td colspan="6" class="text-right" style="padding: 6px; font-size: 8.5px; text-transform: uppercase;">
                    TOTAL MUTASI PERIODE INI:
                </td>
                <td class="text-right font-mono font-bold" style="padding: 6px; font-size: 8.5px; color: #16a34a;">
                    Rp {{ number_format($totalDebit, 0, ',', '.') }}
                </td>
                <td class="text-right font-mono font-bold" style="padding: 6px; font-size: 8.5px; color: #2563eb;">
                    Rp {{ number_format($totalCredit, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
        @endif
    </table>

    <!-- Digital Signatures Footer -->
    <table class="footer-table">
        <tr>
            <td style="width: 30%; vertical-align: top;">
                <div class="sign-box">
                    <div class="sign-title">Disiapkan / Petugas Input</div>
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
                        SISTEM VERIFIKASI DIGITAL AKUNTANSI
                    </div>
                    <div style="font-size: 7px; color: #64748b; line-height: 1.3;">
                        Dokumen ini digenerate secara otomatis oleh modul Akuntansi Terpadu PT Dumai. Seluruh saldo debit dan kredit telah divalidasi dengan integritas data double-entry.
                    </div>
                </div>
            </td>
            <td style="width: 30%; vertical-align: top;">
                <div class="sign-box">
                    <div class="sign-title">Diperiksa / Akuntan & Owner</div>
                    <div style="padding: 3px 0;">
                        @if(!empty($documentQrCode))
                            <img src="data:image/svg+xml;base64,{{ $documentQrCode }}" style="width: 55px; height: 55px;">
                        @else
                            <div class="sign-space"></div>
                        @endif
                    </div>
                    <div class="sign-name">{{ $ownerName ?? 'Kepala Keuangan & Owner' }}</div>
                    <div class="sign-role">Otorisasi Pembukuan & Manajemen</div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
