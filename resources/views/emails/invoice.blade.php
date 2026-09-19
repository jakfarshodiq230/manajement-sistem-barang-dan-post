<!DOCTYPE html>
<html>
<head>
    <title>Tagihan (Invoice)</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .header { text-align: center; border-bottom: 2px solid #f0f0f0; padding-bottom: 20px; margin-bottom: 20px; }
        .header img { max-height: 60px; }
        .header h1 { margin: 10px 0 0; font-size: 24px; color: #444; }
        .content { margin-bottom: 30px; }
        .footer { font-size: 12px; color: #777; text-align: center; border-top: 1px solid #eee; padding-top: 20px; }
        .alert { background-color: #f9f9f9; padding: 15px; border-left: 4px solid #666CFF; margin: 20px 0; }
    </style>
</head>
<body>
    @php
        $owner = \App\Models\Owner::first();
        $logoPath = null;
        if ($owner && $owner->logo) {
            $path = storage_path('app/public/' . $owner->logo);
            if (file_exists($path)) {
                $logoPath = $path;
            }
        }
        if (!$logoPath) {
            $fallback = public_path('logo.png');
            if (file_exists($fallback)) {
                $logoPath = $fallback;
            }
        }
        $companyName = $owner ? $owner->name : 'PT. Pagaruyung Dieselindo Perkasa';
    @endphp

    <div class="container">
        <div class="header">
            @if($logoPath)
                <img src="{{ $message->embed($logoPath) }}" alt="{{ $companyName }} Logo">
            @else
                <div style="font-weight:bold; font-size:16px;">LOGO</div>
            @endif
            <h1>{{ $companyName }}</h1>
        </div>

        <div class="content">
            <p>Yth. <strong>{{ $data['customer_name'] ?? 'Pelanggan' }}</strong>,</p>
            
            <p>Terlampir adalah dokumen Tagihan (Invoice) dengan nomor referensi <strong>{{ $data['reference_no'] ?? '-' }}</strong>.</p>
            
            <div class="alert">
                Total Tagihan Pembayaran: <br>
                <strong style="font-size: 18px;">Rp {{ number_format($data['amount'] ?? 0, 0, ',', '.') }}</strong>
            </div>
            
            <p>Silakan periksa lampiran dokumen PDF untuk rincian lebih lanjut mengenai tagihan ini. Harap melakukan pembayaran sesuai dengan instruksi yang tertera pada dokumen terlampir.</p>
            
            <p>Terima kasih atas kerja sama dan kepercayaan Anda terhadap layanan kami.<br><br>
            Hormat kami,<br>
            <strong>Tim {{ $companyName }}</strong></p>
        </div>

        <div class="footer">
            <p><em>*Pesan ini dikirimkan secara otomatis oleh sistem. Mohon tidak membalas (DO NOT REPLY) email ini.</em></p>
            <p>&copy; {{ date('Y') }} {{ $companyName }}. Hak cipta dilindungi.</p>
        </div>
    </div>
</body>
</html>
