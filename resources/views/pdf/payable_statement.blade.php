@extends('pdf.layout')

@section('title', 'Rekap Tagihan Hutang ' . $document->statement_number)
@section('document_title', 'REKAPITULASI TAGIHAN BULANAN & FAKTUR HUTANG')
@section('document_number', $document->statement_number)
@section('document_date', \Carbon\Carbon::parse($document->created_at ?? now())->format('d-m-Y'))

@section('content')
@php
    $supplier = $document->supplier ?? null;
    $branch = $document->branch ?? $branch ?? null;
    $owner = $branch->owner ?? null;
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
        width: 120px;
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
        padding: 3px 4px;
    }
    .summary-box {
        border: 1.5px solid #222;
        background-color: #fafafa;
        padding: 8px 10px;
        border-radius: 4px;
    }
</style>

<!-- Informasi Dua Kolom: Identitas Faktur & Tujuan Penerima -->
<table class="faktur-meta-box">
    <tr>
        <!-- Kolom Kiri: Detail Supplier & Tagihan Bulanan -->
        <td style="width: 52%; border-right: 1px solid #eee; padding-right: 12px;">
            <table style="width: 100%;">
                <tr>
                    <td class="faktur-meta-label">SUPPLIER / VENDOR</td>
                    <td>: <strong>{{ strtoupper($supplier->name ?? 'PT. CAPELLA PATRIA UTAMA') }}</strong></td>
                </tr>
                <tr>
                    <td class="faktur-meta-label">NO. REKAP TAGIHAN</td>
                    <td>: <strong style="font-family: monospace; font-size: 12px; color: #1a237e;">{{ $document->statement_number }}</strong></td>
                </tr>
                <tr>
                    <td class="faktur-meta-label">PERIODE CUTOFF</td>
                    <td>: <strong>{{ $document->period_month }}</strong> ({{ \Carbon\Carbon::parse($document->period_start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($document->period_end_date)->format('d/m/Y') }})</td>
                </tr>
                <tr>
                    <td class="faktur-meta-label">JATUH TEMPO</td>
                    <td>: {{ $document->due_date ? \Carbon\Carbon::parse($document->due_date)->format('d-m-Y') : '-' }}</td>
                </tr>
                <tr>
                    <td class="faktur-meta-label">STATUS TAGIHAN</td>
                    <td>: <strong style="text-transform: uppercase; color: {{ $document->status === 'paid' ? '#2e7d32' : ($document->status === 'partial' ? '#f57c00' : '#c62828') }};">{{ $document->status === 'paid' ? 'LUNAS SEMUA' : ($document->status === 'partial' ? 'DICICIL SEBAGIAN' : 'BELUM DIBAYAR') }}</strong></td>
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

<!-- TABEL 1: DAFTAR BARANG DALAM TAGIHAN PERIODE INI -->
<div style="font-weight: bold; font-size: 11px; margin-bottom: 4px; text-transform: uppercase; color: #1a237e;">
    1. Rincian Barang & Faktur Pembelian ({{ count($document->payables) }} Faktur)
</div>
<table class="faktur-table">
    <thead>
        <tr>
            <th style="width: 4%; text-align: center;">NO</th>
            <th style="width: 32%;">KODEPART / NAMA BARANG</th>
            <th style="width: 20%;">NO. FAKTUR & PO</th>
            <th style="width: 10%; text-align: center;">QTY</th>
            <th style="width: 16%; text-align: right;">HARGA MODAL</th>
            <th style="width: 18%; text-align: right;">TOTAL NILAI</th>
        </tr>
    </thead>
    <tbody>
        @php $no = 1; @endphp
        @forelse($document->payables as $payable)
            @php
                $grItems = $payable->goodsReceipt->items ?? [];
                $poItems = $payable->purchaseOrder->items ?? [];
            @endphp
            @if(count($grItems) > 0)
                @foreach($grItems as $item)
                    @php
                        $product = $item->productBranch->product ?? null;
                        $qty = (float) ($item->qty_received ?: 1);
                        $unit = $item->unit_name ?: ($product->unit ?? 'PCS');
                        $price = (float) ($item->net_unit_price ?: ($item->gross_price ?: ($item->price ?: 0)));
                        $subtotal = (float) ($item->price ?: ($price * $qty));
                    @endphp
                    <tr>
                        <td style="text-align: center;">{{ $no++ }}</td>
                        <td>
                            <strong>{{ $product->code ?? '-' }}</strong><br>
                            <span style="font-size: 10px; color: #222;">{{ $product->name ?? 'Item Tanpa Nama' }}</span>
                        </td>
                        <td style="font-size: 10px;">
                            <strong>{{ $payable->invoice_number_supplier ?: $payable->payable_number }}</strong><br>
                            <span style="color: #666;">PO: {{ $payable->purchaseOrder->po_number ?? '-' }}</span>
                        </td>
                        <td style="text-align: center; font-weight: bold;">
                            {{ number_format($qty, 0, ',', '.') }} {{ $unit }}
                        </td>
                        <td style="text-align: right; font-family: monospace;">
                            {{ number_format($price, 0, ',', '.') }}
                        </td>
                        <td style="text-align: right; font-family: monospace; font-weight: bold;">
                            {{ number_format($subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td style="text-align: center;">{{ $no++ }}</td>
                    <td>
                        <strong>{{ $payable->payable_number }}</strong><br>
                        <span style="font-size: 10px;">Tagihan Faktur Penerimaan Barang</span>
                    </td>
                    <td style="font-size: 10px;">
                        <strong>{{ $payable->invoice_number_supplier ?: '-' }}</strong><br>
                        <span style="color: #666;">Tgl: {{ \Carbon\Carbon::parse($payable->invoice_date)->format('d/m/Y') }}</span>
                    </td>
                    <td style="text-align: center; font-weight: bold;">1 Faktur</td>
                    <td style="text-align: right; font-family: monospace;">{{ number_format($payable->total_amount, 0, ',', '.') }}</td>
                    <td style="text-align: right; font-family: monospace; font-weight: bold;">{{ number_format($payable->total_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
        @empty
            <tr>
                <td colspan="6" class="text-center" style="padding: 15px;">Tidak ada rincian barang</td>
            </tr>
        @endforelse
    </tbody>
</table>

<!-- TABEL 2: RIWAYAT PEMBAYARAN / CICILAN -->
@if(count($document->payments) > 0)
<div style="font-weight: bold; font-size: 11px; margin-top: 10px; margin-bottom: 4px; text-transform: uppercase; color: #2e7d32;">
    2. Riwayat Pembayaran & Cicilan yang Telah Dilakukan ({{ count($document->payments) }} Transaksi)
</div>
<table class="faktur-table">
    <thead>
        <tr>
            <th style="width: 5%; text-align: center;">NO</th>
            <th style="width: 25%;">NO. BUKTI KAS</th>
            <th style="width: 20%;">TGL BAYAR</th>
            <th style="width: 30%;">METODE & REKENING BANK</th>
            <th style="width: 20%; text-align: right;">NOMINAL DIBAYAR</th>
        </tr>
    </thead>
    <tbody>
        @foreach($document->payments as $pIndex => $payment)
            <tr>
                <td style="text-align: center;">{{ $pIndex + 1 }}</td>
                <td><strong style="font-family: monospace; color: #1a237e;">{{ $payment->payment_number }}</strong></td>
                <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y') }}</td>
                <td>
                    <strong>{{ $payment->payment_method === 'bank_transfer' ? 'Transfer Bank' : 'Kas Tunai Toko' }}</strong>
                    @if($payment->bankAccount)
                        <br><span style="font-size: 9.5px; color: #555;">{{ $payment->bankAccount->bank_name }} ({{ $payment->bankAccount->account_number }})</span>
                    @endif
                    @if($payment->reference_number)
                        <span style="font-size: 9.5px; color: #555;">Ref: {{ $payment->reference_number }}</span>
                    @endif
                </td>
                <td style="text-align: right; font-family: monospace; font-weight: bold; color: #2e7d32;">
                    Rp. {{ number_format($payment->amount, 0, ',', '.') }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif

<!-- Bagian Catatan & Ringkasan Perhitungan Keuangan / Hutang -->
<table style="width: 100%; margin-top: 5px;">
    <tr>
        <!-- Catatan & Keterangan Tambahan -->
        <td style="width: 48%; vertical-align: top; padding-right: 15px;">
            <div style="font-size: 10.5px; line-height: 1.5; color: #333;">
                <strong>* Catatan Tagihan:</strong> {{ $document->notes ?: 'Rekapitulasi tagihan bulanan supplier berdasarkan siklus cutoff transaksi toko.' }}<br>
                <span style="color: #666; font-size: 10px;">* Seluruh mutasi pembayaran kas/bank diverifikasi secara otomatis dalam sistem.</span>
            </div>
        </td>

        <!-- Tabel Ringkasan Nilai Total -->
        <td style="width: 52%; vertical-align: top;">
            <div class="summary-box">
                <table class="summary-table">
                    <tr>
                        <td style="font-weight: 500;">TOTAL PEMBELIAN BULANAN</td>
                        <td style="text-align: right; font-family: monospace; font-weight: bold;">
                            Rp. {{ number_format($document->total_purchases_amount ?: $document->total_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @if((float)$document->total_returns_deduction > 0)
                    <tr>
                        <td style="color: #c62828;">POTONGAN RETUR SUPPLIER</td>
                        <td style="text-align: right; font-family: monospace; color: #c62828;">
                            - Rp. {{ number_format($document->total_returns_deduction, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endif
                    <tr style="border-top: 1px solid #ddd; border-bottom: 1px solid #ddd;">
                        <td style="font-weight: bold; font-size: 11.5px; padding: 4px 0;">TOTAL KEWAJIBAN TAGIHAN</td>
                        <td style="text-align: right; font-family: monospace; font-weight: bold; font-size: 11.5px; color: #1a237e; padding: 4px 0;">
                            Rp. {{ number_format($document->total_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="color: #2e7d32; font-weight: 500;">SUDAH DIBAYAR / DICICIL</td>
                        <td style="text-align: right; font-family: monospace; font-weight: bold; color: #2e7d32;">
                            Rp. {{ number_format($document->paid_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr style="border-top: 1.5px solid #222;">
                        <td style="font-weight: bold; font-size: 12px; color: #c62828; padding: 4px 0;">SISA KEWAJIBAN HUTANG</td>
                        <td style="text-align: right; font-family: monospace; font-weight: bold; font-size: 13px; color: #c62828; padding: 4px 0;">
                            Rp. {{ number_format($document->remaining_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>
@endsection
