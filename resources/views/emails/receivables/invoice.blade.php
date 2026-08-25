<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $subject ?? 'Invoice Tagihan Piutang' }}</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 20px; color: #333; }
    .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .header { background: linear-gradient(135deg, #106ae0 0%, #0c4fa8 100%); color: #ffffff; padding: 28px 24px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
    .header p { margin: 6px 0 0; opacity: 0.9; font-size: 13px; }
    .content { padding: 24px; }
    .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
    .badge-unpaid { background-color: #fee2e2; color: #dc2626; }
    .badge-partial { background-color: #fef3c7; color: #d97706; }
    .card { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 20px; }
    .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
    .info-row:last-child { margin-bottom: 0; }
    .info-label { color: #64748b; }
    .info-value { font-weight: 600; color: #1e293b; text-align: right; }
    .total-banner { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 16px; text-align: center; margin: 20px 0; }
    .total-title { font-size: 12px; color: #1e40af; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }
    .total-amount { font-size: 26px; font-weight: 800; color: #1d4ed8; margin: 4px 0 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th { background: #f1f5f9; color: #475569; font-size: 12px; text-align: left; padding: 10px 8px; font-weight: 600; }
    td { padding: 10px 8px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    .bank-box { background: #f0fdf4; border: 1px dashed #86efac; border-radius: 8px; padding: 14px; margin-top: 20px; }
    .footer { background: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>SURAT TAGIHAN PIUTANG</h1>
      <p>PT. DUMAI MANAJEMEN SISTEM POS & LOGISTIK</p>
    </div>

    <div class="content">
      <p style="font-size: 15px; margin-top: 0;">
        Kepada Yth. <strong>{{ $receivable->customer->name ?? 'Pelanggan Terhormat' }}</strong>,
      </p>
      <p style="font-size: 14px; color: #475569; line-height: 1.5;">
        Berikut kami sampaikan rincian tagihan faktur tempo transaksi belanja Anda yang tercatat pada sistem kami:
      </p>

      <div class="total-banner">
        <div class="total-title">Sisa Saldo Tagihan yang Harus Dibayar</div>
        <div class="total-amount">Rp {{ number_format($receivable->amount_due - $receivable->amount_paid, 0, ',', '.') }}</div>
      </div>

      <div class="card">
        <div class="info-row">
          <span class="info-label">Nomor Faktur / Nota:</span>
          <span class="info-value">{{ $receivable->sale->invoice_number ?? ('REC-' . str_pad($receivable->id, 5, '0', STR_PAD_LEFT)) }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Tanggal Transaksi:</span>
          <span class="info-value">{{ $receivable->created_at->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Tanggal Jatuh Tempo:</span>
          <span class="info-value" style="color: #dc2626;">{{ $receivable->due_date ? date('d/m/Y', strtotime($receivable->due_date)) : '-' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Total Tagihan Awal:</span>
          <span class="info-value">Rp {{ number_format($receivable->amount_due, 0, ',', '.') }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Sudah Dibayarkan:</span>
          <span class="info-value" style="color: #16a34a;">Rp {{ number_format($receivable->amount_paid, 0, ',', '.') }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Status Tagihan:</span>
          <span class="info-value">
            <span class="badge {{ $receivable->status === 'unpaid' ? 'badge-unpaid' : 'badge-partial' }}">
              {{ $receivable->status === 'unpaid' ? 'Belum Dibayar' : 'Sebagian Lunas' }}
            </span>
          </span>
        </div>
      </div>

      @if($receivable->sale && $receivable->sale->items && count($receivable->sale->items) > 0)
      <h3 style="font-size: 14px; margin-bottom: 8px; color: #1e293b;">Rincian Barang yang Dipesan:</h3>
      <table>
        <thead>
          <tr>
            <th>Barang</th>
            <th style="text-align: center;">Qty</th>
            <th style="text-align: right;">Harga Satuan</th>
            <th style="text-align: right;">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          @foreach($receivable->sale->items as $item)
          <tr>
            <td>{{ $item->productBranch->product->name ?? 'Produk' }}</td>
            <td style="text-align: center;">{{ $item->quantity }}</td>
            <td style="text-align: right;">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
            <td style="text-align: right;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
      @endif

      <div class="bank-box">
        <strong style="color: #15803d; font-size: 13px; display: block; margin-bottom: 4px;">Informasi Rekening Pembayaran Toko:</strong>
        <p style="margin: 0; font-size: 13px; color: #166534; line-height: 1.5;">
          Bank: <strong>BCA / Mandiri</strong><br>
          A/N: <strong>PT. DUMAI LOGISTIK TERPADU</strong><br>
          No. Rekening: <strong>123-456-7890</strong>
        </p>
      </div>

      <p style="font-size: 13px; color: #64748b; margin-top: 20px; line-height: 1.5;">
        Jika Anda telah melakukan pembayaran, mohon abaikan email ini atau kirimkan bukti transfer kepada kasir/admin kami. Terima kasih atas kerja sama Anda.
      </p>
    </div>

    <div class="footer">
      Email ini dikirim secara otomatis oleh Sistem Kasir & Manajemen Piutang POS.<br>
      © {{ date('Y') }} PT. DUMAI. Seluruh hak cipta dilindungi.
    </div>
  </div>
</body>
</html>
