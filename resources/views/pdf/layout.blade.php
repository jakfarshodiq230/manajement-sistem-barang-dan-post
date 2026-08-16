<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Dokumen')</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
        }
        .header .company-info {
            width: 60%;
        }
        .header .doc-info {
            width: 40%;
            text-align: right;
        }
        .doc-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .content-table th, .content-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .content-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        
        .footer {
            margin-top: 30px;
            width: 100%;
        }
        .signature-box {
            width: 100%;
            margin-top: 20px;
        }
        .signature-table {
            width: 100%;
            text-align: center;
        }
        .signature-table td {
            width: 33%;
            vertical-align: top;
            padding-top: 50px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
        
        .qr-section {
            margin-top: 30px;
            padding: 10px;
            border: 1px dashed #ccc;
            text-align: center;
            width: 250px;
            float: left;
        }
        .qr-section img {
            width: 80px;
            height: 80px;
        }
        .qr-text {
            font-size: 10px;
            margin-top: 5px;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td class="company-info">
                    @if(isset($branch) && isset($branch->owner))
                        <h2>{{ strtoupper($branch->owner->name) }}</h2>
                        <p>{{ $branch->owner->address }}<br>Telp: {{ $branch->owner->phone }} | Email: {{ $branch->owner->email }}</p>
                    @else
                        <h2>NAMA PERUSAHAAN</h2>
                        <p>Alamat Lengkap Perusahaan, Kota<br>Telp: (021) 1234567 | Email: info@perusahaan.com</p>
                    @endif
                </td>
                <td class="doc-info">
                    <div class="doc-title">@yield('document_title')</div>
                    <p>
                        <strong>No Dokumen:</strong> @yield('document_number')<br>
                        <strong>Tanggal:</strong> @yield('document_date')
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        @yield('content')
    </div>

    <div class="footer clearfix">
        <div class="qr-section">
            <div><img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="QR Code"></div>
            <div class="qr-text">Scan QR Code ini untuk verifikasi keaslian dokumen</div>
        </div>
        
        <div style="float: right; width: 60%;">
            @if(isset($type) && $type === 'goods_receipt')
            <table class="signature-table" style="width: 100%;">
                <tr>
                    <td style="width: 50%;">
                        <p>Pengirim / Ekspedisi,</p>
                        <br><br><br>
                        <div class="signature-name">
                            (____________________)
                        </div>
                    </td>
                    <td style="width: 50%;">
                        <p>Penerima Gudang,</p>
                        <br><br><br>
                        <div class="signature-name">
                            @if(isset($document->user))
                                ( {{ $document->user->name }} )
                                <br><span style="font-weight: normal; text-decoration: none; font-size: 10px;">NIP: {{ $document->user->employee->nik ?? '-' }}</span>
                            @else
                                (____________________)
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
            @else
            <table class="signature-table">
                <tr>
                    <td>
                        <p>Dibuat Oleh,</p>
                        <br><br><br>
                        <div class="signature-name">
                            @if(isset($document->user))
                                ( {{ $document->user->name }} )
                                <br><span style="font-weight: normal; text-decoration: none; font-size: 10px;">NIP: {{ $document->user->employee->nik ?? '-' }}</span>
                            @else
                                ( ____________________ )
                            @endif
                        </div>
                    </td>
                    <td>
                        <p>Diperiksa Oleh,</p>
                        <br><br><br>
                        <div class="signature-name">
                            @if(isset($document->validated_by) && isset($document->validator))
                                ( {{ $document->validator->name }} )
                                <br><span style="font-weight: normal; text-decoration: none; font-size: 10px;">NIP: {{ $document->validator->employee->nik ?? '-' }}</span>
                            @else
                                ( ____________________ )
                            @endif
                        </div>
                    </td>
                    <td>
                        <p>Disetujui Oleh,</p>
                        <br><br><br>
                        <div class="signature-name">
                            @if(isset($document->approved_by) && isset($document->approver))
                                ( {{ $document->approver->name }} )
                                <br><span style="font-weight: normal; text-decoration: none; font-size: 10px;">NIP: {{ $document->approver->employee->nik ?? '-' }}</span>
                            @else
                                ( ____________________ )
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
            @endif
        </div>
    </div>
</body>
</html>
