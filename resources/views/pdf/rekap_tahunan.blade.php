<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekapitulasi Tahunan - {{ $year }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 12px;
        }
        
        .summary-box {
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-box td {
            width: 25%;
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
            background-color: #f9f9f9;
        }
        .summary-box td strong {
            display: block;
            font-size: 14px;
            margin-top: 5px;
        }

        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .content-table th, .content-table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: right;
        }
        .content-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        .content-table td.text-left { text-align: left; }
        .content-table td.text-center { text-align: center; }

        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        
        .footer-date {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>REKAPITULASI KEUANGAN & AUDIT</h2>
        <p>Tahun {{ $year }}</p>
    </div>

    <table class="summary-box">
        <tr>
            <td>
                Total Omset
                <strong>Rp {{ number_format($summary['total_omset'], 0, ',', '.') }}</strong>
            </td>
            <td>
                Total Laba Bersih
                <strong>Rp {{ number_format($summary['total_laba'], 0, ',', '.') }}</strong>
            </td>
            <td>
                Total Transaksi
                <strong>{{ number_format($summary['total_transaksi'], 0, ',', '.') }}</strong>
            </td>
            <td>
                Margin
                <strong>{{ $summary['margin'] }}%</strong>
            </td>
        </tr>
    </table>

    <table class="content-table">
        <thead>
            <tr>
                <th rowspan="2">Bulan</th>
                <th colspan="3">Kinerja Finansial</th>
                <th colspan="2">Audit Kas</th>
                <th colspan="2">Pergerakan Stok</th>
                <th colspan="2">Kumulatif (YTD)</th>
            </tr>
            <tr>
                <th>Total Transaksi</th>
                <th>Omset (Rp)</th>
                <th>Laba Bersih (Rp)</th>
                <th>Selisih Kas (Rp)</th>
                <th>Tutup Kasir</th>
                <th>Masuk</th>
                <th>Keluar</th>
                <th>Total Omset (Rp)</th>
                <th>Total Laba (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($months as $row)
                <tr>
                    <td class="text-left">{{ $row['bulan'] }}</td>
                    
                    @if($row['is_future'])
                        <td class="text-center" colspan="9"><em>-</em></td>
                    @else
                        <td>{{ number_format($row['jumlah_transaksi'], 0, ',', '.') }}</td>
                        <td>{{ number_format($row['omset'], 0, ',', '.') }}</td>
                        <td>{{ number_format($row['laba_bersih'], 0, ',', '.') }}</td>
                        
                        <td class="{{ $row['selisih_kas'] < 0 ? 'text-danger' : ($row['selisih_kas'] > 0 ? 'text-success' : '') }}">
                            {{ number_format($row['selisih_kas'], 0, ',', '.') }}
                        </td>
                        <td class="text-center">{{ number_format($row['jumlah_closing'], 0, ',', '.') }}x</td>
                        
                        <td class="text-center">{{ number_format($row['stok_masuk'], 0, ',', '.') }}</td>
                        <td class="text-center">{{ number_format($row['stok_keluar'], 0, ',', '.') }}</td>
                        
                        <td>{{ number_format($row['kumulatif_omset'], 0, ',', '.') }}</td>
                        <td>{{ number_format($row['kumulatif_laba'], 0, ',', '.') }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer-date">
        Dicetak pada: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y HH:mm:ss') }}
    </div>

    <!-- Halaman Detail per Bulan -->
    @if(isset($monthly_details) && count($monthly_details) > 0)
        @foreach($monthly_details as $m => $detail)
            <div style="page-break-before: always;"></div>
            
            <div class="header">
                <h2>DETAIL REKAPITULASI: {{ strtoupper($detail['month_name']) }} {{ $year }}</h2>
                <p>Rincian Harian</p>
            </div>
            
            <table class="content-table">
                <thead>
                    <tr>
                        <th rowspan="2">Tanggal</th>
                        <th colspan="3">Kinerja Finansial</th>
                        <th colspan="2">Audit Kas</th>
                        <th colspan="2">Pergerakan Stok</th>
                    </tr>
                    <tr>
                        <th>Total Transaksi</th>
                        <th>Omset (Rp)</th>
                        <th>Laba Bersih (Rp)</th>
                        <th>Selisih Kas (Rp)</th>
                        <th>Tutup Kasir</th>
                        <th>Masuk</th>
                        <th>Keluar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($detail['days'] as $day)
                        @if(!$day['is_future'])
                        <tr>
                            <td class="text-left">
                                {{ \Carbon\Carbon::parse($day['tanggal'])->format('d/m/Y') }}<br>
                                <span style="font-size: 9px; color:#666;">{{ $day['hari'] }}</span>
                            </td>
                            <td>{{ number_format($day['jumlah_transaksi'], 0, ',', '.') }}</td>
                            <td>{{ number_format($day['omset'], 0, ',', '.') }}</td>
                            <td>{{ number_format($day['laba'], 0, ',', '.') }}</td>
                            <td class="{{ $day['selisih_kas'] < 0 ? 'text-danger' : ($day['selisih_kas'] > 0 ? 'text-success' : '') }}">
                                {{ number_format($day['selisih_kas'], 0, ',', '.') }}
                            </td>
                            <td class="text-center">{{ $day['selisih_kas'] != 0 || $day['jumlah_transaksi'] > 0 ? 'Ya' : '-' }}</td>
                            <td class="text-center">{{ number_format($day['stok_masuk'], 0, ',', '.') }}</td>
                            <td class="text-center">{{ number_format($day['stok_keluar'], 0, ',', '.') }}</td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
            <div class="footer-date">
                Dicetak pada: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y HH:mm:ss') }}
            </div>
        @endforeach
    @endif
</body>
</html>
