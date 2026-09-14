<!DOCTYPE html>
<html>
<head>
    <title>Tagihan (Invoice)</title>
</head>
<body>
    <h2>Halo {{ $data['customer_name'] ?? 'Pelanggan' }},</h2>
    
    <p>Terlampir adalah dokumen Tagihan (Invoice) dengan nomor referensi <strong>{{ $data['reference_no'] ?? '-' }}</strong>.</p>
    
    <p>Total tagihan: <strong>Rp {{ number_format($data['amount'] ?? 0, 0, ',', '.') }}</strong></p>
    
    <p>Silakan periksa lampiran PDF untuk rincian lebih lanjut.</p>
    
    <p>Terima kasih,<br>
    Tim Pagaruyung Diesel</p>
</body>
</html>
