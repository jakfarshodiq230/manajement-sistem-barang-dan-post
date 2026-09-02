@extends('pdf.layout')

@section('title', 'Faktur / Tanda Terima Gudang ' . ($document->invoice_number_supplier ?: $document->receipt_number))
@section('document_title', 'FAKTUR / TANDA TERIMA BARANG')
@section('document_number', $document->receipt_number)
@section('document_date', \Carbon\Carbon::parse($document->date)->format('d-m-Y'))

@section('content')
@php
    $supplier = $document->purchaseOrder->supplier ?? null;
    $branch = $document->purchaseOrder->branch ?? $branch ?? null;
    $owner = $branch->owner ?? null;
    
    // Financial calculations
    $subtotalBruto = (float) ($document->subtotal_bruto ?: 0);
    $extraDiscount = (float) ($document->extra_discount ?: 0);
    $totalAmount = (float) ($document->total_amount ?: 0);
    $dppAmount = (float) ($document->dpp_amount ?: 0);
    $taxAmount = (float) ($document->tax_amount ?: 0);
    $taxPercentage = $document->tax_percentage ?? 11;
    
    // If not filled on parent, calculate from items
    if ($subtotalBruto == 0 && count($document->items) > 0) {
        foreach ($document->items as $it) {
            $qty = (float) ($it->qty_received ?: 1);
            $gross = (float) ($it->gross_price ?: $it->net_unit_price ?: $it->price ?: 0);
            $net = (float) ($it->net_unit_price ?: $gross);
            $subtotalBruto += ($gross * $qty);
            $totalAmount += ($net * $qty);
        }
        if ($dppAmount == 0) $dppAmount = $totalAmount / (1 + ($taxPercentage / 100));
        if ($taxAmount == 0) $taxAmount = $totalAmount - $dppAmount;
    }
@endphp

<style>
    .faktur-meta-box {
        width: 100%;
        margin-bottom: 12px;
        font-size: 11px;
        line-height: 1.4;
    }
    .faktur-meta-box td {
        vertical-align: top;
        padding: 4px;
    }
    .faktur-meta-label {
        width: 110px;
        font-weight: bold;
        color: #333;
    }
    .faktur-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
        font-size: 10.5px;
    }
    .faktur-table th {
        border-top: 1.5px solid #222;
        border-bottom: 1.5px solid #222;
        padding: 6px 4px;
        font-weight: bold;
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 10px;
    }
    .faktur-table td {
        border-bottom: 1px dashed #ccc;
        padding: 5px 4px;
        vertical-align: middle;
    }
    .summary-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
    }
    .summary-table td {
        padding: 2px 4px;
    }
    .summary-box {
        border: 1px solid #333;
        background-color: #fafafa;
        padding: 6px 8px;
        border-radius: 4px;
    }
</style>

<!-- Informasi Dua Kolom: Identitas Faktur & Tujuan Penerima -->
<table class="faktur-meta-box">
    <tr>
        <!-- Kolom Kiri: Detail Faktur & Supplier -->
        <td style="width: 52%; border-right: 1px solid #eee; padding-right: 12px;">
            <table style="width: 100%;">
                <tr>
                    <td class="faktur-meta-label">SUPPLIER</td>
                    <td>: <strong>{{ strtoupper($supplier->name ?? 'PT. CAPELLA PATRIA UTAMA') }}</strong></td>
                </tr>
                <tr>
                    <td class="faktur-meta-label">NOMOR FAKTUR</td>
                    <td>: <strong style="font-family: monospace; font-size: 12px; color: #1a237e;">{{ $document->invoice_number_supplier ?: ($document->receipt_number ?: '-') }}</strong></td>
                </tr>
                <tr>
                    <td class="faktur-meta-label">TGL / JTH TEMPO</td>
                    <td>: {{ \Carbon\Carbon::parse($document->date)->format('d-m-Y') }} / {{ $document->due_date ? \Carbon\Carbon::parse($document->due_date)->format('d-m-Y') : \Carbon\Carbon::parse($document->date)->addDays(30)->format('d-m-Y') }}</td>
                </tr>
                <tr>
                    <td class="faktur-meta-label">KODE SALES</td>
                    <td>: {{ $document->sales_name ?: ($supplier->pic_name ?? 'REZEKI GENESIS') }}</td>
                </tr>
                <tr>
                    <td class="faktur-meta-label">NO. REF PO / GR</td>
                    <td>: {{ $document->purchaseOrder->po_number ?? '-' }} (GR: {{ $document->receipt_number }})</td>
                </tr>
            </table>
        </td>

        <!-- Kolom Kanan: Identitas Toko / Cabang Pembeli -->
        <td style="width: 48%; padding-left: 12px;">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 70px; font-weight: bold;">KEPADA</td>
                    <td>: <strong>{{ strtoupper($owner->name ?? $branch->name ?? 'PT. PAGARUYUNG MITRA PERSADA') }}</strong></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">CABANG</td>
                    <td>: {{ strtoupper($branch->name ?? 'GUDANG UTAMA') }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">ALAMAT</td>
                    <td>: {{ $branch->address ?? ($owner->address ?? '-') }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">KONTAK</td>
                    <td>: {{ $branch->phone ?? ($owner->phone ?? '-') }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Tabel Rincian Barang & Diskon Bertingkat -->
<table class="faktur-table">
    <thead>
        <tr>
            <th style="width: 4%; text-align: center;">NO</th>
            <th style="width: 32%;">KODEPART / NAMA BRG.</th>
            <th style="width: 10%; text-align: center;">QTY</th>
            <th style="width: 14%; text-align: right;">HRG / @</th>
            <th style="width: 14%; text-align: center;">DISCOUNT</th>
            <th style="width: 13%; text-align: right;">NETTO</th>
            <th style="width: 13%; text-align: right;">JUMLAH RP</th>
        </tr>
    </thead>
    <tbody>
        @forelse($document->items as $index => $item)
            @php
                $product = $item->productBranch->product ?? null;
                $qty = (float) ($item->qty_received ?: 1);
                $unit = $item->unit_name ?: ($product->unit ?? 'PCS');
                
                $grossPrice = (float) ($item->gross_price ?: $item->net_unit_price ?: 0);
                $netUnitPrice = (float) ($item->net_unit_price ?: $grossPrice);
                
                // Parse and calculate discount
                $d1 = (float) ($item->discount_percent_1 ?: 0);
                $d2 = (float) ($item->discount_percent_2 ?: 0);
                $d3 = (float) ($item->discount_percent_3 ?: 0);
                if ($item->discount_string && $d1 == 0 && $d2 == 0) {
                    $parts = array_map('floatval', explode('+', $item->discount_string));
                    $d1 = $parts[0] ?? 0;
                    $d2 = $parts[1] ?? 0;
                    $d3 = $parts[2] ?? 0;
                }

                if ($d1 > 0 || $d2 > 0 || $d3 > 0 || (float)$item->discount_amount > 0) {
                    $cur = $grossPrice;
                    if ($d1 > 0) $cur *= (1 - ($d1 / 100));
                    if ($d2 > 0) $cur *= (1 - ($d2 / 100));
                    if ($d3 > 0) $cur *= (1 - ($d3 / 100));
                    if ((float)$item->discount_amount > 0 && $qty > 0) $cur -= ((float)$item->discount_amount / $qty);
                    $netUnitPrice = max(0, round($cur));
                }
                
                // Subtotal per row
                $rowSubtotal = round($netUnitPrice * $qty);
                
                // Discount string representation
                $discStr = $item->discount_string;
                if (!$discStr) {
                    $discs = [];
                    if (!empty($item->discount_percent_1) && $item->discount_percent_1 > 0) $discs[] = number_format($item->discount_percent_1, 2) . '%';
                    if (!empty($item->discount_percent_2) && $item->discount_percent_2 > 0) $discs[] = number_format($item->discount_percent_2, 2) . '%';
                    if (!empty($item->discount_percent_3) && $item->discount_percent_3 > 0) $discs[] = number_format($item->discount_percent_3, 2) . '%';
                    $discStr = count($discs) > 0 ? implode(' + ', $discs) : ($item->discount_amount > 0 ? 'Rp ' . number_format($item->discount_amount, 0, ',', '.') : '-');
                }
            @endphp
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $product->code ?? '-' }}</strong><br>
                    <span style="font-size: 10px; color: #222;">{{ $product->name ?? 'Barang Tanpa Nama' }}</span>
                </td>
                <td style="text-align: center; font-weight: bold;">
                    {{ number_format($qty, 0, ',', '.') }} {{ $unit }}
                </td>
                <td style="text-align: right; font-family: monospace;">
                    {{ number_format($grossPrice, 0, ',', '.') }}
                </td>
                <td style="text-align: center; font-size: 9.5px; color: #444;">
                    {{ $discStr }}
                </td>
                <td style="text-align: right; font-family: monospace; font-weight: 500;">
                    {{ number_format($netUnitPrice, 0, ',', '.') }}
                </td>
                <td style="text-align: right; font-family: monospace; font-weight: bold;">
                    {{ number_format($rowSubtotal, 0, ',', '.') }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px;">Tidak ada item penerimaan barang</td>
            </tr>
        @endforelse
    </tbody>
</table>

<!-- Bagian Catatan & Ringkasan Perhitungan Keuangan / Pajak -->
<table style="width: 100%; margin-top: 5px;">
    <tr>
        <!-- Catatan & Keterangan Tambahan -->
        <td style="width: 50%; vertical-align: top; padding-right: 15px;">
            <div style="font-size: 10px; line-height: 1.5; color: #333;">
                <strong>* Ket.:</strong> {{ $document->notes ?: 'Barang telah diterima dalam kondisi baik, lengkap, dan sesuai pesanan.' }}<br>
                <span style="color: #666;">* Harga sudah termasuk perhitungan PPN & diskon supplier yang berlaku.</span>
            </div>
        </td>

        <!-- Tabel Ringkasan Nilai Total -->
        <td style="width: 50%; vertical-align: top;">
            <div class="summary-box">
                <table class="summary-table">
                    <tr>
                        <td style="font-weight: 500;">JUMLAH HARGA JUAL</td>
                        <td style="text-align: right; font-family: monospace; font-weight: bold;">
                            Rp. {{ number_format($subtotalBruto > 0 ? $subtotalBruto : $totalAmount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @if($extraDiscount > 0)
                    <tr>
                        <td style="color: #c62828;">EXTRA DISCOUNT</td>
                        <td style="text-align: right; font-family: monospace; color: #c62828;">
                            - Rp. {{ number_format($extraDiscount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endif
                    <tr style="border-top: 1px solid #ddd; border-bottom: 1px solid #ddd;">
                        <td style="font-weight: bold; font-size: 12px; padding: 4px 0;">TOTAL (Inc PPN)</td>
                        <td style="text-align: right; font-family: monospace; font-weight: bold; font-size: 12px; color: #1a237e; padding: 4px 0;">
                            Rp. {{ number_format($totalAmount, 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 10px; color: #666; padding-top: 4px;">DPP (Dasar Pengenaan Pajak)</td>
                        <td style="text-align: right; font-family: monospace; font-size: 10px; color: #666; padding-top: 4px;">
                            Rp. {{ number_format($dppAmount, 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 10px; color: #666;">PPN ({{ $taxPercentage }}%)</td>
                        <td style="text-align: right; font-family: monospace; font-size: 10px; color: #666;">
                            Rp. {{ number_format($taxAmount, 0, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>
@endsection
