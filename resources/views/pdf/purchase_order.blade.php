@extends('pdf.layout')

@section('title', 'Purchase Order ' . $document->po_number)
@section('document_title', 'PURCHASE ORDER')
@section('document_number', $document->po_number)
@section('document_date', \Carbon\Carbon::parse($document->date)->format('d F Y'))

@section('content')
    <div style="margin-bottom: 20px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <strong>Kepada Yth:</strong><br>
                    {{ $document->supplier->name ?? 'Supplier Umum' }}<br>
                    {{ $document->supplier->address ?? '-' }}<br>
                    Telp: {{ $document->supplier->phone ?? '-' }}
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <strong>Dikirim Ke:</strong><br>
                    {{ $document->branch->name ?? 'Gudang Utama' }}<br>
                    {{ $document->branch->address ?? '-' }}
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

    @if($document->notes)
        <div style="margin-top: 20px;">
            <strong>Catatan:</strong>
            <p>{{ $document->notes }}</p>
        </div>
    @endif
@endsection
