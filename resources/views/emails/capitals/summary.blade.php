<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $subject ?? 'Laporan Eksekutif Portofolio Modal & ROI' }}</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 20px; color: #333; }
    .container { max-width: 650px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .header { background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); color: #ffffff; padding: 28px 24px; text-align: center; }
    .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
    .header p { margin: 6px 0 0; opacity: 0.9; font-size: 13px; }
    .content { padding: 24px; }
    .grid { display: flex; gap: 12px; margin-bottom: 20px; }
    .stat-card { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; text-align: center; }
    .stat-label { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 600; }
    .stat-val { font-size: 18px; font-weight: 800; margin-top: 4px; }
    .val-injected { color: #2563eb; }
    .val-returned { color: #16a34a; }
    .val-outstanding { color: #ea580c; }
    .val-roi { color: #7c3aed; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th { background: #f1f5f9; color: #475569; font-size: 12px; text-align: left; padding: 10px 8px; font-weight: 600; }
    td { padding: 10px 8px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    .footer { background: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>EXECUTIVE CAPITAL & ROI REPORT</h1>
      <p>RINGKASAN PORTOFOLIO MODAL KONSOLIDASI SELURUH CABANG</p>
    </div>

    <div class="content">
      <p style="font-size: 15px; margin-top: 0;">
        Kepada Yth. <strong>Owner / Direksi PT. DUMAI</strong>,
      </p>
      <p style="font-size: 14px; color: #475569; line-height: 1.5;">
        Berikut adalah ikhtisar real-time performa penyaluran modal usaha dan progres pengembalian (Payback ROI) per tanggal <strong>{{ date('d/m/Y') }}</strong>:
      </p>

      <table style="margin-bottom: 20px;">
        <tr>
          <td style="padding: 10px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; text-align: center; width: 50%;">
            <div style="font-size: 11px; color: #1e40af; font-weight: 600;">TOTAL MODAL DISALURKAN</div>
            <div style="font-size: 20px; font-weight: 800; color: #1d4ed8; margin-top: 2px;">Rp {{ number_format($summary['total_injected'] ?? 0, 0, ',', '.') }}</div>
          </td>
          <td style="padding: 10px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; text-align: center; width: 50%;">
            <div style="font-size: 11px; color: #15803d; font-weight: 600;">TOTAL MODAL DIKEMBALIKAN</div>
            <div style="font-size: 20px; font-weight: 800; color: #16a34a; margin-top: 2px;">Rp {{ number_format($summary['total_returned'] ?? 0, 0, ',', '.') }}</div>
          </td>
        </tr>
        <tr>
          <td style="padding: 10px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 6px; text-align: center; width: 50%; margin-top: 8px;">
            <div style="font-size: 11px; color: #c2410c; font-weight: 600;">SISA MODAL TERTANAM</div>
            <div style="font-size: 20px; font-weight: 800; color: #ea580c; margin-top: 2px;">Rp {{ number_format($summary['remaining_capital'] ?? 0, 0, ',', '.') }}</div>
          </td>
          <td style="padding: 10px; background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 6px; text-align: center; width: 50%; margin-top: 8px;">
            <div style="font-size: 11px; color: #7e22ce; font-weight: 600;">PAYBACK RATE (% ROI)</div>
            <div style="font-size: 20px; font-weight: 800; color: #9333ea; margin-top: 2px;">{{ $summary['payback_percentage'] ?? 0 }}%</div>
          </td>
        </tr>
      </table>

      <h3 style="font-size: 14px; margin-bottom: 8px; color: #1e293b;">Rincian Portofolio per Cabang Toko:</h3>
      <table>
        <thead>
          <tr>
            <th>Cabang</th>
            <th style="text-align: right;">Modal Diberikan</th>
            <th style="text-align: right;">Dikembalikan</th>
            <th style="text-align: right;">Sisa Pokok</th>
            <th style="text-align: center;">ROI %</th>
          </tr>
        </thead>
        <tbody>
          @foreach(($summary['branch_breakdown'] ?? []) as $b)
          <tr>
            <td style="font-weight: 600;">{{ $b['branch_name'] }}</td>
            <td style="text-align: right;">Rp {{ number_format($b['total_injected'], 0, ',', '.') }}</td>
            <td style="text-align: right; color: #16a34a;">Rp {{ number_format($b['total_returned'], 0, ',', '.') }}</td>
            <td style="text-align: right; color: #ea580c;">Rp {{ number_format($b['remaining_capital'], 0, ',', '.') }}</td>
            <td style="text-align: center; font-weight: 700; color: #7c3aed;">{{ $b['payback_percentage'] }}%</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="footer">
      Executive Financial Monitoring Report PT. DUMAI.<br>
      © {{ date('Y') }} PT. DUMAI. Seluruh hak cipta dilindungi.
    </div>
  </div>
</body>
</html>
