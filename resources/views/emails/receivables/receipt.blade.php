<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $subject ?? 'Bukti Penerimaan Pembayaran Piutang' }}</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 20px; color: #333; }
    .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .header { background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); color: #ffffff; padding: 28px 24px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
    .header p { margin: 6px 0 0; opacity: 0.9; font-size: 13px; }
    .content { padding: 24px; }
    .paid-banner { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; text-align: center; margin: 20px 0; }
    .paid-title { font-size: 12px; color: #15803d; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }
    .paid-amount { font-size: 26px; font-weight: 800; color: #16a34a; margin: 4px 0 0; }
    .card { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 20px; }
    .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
    .info-row:last-child { margin-bottom: 0; }
    .info-label { color: #64748b; }
    .info-value { font-weight: 600; color: #1e293b; text-align: right; }
    .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .badge-paid { background-color: #dcfce7; color: #15803d; }
    .badge-partial { background-color: #fef3c7; color: #d97706; }
    .footer { background: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>KWITANSI PEMBAYARAN PIUTANG</h1>
      <p>PT. DUMAI MANAJEMEN SISTEM POS & LOGISTIK</p>
    </div>

    <div class="content">
      <p style="font-size: 15px; margin-top: 0;">
        Kepada Yth. <strong>{{ $payment->receivable->customer->name ?? 'Pelanggan Terhormat' }}</strong>,
      </p>
      <p style="font-size: 14px; color: #475569; line-height: 1.5;">
        Terima kasih! Pembayaran cicilan/pelunasan piutang Anda telah berhasil kami terima dan dibukukan ke dalam sistem kasir kami.
      </p>

      <div class="paid-banner">
        <div class="paid-title">Nominal yang Diterima</div>
        <div class="paid-amount">Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>
      </div>

      <div class="card">
        <div class="info-row">
          <span class="info-label">No. Transaksi Kwitansi:</span>
          <span class="info-value">PAY-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">No. Faktur Piutang:</span>
          <span class="info-value">{{ $payment->receivable->sale->invoice_number ?? ('REC-' . str_pad($payment->receivable_id, 5, '0', STR_PAD_LEFT)) }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Waktu Pembayaran:</span>
          <span class="info-value">{{ $payment->created_at->format('d/m/Y H:i') }} WIB</span>
        </div>
        <div class="info-row">
          <span class="info-label">Metode Pembayaran:</span>
          <span class="info-value">{{ strtoupper($payment->payment_method ?? 'KAS TUNAI') }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Kasir Penerima:</span>
          <span class="info-value">{{ $payment->user->name ?? 'Kasir Cabang' }}</span>
        </div>
        @if($payment->notes)
        <div class="info-row">
          <span class="info-label">Catatan:</span>
          <span class="info-value">{{ $payment->notes }}</span>
        </div>
        @endif
        <hr style="border: none; border-top: 1px dashed #cbd5e1; margin: 12px 0;">
        <div class="info-row">
          <span class="info-label">Total Tagihan Awal:</span>
          <span class="info-value">Rp {{ number_format($payment->receivable->amount_due, 0, ',', '.') }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Total Sudah Terbayar:</span>
          <span class="info-value" style="color: #16a34a;">Rp {{ number_format($payment->receivable->amount_paid, 0, ',', '.') }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Sisa Pokok Piutang:</span>
          <span class="info-value" style="color: #dc2626; font-size: 15px;">
            Rp {{ number_format(max(0, $payment->receivable->amount_due - $payment->receivable->amount_paid), 0, ',', '.') }}
          </span>
        </div>
        <div class="info-row" style="margin-top: 6px;">
          <span class="info-label">Status Piutang:</span>
          <span class="info-value">
            <span class="badge {{ $payment->receivable->status === 'paid' ? 'badge-paid' : 'badge-partial' }}">
              {{ $payment->receivable->status === 'paid' ? 'LUNAS SEPENUHNYA' : 'BELUM LUNAS (SEBAGIAN)' }}
            </span>
          </span>
        </div>
      </div>

      <p style="font-size: 13px; color: #64748b; margin-top: 20px; line-height: 1.5;">
        Kwitansi digital ini sah dan diterbitkan secara elektronik oleh sistem manajemen kasir terintegrasi.
      </p>
    </div>

    <div class="footer">
      Sistem Kasir POS & Manajemen Piutang PT. DUMAI.<br>
      © {{ date('Y') }} PT. DUMAI. Seluruh hak cipta dilindungi.
    </div>
  </div>
</body>
</html>
