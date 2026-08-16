@extends('pdf.layout')

@section('title', 'Tanda Bukti ' . ($document->return_type == 'replacement' ? 'Ganti Barang' : 'Retur') . ' ' . $document->return_number)
@section('document_title', $document->return_type == 'replacement' ? 'TANDA GANTI BARANG' : 'BUKTI RETUR BARANG')
@section('document_number', $document->return_number)
@section('document_date', \Carbon\Carbon::parse($document->created_at)->format('d F Y'))

@section('content')
    <div style="margin-bottom: 20px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <strong>Cabang/Lokasi:</strong><br>
                    {{ $document->branch->name ?? 'Cabang Utama' }}<br>
                    {{ $document->branch->address ?? '-' }}
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <strong>Referensi Asal:</strong><br>
                    {{ $document->reference_type ?? '-' }} - #{{ $document->reference_id ?? '-' }}<br>
                    <strong>Tipe Pengembalian:</strong><br>
                    {{ strtoupper($document->return_type) }}
                </td>
            </tr>
        </table>
    </div>

    <table class="content-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 40%;">Nama Barang</th>
                <th style="width: 15%; text-align: center;">Qty</th>
                <th style="width: 40%;">Alasan Retur / Kondisi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($document->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->product->name ?? 'Item Tidak Ditemukan' }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td>{{ $item->reason ?? '-' }}</td>
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
            <strong>Catatan:</strong>
            <p>{{ $document->notes }}</p>
        </div>
    @endif
@endsection
