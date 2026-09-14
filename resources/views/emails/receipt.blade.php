<!DOCTYPE html>
<html>
<head>
    <title>Kuitansi Pembayaran</title>
</head>
<body>
    <h2>Halo {{ $data['customer_name'] ?? 'Pelanggan' }},</h2>
    
    <p>Terima kasih atas pembayaran Anda. Terlampir adalah dokumen Kuitansi Pembayaran dengan nomor referensi <strong>{{ $data['reference_no'] ?? '-' }}</strong>.</p>
    
    <p>Jumlah dibayar: <strong>Rp {{ number_format($data['amount'] ?? 0, 0, ',', '.') }}</strong></p>
    
    <p>Silakan periksa lampiran PDF untuk rincian lebih lanjut.</p>
    
    <p>Hormat kami,<br>
    Tim Pagaruyung Diesel</p>
</body>
</html>
