@extends('pdf.layout')

@section('title', 'Struk Pembelian ' . ($document->invoice_number ?? $document->id))
@section('document_title', 'STRUK PEMBELIAN')
@section('document_number', $document->invoice_number ?? $document->id)
@section('document_date', \Carbon\Carbon::parse($document->created_at)->format('d F Y H:i'))

@section('content')
    <div style="margin-bottom: 20px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <strong>Toko/Cabang:</strong><br>
                    {{ $document->branch->name ?? 'Toko Utama' }}<br>
                    {{ $document->branch->address ?? '-' }}
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <strong>Kasir:</strong><br>
                    {{ $document->user->name ?? 'Kasir' }}<br>
                    <strong>Pelanggan:</strong><br>
                    {{ $document->customer_name ?? 'Umum' }}
                </td>
            </tr>
        </table>
    </div>

    <table class="content-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 45%;">Nama Barang</th>
                <th style="width: 15%; text-align: center;">Qty</th>
                <th style="width: 15%; text-align: right;">Harga Satuan</th>
                <th style="width: 20%; text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($document->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->product->name ?? 'Item Tidak Ditemukan' }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">Tidak ada item</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right" style="font-weight: bold;">TOTAL:</td>
                <td class="text-right" style="font-weight: bold;">Rp {{ number_format($document->total_amount, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
