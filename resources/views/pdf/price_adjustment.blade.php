<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Penetapan Harga - {{ $adjustment->adjustment_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 9pt;
            line-height: 1.35;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .company-name {
            font-size: 14pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-info {
            font-size: 8pt;
            color: #475569;
        }
        .doc-title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin-top: 6px;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }
        .doc-subtitle {
            text-align: center;
            font-size: 9pt;
            color: #64748b;
            margin-bottom: 14px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 3px 6px;
            font-size: 8.5pt;
            vertical-align: top;
        }
        .meta-label {
            width: 18%;
            font-weight: bold;
            color: #475569;
        }
        .meta-sep {
            width: 2%;
        }
        .meta-val {
            width: 30%;
            color: #0f172a;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-approved {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .badge-draft {
            background-color: #fef9c3;
            color: #854d0e;
            border: 1px solid #fde047;
        }
        .kpi-table {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: separate;
            border-spacing: 6px 0;
        }
        .kpi-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
        }
        .kpi-title {
            font-size: 7pt;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
        }
        .kpi-value {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 8pt;
            text-transform: uppercase;
            padding: 6px 5px;
            text-align: left;
            border: 1px solid #0f172a;
        }
        .items-table td {
            padding: 5px 5px;
            font-size: 8pt;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .text-success { color: #16a34a; font-weight: bold; }
        .text-error { color: #dc2626; font-weight: bold; }
        .text-muted { color: #64748b; }
        .sig-table {
            width: 100%;
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .sig-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            font-size: 8.5pt;
        }
        .sig-box {
            height: 55px;
        }
        .sig-name {
            font-weight: bold;
            text-decoration: underline;
            color: #0f172a;
        }
        .sig-title {
            font-size: 7.5pt;
            color: #64748b;
        }
        .footer-note {
            margin-top: 15px;
            font-size: 7.5pt;
            color: #94a3b8;
            border-top: 1px dashed #cbd5e1;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <!-- Header Perusahaan -->
    <table class="header-table">
        <tr>
            <td style="width: 60%; vertical-align: middle;">
                <div class="company-name">{{ $owner->company_name ?? $owner->name ?? 'PT. DUMAI BERKAH ABADI' }}</div>
                <div class="company-info">
                    {{ $owner->address ?? 'Pusat Distribusi & Operasional Toko Retail' }}<br>
                    Telepon: {{ $owner->phone ?? '-' }} | Email: {{ $owner->email ?? '-' }}
                </div>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: middle;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="text-align: right; vertical-align: middle; padding-right: 8px;">
                            <div style="font-size: 7.5pt; color: #64748b; font-weight: bold; text-transform: uppercase;">SK PENETAPAN HARGA RESMI</div>
                            <div style="font-size: 9.5pt; font-weight: bold; font-family: monospace; color: #0f172a;">
                                {{ $adjustment->adjustment_number }}
                            </div>
                            <div style="font-size: 7pt; color: #16a34a; font-weight: bold; margin-top: 2px;">
                                [DOKUMEN TERVALIDASI SISTEM]
                            </div>
                        </td>
                        <td style="width: 50px; text-align: right; vertical-align: middle;">
                            @if(isset($documentQrCode) && $documentQrCode)
                                <img src="data:image/svg+xml;base64,{{ $documentQrCode }}" style="width: 48px; height: 48px; border: 1px solid #cbd5e1; padding: 2px; background: #fff;" alt="QR SK" />
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Judul Dokumen -->
    <div class="doc-title">SURAT PENETAPAN HARGA JUAL TOKO (PERIODIK)</div>
    <div class="doc-subtitle">Berlaku Efektif Terhitung Sejak Tanggal: <strong>{{ \Carbon\Carbon::parse($adjustment->effective_date)->isoFormat('D MMMM Y') }}</strong></div>

    <!-- Metadata Dokumen -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">No. Dokumen</td>
            <td class="meta-sep">:</td>
            <td class="meta-val font-mono font-weight-bold">{{ $adjustment->adjustment_number }}</td>

            <td class="meta-label">Target Cabang</td>
            <td class="meta-sep">:</td>
            <td class="meta-val"><strong>{{ $adjustment->branch ? $adjustment->branch->name : 'Semua Cabang Toko (Pusat & Cabang)' }}</strong></td>
        </tr>
        <tr>
            <td class="meta-label">Judul Periode</td>
            <td class="meta-sep">:</td>
            <td class="meta-val"><strong>{{ $adjustment->title }}</strong></td>

            <td class="meta-label">Status Dokumen</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">
                <span class="status-badge {{ $adjustment->status === 'approved' ? 'badge-approved' : 'badge-draft' }}">
                    {{ $adjustment->status === 'approved' ? 'DISETUJUI & BERLAKU' : 'DRAFT USULAN' }}
                </span>
            </td>
        </tr>
        <tr>
            <td class="meta-label">Alasan Penyesuaian</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">{{ $adjustment->reason ?? 'Penyesuaian Harga Berkala' }}</td>

            <td class="meta-label">Aturan Batch Fisik</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">
                <strong style="color: #0369a1;">
                    {{ ($adjustment->batch_policy ?? 'all_active') === 'all_active' ? 'Seluruh Batch Aktif (Sinkron)' : 'Hanya Batch Baru (Pertahankan Batch Lama)' }}
                </strong>
            </td>
        </tr>
    </table>

    <!-- KPI Summary Cards -->
    <table class="kpi-table">
        <tr>
            <td style="width: 25%;">
                <div class="kpi-card">
                    <div class="kpi-title">Total Produk Diubah</div>
                    <div class="kpi-value">{{ $totalItems }} SKU</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-card">
                    <div class="kpi-title">Produk Naik Harga</div>
                    <div class="kpi-value text-success">+{{ $totalItemsIncreased }} Item</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-card">
                    <div class="kpi-title">Produk Turun / Stabil</div>
                    <div class="kpi-value text-muted">{{ $totalItemsDecreased }} Item</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-card">
                    <div class="kpi-title">Total Penyesuaian Nilai</div>
                    <div class="kpi-value font-mono {{ $totalPriceIncrease >= 0 ? 'text-success' : 'text-error' }}">
                        {{ $totalPriceIncrease >= 0 ? '+' : '' }}Rp {{ number_format($totalPriceIncrease, 0, ',', '.') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Tabel Rincian Daftar Harga -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 22px;">No</th>
                <th style="width: 70px;">Kode SKU</th>
                <th class="text-center" style="width: 38px;">QR Barcode</th>
                <th>Nama Produk & Kategori</th>
                <th class="text-end" style="width: 75px;">HPP Modal</th>
                <th class="text-end" style="width: 75px;">Harga Lama</th>
                <th class="text-end" style="width: 80px;">Harga Baru</th>
                <th class="text-end" style="width: 65px;">Selisih (Rp)</th>
                <th class="text-end" style="width: 70px;">Min. Nego</th>
            </tr>
        </thead>
        <tbody>
            @foreach($adjustment->items as $index => $item)
                @php
                    $diff = (float)$item->new_price - (float)$item->old_price;
                    $percent = (float)$item->old_price > 0 ? round(($diff / (float)$item->old_price) * 100, 1) : 0;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-mono" style="font-size: 7.5pt; font-weight: bold;">{{ $item->product->sku ?? '-' }}</td>
                    <td class="text-center" style="padding: 2px;">
                        @if(isset($itemQrCodes[$item->id]))
                            <img src="data:image/svg+xml;base64,{{ $itemQrCodes[$item->id] }}" style="width: 28px; height: 28px;" alt="QR Item" />
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        <strong>{{ $item->product->name ?? '-' }}</strong><br>
                        <span class="text-muted" style="font-size: 7pt;">{{ $item->product->category->name ?? 'Umum' }}</span>
                    </td>
                    <td class="text-end font-mono">Rp {{ number_format($item->new_cost_price, 0, ',', '.') }}</td>
                    <td class="text-end font-mono text-muted">Rp {{ number_format($item->old_price, 0, ',', '.') }}</td>
                    <td class="text-end font-mono" style="font-weight: bold; color: #0f172a;">
                        Rp {{ number_format($item->new_price, 0, ',', '.') }}
                    </td>
                    <td class="text-end font-mono {{ $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-error' : 'text-muted') }}">
                        {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 0, ',', '.') }}
                        <div style="font-size: 6.5pt;">({{ $diff > 0 ? '+' : '' }}{{ $percent }}%)</div>
                    </td>
                    <td class="text-end font-mono text-muted">Rp {{ number_format($item->new_min_nego_price, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Tanda Tangan & Otorisasi -->
    <table class="sig-table">
        <tr>
            <td>
                <div style="font-weight: 600; color: #475569; margin-bottom: 3px;">Dibuat & Dianalisis Oleh:</div>
                <div class="sig-box" style="padding: 2px 0;">
                    @if(isset($creatorQrCode) && $creatorQrCode)
                        <img src="data:image/svg+xml;base64,{{ $creatorQrCode }}" style="width: 45px; height: 45px;" alt="QR TTD Pembuat" />
                        <div style="font-size: 6pt; color: #16a34a; font-weight: bold;">[TERTANDA DIGITAL]</div>
                    @else
                        <div style="height: 45px;"></div>
                    @endif
                </div>
                <div class="sig-name">{{ $adjustment->creator->name ?? 'Staf Administrasi' }}</div>
                <div class="sig-title">Staf Administrasi / Analis Harga</div>
            </td>
            <td>
                <div style="font-weight: 600; color: #475569; margin-bottom: 3px;">Diperiksa Oleh:</div>
                <div class="sig-box" style="padding: 2px 0;">
                    @if(isset($reviewerQrCode) && $reviewerQrCode)
                        <img src="data:image/svg+xml;base64,{{ $reviewerQrCode }}" style="width: 45px; height: 45px;" alt="QR TTD Pemeriksa" />
                        <div style="font-size: 6pt; color: #2563eb; font-weight: bold;">[TERVERIFIKASI]</div>
                    @else
                        <div style="height: 45px;"></div>
                    @endif
                </div>
                <div class="sig-name">Kepala Bagian Operasional</div>
                <div class="sig-title">Manajer Operasional Toko</div>
            </td>
            <td>
                <div style="font-weight: 600; color: #475569; margin-bottom: 3px;">Disahkan & Ditetapkan Oleh:</div>
                <div class="sig-box" style="padding: 2px 0;">
                    @if(isset($approverQrCode) && $approverQrCode)
                        <img src="data:image/svg+xml;base64,{{ $approverQrCode }}" style="width: 45px; height: 45px;" alt="QR TTD Pengesah" />
                        <div style="font-size: 6pt; color: #16a34a; font-weight: bold;">[DISAHKAN RESMI]</div>
                    @else
                        <div style="height: 45px;"></div>
                    @endif
                </div>
                <div class="sig-name">{{ $adjustment->approver->name ?? $owner->name ?? 'Owner / Direktur' }}</div>
                <div class="sig-title">Owner / Direksi PT. DUMAI</div>
            </td>
        </tr>
    </table>

    <!-- Catatan Kaki & Keabsahan -->
    <div class="footer-note">
        <table style="width: 100%;">
            <tr>
                <td style="font-size: 7.5pt; color: #64748b;">
                    * Dokumen ini sah dan mengikat seluruh unit kasir (POS) dan katalog penjualan cabang sejak tanggal efektif.<br>
                    ID Verifikasi Sistem: <span class="font-mono">{{ $verificationUuid }}</span> | Dicetak pada: {{ $printedAt }}
                </td>
                <td style="text-align: right; font-size: 7.5pt; color: #64748b;">
                    Dokumen Otentik &bull; PT. DUMAI BERKAH ABADI
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
