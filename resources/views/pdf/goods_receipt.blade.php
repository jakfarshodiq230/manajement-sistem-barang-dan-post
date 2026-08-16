@extends('pdf.layout')

@section('title', 'Tanda Terima Gudang ' . $document->receipt_number)
@section('document_title', 'TANDA TERIMA GUDANG')
@section('document_number', $document->receipt_number)
@section('document_date', \Carbon\Carbon::parse($document->date)->format('d F Y'))

@section('content')
    <div style="margin-bottom: 20px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <strong>Diterima Di:</strong><br>
                    {{ $document->purchaseOrder->branch->name ?? 'Gudang Utama' }}<br>
                    {{ $document->purchaseOrder->branch->address ?? '-' }}
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <strong>Pengirim/Supplier:</strong><br>
                    {{ $document->purchaseOrder->supplier->name ?? 'Internal / Lain-lain' }}<br>
                    <strong>Referensi PO:</strong><br>
                    {{ $document->purchaseOrder->po_number ?? '-' }}
                </td>
            </tr>
        </table>
    </div>

    <table class="content-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 50%;">Nama Barang</th>
                <th style="width: 20%; text-align: center;">Qty Diterima</th>
                <th style="width: 25%;">Kondisi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($document->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->productBranch->product->name ?? 'Item Tidak Ditemukan' }}</td>
                    <td class="text-center">{{ $item->qty_received ?? 0 }}</td>
                    <td>{{ $item->condition ?? 'Baik' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Tidak ada item</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($document->notes)
        <div style="margin-top: 20px;">
            <strong>Catatan Penerimaan:</strong>
            <p>{{ $document->notes }}</p>
        </div>
    @endif
@endsection
