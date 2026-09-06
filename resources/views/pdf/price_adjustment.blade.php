<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SK Penetapan Harga Resmi - {{ $adjustment->adjustment_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 14mm 14mm 14mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #0f172a;
            font-size: 8.5pt;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }

        /* 1. Kop Dokumen Resmi */
        .kop-table {
            width: 100%;
            border-bottom: 2.5px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .company-title {
            font-size: 14pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .company-desc {
            font-size: 7.5pt;
            color: #475569;
            line-height: 1.35;
        }
        .sk-card-title {
            font-size: 7.5pt;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .sk-card-number {
            font-size: 9.5pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 1px;
            letter-spacing: 0.3px;
        }
        .sk-card-date {
            font-size: 7pt;
            color: #64748b;
            margin-top: 1px;
        }

        /* 2. Parameter Grid Metadata SK */
        .meta-box {
            width: 100%;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .meta-box td {
            padding: 5px 8px;
            font-size: 8pt;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
        }
        .meta-lbl {
            width: 17%;
            font-weight: bold;
            color: #475569;
            font-size: 7.5pt;
            text-transform: uppercase;
        }
        .meta-sep {
            width: 2%;
            text-align: center;
            color: #94a3b8;
        }
        .meta-val {
            width: 31%;
            color: #0f172a;
        }

        /* 3. KPI Metrics Summary Cards */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 12px;
        }
        .kpi-cell {
            width: 25%;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            padding: 6px 4px;
            text-align: center;
        }
        .kpi-title {
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.3px;
        }
        .kpi-num {
            font-size: 10.5pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }

        /* 4. Table Rincian Produk */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .items-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 4px;
            border: 1px solid #1e293b;
            letter-spacing: 0.3px;
        }
        .items-table td {
            padding: 5px 4px;
            font-size: 7.5pt;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }
        .row-alt {
            background-color: #f8fafc;
        }

        /* Helpers */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .text-primary { color: #1e3a8a; }
        .text-success { color: #16a34a; }
        .text-danger { color: #dc2626; }
        .text-muted { color: #64748b; }

        /* Badges */
        .tag-pill {
            display: inline-block;
            padding: 2px 6px;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 3px;
        }
        .tag-green {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }
        .tag-yellow {
            background-color: #fef9c3;
            color: #854d0e;
            border: 1px solid #fde047;
        }
        .tag-red {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        /* 5. Signatures Grid */
        .sig-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-top: 10px;
            page-break-inside: avoid;
        }
        .sig-box {
            width: 33.33%;
            border: 1px solid #cbd5e1;
            background-color: #fafafa;
            padding: 8px 4px 6px 4px;
            text-align: center;
            vertical-align: top;
        }
        .sig-role {
            font-size: 7.5pt;
            font-weight: bold;
            color: #334155;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .sig-qr-container {
            height: 44px;
            margin: 2px auto;
        }
        .sig-name {
            font-size: 8.5pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 4px;
            text-decoration: underline;
        }
        .sig-title {
            font-size: 6.5pt;
            color: #64748b;
        }

        /* 6. Footer Legalitas */
        .footer-table {
            width: 100%;
            margin-top: 12px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 5px;
            font-size: 6.5pt;
            color: #64748b;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>

    <!-- 1. KOP SURAT RESMI PERUSAHAAN -->
    <table class="kop-table">
        <tr>
            <td style="width: 62%; vertical-align: middle;">
                <div class="company-title">{{ $companyName }}</div>
                <div class="company-desc">
                    {{ $owner->address ?? ($adjustment->branch->address ?? 'Pusat Distribusi & Operasional Retail Terpadu') }}<br>
                    Telepon: {{ $owner->phone ?? ($adjustment->branch->phone ?? '-') }} &bull; Email: {{ $owner->email ?? ($adjustment->branch->email ?? '-') }}
                </div>
            </td>
            <td style="width: 38%; text-align: right; vertical-align: middle;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="text-align: right; vertical-align: middle; padding-right: 6px;">
                            <div class="sk-card-title">SK PENETAPAN HARGA RESMI</div>
                            <div class="sk-card-number">{{ $adjustment->adjustment_number }}</div>
                            <div class="sk-card-date">Ditetapkan: {{ date('d/m/Y', strtotime($adjustment->created_at)) }}</div>
                        </td>
                        <td style="width: 48px; text-align: right; vertical-align: middle;">
                            @if(!empty($documentQrCode))
                                <img src="data:image/svg+xml;base64,{{ $documentQrCode }}" style="width: 46px; height: 46px; border: 1px solid #cbd5e1; padding: 1px; background: #fff;" alt="QR SK" />
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- 2. PARAMETER METADATA KEPUTUSAN SK -->
    <table class="meta-box">
        <tr>
            <td class="meta-lbl">Judul Kebijakan</td>
            <td class="meta-sep">:</td>
            <td class="meta-val font-bold">{{ $adjustment->title }}</td>
            <td class="meta-lbl">Target Unit / Toko</td>
            <td class="meta-sep">:</td>
            <td class="meta-val font-bold">{{ $targetBranchName }}</td>
        </tr>
        <tr>
            <td class="meta-lbl">Tanggal Efektif</td>
            <td class="meta-sep">:</td>
            <td class="meta-val font-bold text-primary">{{ date('d F Y', strtotime($adjustment->effective_date)) }}</td>
            <td class="meta-lbl">Aturan Batch Fisik</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">{{ $batchRuleLabel }}</td>
        </tr>
        <tr>
            <td class="meta-lbl">Status Pengesahan</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">
                @if($adjustment->status === 'approved')
                    <span class="tag-pill tag-green">&#10003; DISETUJUI & DITERAPKAN (SAH)</span>
                @elseif($adjustment->status === 'draft')
                    <span class="tag-pill tag-yellow">&#9679; DRAFT USULAN (BELUM SAH)</span>
                @else
                    <span class="tag-pill tag-red">&#10007; DITOLAK (REJECTED)</span>
                @endif
            </td>
            <td class="meta-lbl">Alasan / Dasar</td>
            <td class="meta-sep">:</td>
            <td class="meta-val" style="font-style: italic; color: #475569;">{{ $adjustment->reason ?: 'Penyesuaian berkala harga pasar & margin operasional' }}</td>
        </tr>
    </table>

    <!-- 3. REKAPITULASI METRIK PERUBAHAN HARGA -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-cell">
                <div class="kpi-title">TOTAL PRODUK</div>
                <div class="kpi-num text-primary">{{ $totalItems }} <span style="font-size: 7.5pt; font-weight: normal; color: #64748b;">SKU</span></div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-title">PRODUK NAIK HARGA</div>
                <div class="kpi-num text-success">{{ $totalItemsIncreased }} <span style="font-size: 7.5pt; font-weight: normal; color: #64748b;">SKU</span></div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-title">PRODUK TURUN HARGA</div>
                <div class="kpi-num text-danger">{{ $totalItemsDecreased }} <span style="font-size: 7.5pt; font-weight: normal; color: #64748b;">SKU</span></div>
            </td>
            <td class="kpi-cell">
                <div class="kpi-title">AKUMULASI SELISIH</div>
                <div class="kpi-num {{ $totalPriceIncrease >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $totalPriceIncrease >= 0 ? '+' : '' }}Rp {{ number_format($totalPriceIncrease, 0, ',', '.') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- 4. TABEL RINCIAN PRODUK & STRUKTUR HARGA BARU -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 4%; text-align: center;">NO</th>
                <th style="width: 14%; text-align: center;">SCAN / SKU</th>
                <th style="width: 29%; text-align: left;">NAMA PRODUK & KATEGORI</th>
                <th style="width: 7%; text-align: center;">SATUAN</th>
                <th style="width: 13%; text-align: right;">HARGA LAMA</th>
                <th style="width: 14%; text-align: right;">HARGA BARU</th>
                <th style="width: 9%; text-align: right;">MIN. NEGO</th>
                <th style="width: 10%; text-align: right;">SELISIH</th>
            </tr>
        </thead>
        <tbody>
            @forelse($adjustment->items as $index => $item)
                @php
                    $diff = (float)$item->new_price - (float)$item->old_price;
                    $diffPct = $item->old_price > 0 ? ($diff / $item->old_price) * 100 : 0;
                    $sku = ($item->product && !empty($item->product->sku)) ? $item->product->sku : ('PRD-' . $item->product_id);
                    $prodName = ($item->product && !empty($item->product->name)) ? $item->product->name : ('Produk #' . $item->product_id);
                    $catName = ($item->product && $item->product->category && !empty($item->product->category->name)) ? $item->product->category->name : 'Umum';
                    $unitName = ($item->product && !empty($item->product->unit)) ? $item->product->unit : 'Pcs';
                    $hasQr = isset($itemQrCodes[$item->id]) && !empty($itemQrCodes[$item->id]);
                    $isEven = ($index % 2 === 1);
                @endphp
                <tr class="{{ $isEven ? 'row-alt' : '' }}">
                    <td class="text-center text-muted">{{ $index + 1 }}</td>
                    <td class="text-center" style="padding: 3px 2px;">
                        @if($hasQr)
                            <img src="data:image/svg+xml;base64,{{ $itemQrCodes[$item->id] }}" style="width: 22px; height: 22px; vertical-align: middle; display: inline-block; margin-right: 3px;" alt="QR" />
                        @endif
                        <span class="font-bold" style="font-size: 7.5pt; color: #0f172a; vertical-align: middle;">
                            {{ $sku }}
                        </span>
                        @if($item->product && !empty($item->product->barcode) && $item->product->barcode !== $sku)
                            <div style="font-size: 6pt; color: #64748b;">
                                {{ $item->product->barcode }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <div class="font-bold" style="color: #0f172a; font-size: 8pt;">
                            {{ $prodName }}
                        </div>
                        <div style="font-size: 6.5pt; color: #64748b; margin-top: 1px;">
                            {{ $catName }}
                            @if($item->product && !empty($item->product->brand))
                                &bull; Merk: {{ $item->product->brand }}
                            @endif
                            @if(!empty($item->notes))
                                &bull; <em>{{ $item->notes }}</em>
                            @endif
                        </div>
                    </td>
                    <td class="text-center font-bold" style="color: #334155; font-size: 7.5pt;">
                        {{ $unitName }}
                    </td>
                    <td class="text-right text-muted" style="font-size: 7.5pt;">
                        Rp {{ number_format($item->old_price, 0, ',', '.') }}
                    </td>
                    <td class="text-right font-bold" style="color: #0f172a; font-size: 8pt;">
                        Rp {{ number_format($item->new_price, 0, ',', '.') }}
                        @if($diff > 0)
                            <div class="text-success" style="font-size: 6.5pt; font-weight: bold;">+{{ number_format($diffPct, 1) }}% (+Rp {{ number_format($diff, 0, ',', '.') }})</div>
                        @elseif($diff < 0)
                            <div class="text-danger" style="font-size: 6.5pt; font-weight: bold;">{{ number_format($diffPct, 1) }}% (-Rp {{ number_format(abs($diff), 0, ',', '.') }})</div>
                        @else
                            <div class="text-muted" style="font-size: 6.5pt;">Tetap</div>
                        @endif
                    </td>
                    <td class="text-right" style="color: #b45309; font-weight: 600; font-size: 7.5pt;">
                        @if(!empty($item->new_min_nego_price) && $item->new_min_nego_price > 0)
                            Rp {{ number_format($item->new_min_nego_price, 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right font-bold" style="font-size: 7.5pt;">
                        @if($diff > 0)
                            <span class="text-success">+Rp {{ number_format($diff, 0, ',', '.') }}</span>
                        @elseif($diff < 0)
                            <span class="text-danger">-Rp {{ number_format(abs($diff), 0, ',', '.') }}</span>
                        @else
                            <span class="text-muted">Rp 0</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted" style="padding: 16px;">
                        Tidak ada rincian produk dalam dokumen penetapan harga ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- 5. TANDA TANGAN DIGITAL 3 PIHAK OTORISASI RESMI -->
    <table class="sig-table">
        <tr>
            <td class="sig-box">
                <div class="sig-role">Dibuat / Diusulkan:</div>
                <div class="sig-qr-container">
                    @if(!empty($creatorQrCode))
                        <img src="data:image/svg+xml;base64,{{ $creatorQrCode }}" style="width: 40px; height: 40px;" alt="QR TTD Pembuat" />
                    @endif
                </div>
                <div style="font-size: 5.5pt; font-weight: bold; color: #2563eb;">[TERTANDA DIGITAL]</div>
                <div class="sig-name">{{ $creatorName }}</div>
                <div class="sig-title">Analis Harga / Staf Inventory</div>
            </td>
            <td class="sig-box">
                <div class="sig-role">Diperiksa / Diverifikasi:</div>
                <div class="sig-qr-container">
                    @if(!empty($reviewerQrCode))
                        <img src="data:image/svg+xml;base64,{{ $reviewerQrCode }}" style="width: 40px; height: 40px;" alt="QR TTD Pemeriksa" />
                    @endif
                </div>
                <div style="font-size: 5.5pt; font-weight: bold; color: #2563eb;">[TERVERIFIKASI SAH]</div>
                <div class="sig-name">Kepala Operasional Toko</div>
                <div class="sig-title">Manajer Operasional Cabang</div>
            </td>
            <td class="sig-box">
                <div class="sig-role">Disahkan & Ditetapkan:</div>
                <div class="sig-qr-container">
                    @if(!empty($approverQrCode))
                        <img src="data:image/svg+xml;base64,{{ $approverQrCode }}" style="width: 40px; height: 40px;" alt="QR TTD Pengesah" />
                    @endif
                </div>
                <div style="font-size: 5.5pt; font-weight: bold; color: #16a34a;">[DISAHKAN RESMI]</div>
                <div class="sig-name">{{ $approverName }}</div>
                <div class="sig-title">Owner / Direksi {{ $companyName }}</div>
            </td>
        </tr>
    </table>

    <!-- 6. CATATAN KAKI KEABSAHAN HUKUM SK -->
    <table class="footer-table">
        <tr>
            <td style="width: 70%; text-align: left; vertical-align: middle;">
                * Surat Keputusan (SK) ini sah dan mengikat seluruh kasir POS & katalog inventori sejak tanggal efektif.<br>
                ID Verifikasi Dokumen: <span class="font-bold" style="color: #0f172a;">{{ $verificationUuid }}</span> &bull; Dicetak pada: {{ $printedAt }}
            </td>
            <td style="width: 30%; text-align: right; vertical-align: middle;">
                <strong>DOKUMEN RESMI OTENTIK</strong><br>
                {{ $companyName }}
            </td>
        </tr>
    </table>

</body>
</html>
