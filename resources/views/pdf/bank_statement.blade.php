<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekening Koran - {{ $bankAccount->bank_name }} - {{ $bankAccount->account_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 12mm 12mm 12mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9.5px;
            line-height: 1.4;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        
        /* Header Table */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .company-name {
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f172a;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .branch-info {
            font-size: 9px;
            color: #64748b;
            line-height: 1.4;
        }
        .doc-title {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            text-align: right;
            color: #0f172a;
            letter-spacing: 0.8px;
        }
        .doc-subtitle {
            font-size: 9px;
            text-align: right;
            color: #64748b;
            margin-top: 2px;
        }
        
        /* Account Info Box */
        .info-card {
            width: 100%;
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background-color: #f8fafc;
            padding: 8px 10px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            vertical-align: top;
            padding: 2.5px 4px;
            font-size: 9.5px;
        }
        .label {
            font-weight: 600;
            color: #64748b;
            width: 105px;
        }
        .val {
            color: #0f172a;
            font-weight: 600;
        }

        /* 4-KPI Summary Cards */
        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 14px;
        }
        .summary-card {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            text-align: center;
            background-color: #ffffff;
        }
        .summary-title {
            font-size: 8px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .summary-value {
            font-size: 11px;
            font-weight: 700;
            font-family: 'Courier New', Courier, monospace;
        }
        .val-primary { color: #0f172a; }
        .val-success { color: #16a34a; }
        .val-danger  { color: #dc2626; }

        /* Mutation Table */
        .mutation-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .mutation-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #cbd5e1;
            padding: 6px 6px;
            text-align: center;
            letter-spacing: 0.3px;
        }
        .mutation-table td {
            border: 1px solid #e2e8f0;
            padding: 5px 6px;
            font-size: 8.5px;
            vertical-align: top;
            color: #1e293b;
        }
        .row-even {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .text-left   { text-align: left; }
        .font-mono   { font-family: 'Courier New', Courier, monospace; }
        .font-bold   { font-weight: 700; }
        
        .badge-cat {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: 700;
            text-transform: uppercase;
            background-color: #f1f5f9;
            color: #475569;
        }

        /* Footer & Signatures */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .footer-table td {
            vertical-align: top;
            padding: 0 8px;
        }
        .ttd-box {
            text-align: center;
            font-size: 9px;
        }
        .ttd-space {
            height: 44px;
        }
        .ttd-line {
            font-weight: 700;
            color: #0f172a;
            border-top: 1px solid #0f172a;
            padding-top: 4px;
            display: inline-block;
            min-width: 140px;
        }
        .disclaimer {
            font-size: 7.5px;
            color: #94a3b8;
            font-style: italic;
            margin-top: 6px;
            line-height: 1.3;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="width: 58%; vertical-align: top;">
                <div class="company-name">
                    {{ $branch->owner->name ?? ($branch->name ?? 'PT. PAGARUYUNG MITRA PERSADA') }}
                </div>
                <div class="branch-info">
                    {{ $branch->name ?? 'Cabang Utama' }} &bull; {{ $branch->address ?? 'Jl. Jendral Sudirman No. 128, Dumai' }}<br>
                    Telp: {{ $branch->phone ?? '(0765) 31234' }} | Email: {{ $branch->email ?? 'finance@pagaruyung.com' }}
                </div>
            </td>
            <td style="width: 42%; vertical-align: top; text-align: right;">
                <div class="doc-title">REKENING KORAN BANK</div>
                <div class="doc-subtitle">Laporan Resmi Buku Mutasi Rekening</div>
                <div style="font-size: 8.5px; color: #64748b; margin-top: 5px;">
                    Dicetak: <strong style="color: #0f172a;">{{ date('d/m/Y H:i') }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <!-- Bank Account Metadata -->
    <div class="info-card">
        <table class="info-table">
            <tr>
                <td style="width: 50%;">
                    <table style="width: 100%;">
                        <tr>
                            <td class="label">Nama Bank</td>
                            <td style="width: 6px;">:</td>
                            <td class="val">{{ $bankAccount->bank_name }}</td>
                        </tr>
                        <tr>
                            <td class="label">Nomor Rekening</td>
                            <td>:</td>
                            <td class="val font-mono">{{ $bankAccount->account_number ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Atas Nama</td>
                            <td>:</td>
                            <td class="val">{{ $bankAccount->account_name ?: '-' }}</td>
                        </tr>
                    </table>
                </td>
                <td style="width: 50%;">
                    <table style="width: 100%;">
                        <tr>
                            <td class="label">Periode Mutasi</td>
                            <td style="width: 6px;">:</td>
                            <td class="val">{{ date('d/m/Y', strtotime($period['start_date'])) }} s/d {{ date('d/m/Y', strtotime($period['end_date'])) }}</td>
                        </tr>
                        <tr>
                            <td class="label">Tipe Akun</td>
                            <td>:</td>
                            <td class="val" style="text-transform: uppercase;">{{ str_replace('_', ' ', $bankAccount->type ?: 'Bank Transfer') }}</td>
                        </tr>
                        <tr>
                            <td class="label">Mata Uang</td>
                            <td>:</td>
                            <td class="val">IDR (Rupiah Indonesia)</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- 4-Summary KPI Cards -->
    <table class="summary-table">
        <tr>
            <td style="width: 25%; padding: 0;">
                <div class="summary-card" style="border-left: 3px solid #0f172a;">
                    <div class="summary-title">Saldo Awal Periode</div>
                    <div class="summary-value val-primary">
                        Rp {{ number_format($summary['opening_balance'], 0, ',', '.') }}
                    </div>
                </div>
            </td>
            <td style="width: 25%; padding: 0;">
                <div class="summary-card" style="border-left: 3px solid #16a34a;">
                    <div class="summary-title">Total Masuk (Kredit +)</div>
                    <div class="summary-value val-success">
                        +Rp {{ number_format($summary['total_credit'], 0, ',', '.') }}
                    </div>
                </div>
            </td>
            <td style="width: 25%; padding: 0;">
                <div class="summary-card" style="border-left: 3px solid #dc2626;">
                    <div class="summary-title">Total Keluar (Debet -)</div>
                    <div class="summary-value val-danger">
                        -Rp {{ number_format($summary['total_debit'], 0, ',', '.') }}
                    </div>
                </div>
            </td>
            <td style="width: 25%; padding: 0;">
                <div class="summary-card" style="border-left: 3px solid #2563eb;">
                    <div class="summary-title">Saldo Akhir Periode</div>
                    <div class="summary-value val-primary">
                        Rp {{ number_format($summary['closing_balance'], 0, ',', '.') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Ledger Mutation Table -->
    <table class="mutation-table">
        <thead>
            <tr>
                <th style="width: 22px;">No</th>
                <th style="width: 62px;">Tgl / Jam</th>
                <th style="width: 88px;">No. Referensi</th>
                <th style="width: 80px;">Kategori</th>
                <th>Keterangan Transaksi</th>
                <th style="width: 72px;">Debet (-)</th>
                <th style="width: 72px;">Kredit (+)</th>
                <th style="width: 82px;">Saldo (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <!-- Opening Balance Row -->
            <tr style="background-color: #f8fafc; font-style: italic;">
                <td class="text-center font-bold" style="color: #64748b;">-</td>
                <td class="text-center font-bold" style="color: #64748b;">{{ date('d/m/Y', strtotime($period['start_date'])) }}</td>
                <td class="text-center font-bold" style="color: #64748b;">-</td>
                <td><span class="badge-cat">SALDO AWAL</span></td>
                <td style="color: #64748b;">Saldo awal sebelum periode mutasi berjalan</td>
                <td class="text-right" style="color: #94a3b8;">-</td>
                <td class="text-right" style="color: #94a3b8;">-</td>
                <td class="text-right font-mono font-bold" style="color: #0f172a;">
                    {{ number_format($summary['opening_balance'], 0, ',', '.') }}
                </td>
            </tr>

            @forelse($mutations as $index => $item)
            <tr class="{{ $index % 2 === 1 ? 'row-even' : '' }}">
                <td class="text-center" style="color: #94a3b8;">{{ $index + 1 }}</td>
                <td class="text-center">
                    <span class="font-bold">{{ date('d/m/y', strtotime($item['date'])) }}</span>
                    <span style="font-size: 7px; color: #94a3b8; display: block;">{{ $item['time'] ?: '' }}</span>
                </td>
                <td class="font-mono" style="font-size: 7.5px; font-weight: 700; color: #334155;">
                    {{ $item['reference_no'] }}
                </td>
                <td>
                    <span class="badge-cat">{{ $item['category'] }}</span>
                </td>
                <td style="line-height: 1.25;">
                    {{ $item['description'] }}
                </td>
                <td class="text-right font-mono" style="color: {{ $item['debit'] > 0 ? '#dc2626' : '#cbd5e1' }}; font-weight: {{ $item['debit'] > 0 ? '700' : 'normal' }};">
                    {{ $item['debit'] > 0 ? number_format($item['debit'], 0, ',', '.') : '-' }}
                </td>
                <td class="text-right font-mono" style="color: {{ $item['credit'] > 0 ? '#16a34a' : '#cbd5e1' }}; font-weight: {{ $item['credit'] > 0 ? '700' : 'normal' }};">
                    {{ $item['credit'] > 0 ? number_format($item['credit'], 0, ',', '.') : '-' }}
                </td>
                <td class="text-right font-mono font-bold" style="color: #0f172a;">
                    {{ number_format($item['running_balance'], 0, ',', '.') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center" style="padding: 16px; color: #94a3b8;">
                    Tidak ada catatan mutasi transaksi pada periode yang dipilih.
                </td>
            </tr>
            @endforelse

            <!-- Total Row -->
            <tr style="background-color: #f1f5f9; font-weight: 700; border-top: 2px solid #cbd5e1;">
                <td colspan="5" class="text-right" style="padding: 6px; color: #334155; font-size: 8px;">
                    TOTAL PERGERAKAN & SALDO AKHIR:
                </td>
                <td class="text-right font-mono" style="color: #dc2626;">{{ number_format($summary['total_debit'], 0, ',', '.') }}</td>
                <td class="text-right font-mono" style="color: #16a34a;">{{ number_format($summary['total_credit'], 0, ',', '.') }}</td>
                <td class="text-right font-mono font-bold" style="color: #0f172a; font-size: 9.5px;">{{ number_format($summary['closing_balance'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Footer & Signatures -->
    <table class="footer-table">
        <tr>
            <td style="width: 36%; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px 8px; background-color: #f8fafc;">
                    <tr>
                        <td style="width: 52px; vertical-align: middle; padding-right: 6px;">
                            @if(isset($documentQrCode) && $documentQrCode)
                            <img src="data:image/svg+xml;base64,{{ $documentQrCode }}" style="width: 48px; height: 48px;" />
                            @endif
                        </td>
                        <td style="vertical-align: middle;">
                            <div style="font-size: 7.5px; color: #475569; line-height: 1.35;">
                                <strong style="color: #0f172a; font-size: 8px;">BUKTI KEABSAHAN DATA:</strong><br>
                                Pindai QR ini untuk verifikasi keaslian rekonsiliasi data mutasi bank pada sistem <strong>Ms.POS</strong>.
                            </div>
                        </td>
                    </tr>
                </table>
                <div class="disclaimer">
                    * Harap periksa mutasi secara berkala. Apabila terdapat selisih, hubungi bagian administrasi keuangan dalam 7 hari kerja.
                </div>
            </td>
            <td style="width: 32%; text-align: center;">
                <div class="ttd-box">
                    <div style="color: #64748b; font-weight: 600;">Petugas Keuangan / Kasir,</div>
                    <div style="padding: 3px 0 1px 0;">
                        @if(isset($signerQrCode) && $signerQrCode)
                        <img src="data:image/svg+xml;base64,{{ $signerQrCode }}" style="width: 46px; height: 46px;" />
                        <div style="font-size: 6.5px; color: #16a34a; font-weight: 700; margin-top: 1px;">[TERTANDA DIGITAL]</div>
                        @else
                        <div class="ttd-space"></div>
                        @endif
                    </div>
                    <div class="ttd-line">{{ $userName ?? (auth()->user()->name ?? 'Finance Officer') }}</div>
                    <div style="font-size: 7.5px; color: #64748b; margin-top: 2px;">{{ $userRole ?? 'Petugas Keuangan' }} &bull; {{ date('d/m/Y') }}</div>
                </div>
            </td>
            <td style="width: 32%; text-align: center;">
                <div class="ttd-box">
                    <div style="color: #64748b; font-weight: 600;">Mengetahui (Pimpinan / Owner),</div>
                    <div class="ttd-space" style="height: 54px;"></div>
                    <div class="ttd-line">{{ $ownerName ?? ($branch->owner->name ?? 'Pimpinan Toko') }}</div>
                    <div style="font-size: 7.5px; color: #64748b; margin-top: 2px;">{{ $branch->name ?? 'Cabang Utama' }} &bull; {{ date('d/m/Y') }}</div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
