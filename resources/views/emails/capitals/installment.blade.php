<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $subject ?? 'Notifikasi Setoran Modal Cabang' }}</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 20px; color: #333; }
    .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .header { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #ffffff; padding: 28px 24px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
    .header p { margin: 6px 0 0; opacity: 0.9; font-size: 13px; }
    .content { padding: 24px; }
    .capital-banner { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 16px; text-align: center; margin: 20px 0; }
    .capital-title { font-size: 12px; color: #047857; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }
    .capital-amount { font-size: 26px; font-weight: 800; color: #059669; margin: 4px 0 0; }
    .card { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 20px; }
    .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
    .info-row:last-child { margin-bottom: 0; }
    .info-label { color: #64748b; }
    .info-value { font-weight: 600; color: #1e293b; text-align: right; }
    .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .badge-approved { background-color: #dcfce7; color: #15803d; }
    .badge-pending { background-color: #fef3c7; color: #d97706; }
    .btn-action { display: block; width: 100%; box-sizing: border-box; background: #059669; color: #ffffff !important; text-align: center; padding: 12px 16px; border-radius: 8px; font-weight: 700; text-decoration: none; font-size: 14px; margin-top: 20px; }
    .footer { background: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>SETORAN PENGEMBALIAN MODAL</h1>
      <p>EXECUTIVE OWNER & MANAGEMENT REPORT</p>
    </div>

    <div class="content">
      <p style="font-size: 15px; margin-top: 0;">
        Kepada Yth. <strong>Owner / Manajemen PT. DUMAI</strong>,
      </p>
      <p style="font-size: 14px; color: #475569; line-height: 1.5;">
        Laporan setoran pengembalian / cicilan modal cabang telah tercatat pada sistem dengan rincian sebagai berikut:
      </p>

      <div class="capital-banner">
        <div class="capital-title">Nominal Setoran Pengembalian Modal</div>
        <div class="capital-amount">Rp {{ number_format($capital->amount, 0, ',', '.') }}</div>
      </div>

      <div class="card">
        <div class="info-row">
          <span class="info-label">No. Referensi:</span>
          <span class="info-value">{{ $capital->reference_no }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Cabang Pengirim:</span>
          <span class="info-value" style="color: #059669;">{{ $capital->branch->name ?? 'Cabang Toko' }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Kategori Setoran:</span>
          <span class="info-value">{{ $capital->category }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Tanggal Setor:</span>
          <span class="info-value">{{ date('d/m/Y', strtotime($capital->date)) }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Metode Pembayaran:</span>
          <span class="info-value">{{ $capital->payment_method }}</span>
        </div>
        @if($capital->bank_name)
        <div class="info-row">
          <span class="info-label">Bank Tujuan:</span>
          <span class="info-value">{{ $capital->bank_name }} ({{ $capital->account_number ?? '-' }})</span>
        </div>
        @endif
        <div class="info-row">
          <span class="info-label">Status Verifikasi:</span>
          <span class="info-value">
            <span class="badge {{ $capital->status === 'approved' ? 'badge-approved' : 'badge-pending' }}">
              {{ $capital->status === 'approved' ? 'DISETUJUI / DITERIMA' : 'MENUNGGU APPROVAL OWNER' }}
            </span>
          </span>
        </div>
        @if($capital->notes)
        <div class="info-row" style="margin-top: 8px;">
          <span class="info-label">Catatan:</span>
          <span class="info-value">{{ $capital->notes }}</span>
        </div>
        @endif
      </div>

      @if($capital->status === 'pending')
      <p style="font-size: 13px; color: #64748b; line-height: 1.5;">
        Silakan buka dashboard manajemen modal untuk memeriksa bukti mutasi bank dan menyetujui transaksi ini agar progres ROI cabang ter-update secara otomatis.
      </p>
      @endif

      <a href="{{ config('app.url') }}/apps/branch-capitals" class="btn-action">
        Buka Dashboard Modal & ROI Cabang
      </a>
    </div>

    <div class="footer">
      Sistem Manajemen Finansial & Modal Toko PT. DUMAI.<br>
      © {{ date('Y') }} PT. DUMAI. Seluruh hak cipta dilindungi.
    </div>
  </div>
</body>
</html>
