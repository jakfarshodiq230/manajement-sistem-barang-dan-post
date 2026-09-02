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
            vertical-align: top;
            padding: 0 6px;
            text-align: center;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
        
        .qr-section {
            margin-top: 15px;
            padding: 8px;
            border: 1px dashed #ccc;
            text-align: center;
            width: 180px;
            float: left;
        }
        .qr-section img {
            width: 70px;
            height: 70px;
        }
        .qr-text {
            font-size: 9px;
            margin-top: 4px;
            color: #555;
        }
        .digital-ttd-qr {
            width: 60px;
            height: 60px;
            margin: 2px auto;
        }
        .digital-badge {
            font-size: 8px;
            color: #1b5e20;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .physical-ttd-space {
            height: 60px;
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
                        <h2 style="margin: 0 0 2px 0; font-size: 16px;">{{ strtoupper($branch->owner->name) }}</h2>
                        <div style="font-size: 13px; font-weight: bold; color: #1a237e; margin-bottom: 3px;">
                            {{ strtoupper($branch->name) }}
                        </div>
                        <p style="margin: 0; font-size: 10px; color: #555; line-height: 1.3;">
                            {{ $branch->address ?: $branch->owner->address }}<br>
                            Telp: {{ $branch->phone ?: $branch->owner->phone }} 
                            @if(!empty($branch->email) || !empty($branch->owner->email))
                                | Email: {{ $branch->email ?: $branch->owner->email }}
                            @endif
                        </p>
                    @elseif(isset($branch))
                        <h2 style="margin: 0 0 2px 0; font-size: 16px;">{{ strtoupper($branch->name) }}</h2>
                        <p style="margin: 0; font-size: 10px; color: #555; line-height: 1.3;">
                            {{ $branch->address ?? '-' }}<br>
                            Telp: {{ $branch->phone ?? '-' }}
                        </p>
                    @else
                        <h2 style="margin: 0 0 2px 0; font-size: 16px;">NAMA PERUSAHAAN</h2>
                        <p style="margin: 0; font-size: 10px; color: #555; line-height: 1.3;">Alamat Lengkap Perusahaan, Kota<br>Telp: (021) 1234567 | Email: info@perusahaan.com</p>
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
        
        <div style="float: right; width: 70%;">
            @if(isset($type) && $type === 'goods_receipt')
            <table class="signature-table" style="width: 100%;">
                <tr>
                    <td style="width: 33%;">
                        <p style="margin: 0 0 5px 0; font-size: 11px;">Pengirim / Ekspedisi,</p>
                        <div class="physical-ttd-space"></div>
                        <div class="signature-name">
                            ( ____________________ )
                        </div>
                        <div style="font-size: 9px; color: #777;">Tanda Tangan & Nama Terang</div>
                    </td>
                    <td style="width: 33%;">
                        <p style="margin: 0 0 5px 0; font-size: 11px;">Penerima Gudang,</p>
                        @if(isset($userQrCode) && $userQrCode)
                            <div><img src="data:image/svg+xml;base64,{{ $userQrCode }}" class="digital-ttd-qr" alt="TTD QR Penerima"></div>
                            <div class="digital-badge">[TERTANDA DIGITAL]</div>
                        @else
                            <div class="physical-ttd-space"></div>
                        @endif
                        <div class="signature-name">
                            @if(isset($document->user))
                                ( {{ $document->user->name }} )
                                <br><span style="font-weight: normal; text-decoration: none; font-size: 9px; color: #555;">NIP: {{ $document->user->nip ?? ($document->user->employee->nik ?? 'EMP-' . str_pad($document->user->id, 3, '0', STR_PAD_LEFT)) }}</span>
                            @else
                                ( ____________________ )
                            @endif
                        </div>
                    </td>
                    <td style="width: 33%;">
                        <p style="margin: 0 0 5px 0; font-size: 11px;">Menyetujui (Ka. Divisi / Owner),</p>
                        <div class="physical-ttd-space"></div>
                        <div class="signature-name">
                            @if(isset($branch) && isset($branch->owner))
                                ( {{ $branch->owner->name }} )
                            @elseif(isset($document->approver))
                                ( {{ $document->approver->name }} )
                                <br><span style="font-weight: normal; text-decoration: none; font-size: 9px; color: #555;">NIP: {{ $document->approver->nip ?? ($document->approver->employee->nik ?? 'EMP-' . str_pad($document->approver->id, 3, '0', STR_PAD_LEFT)) }}</span>
                            @else
                                ( Pimpinan Toko )
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
            @else
            <table class="signature-table" style="width: 100%;">
                <tr>
                    <td style="width: 33%;">
                        <p style="margin: 0 0 5px 0; font-size: 11px;">Dibuat Oleh (Kasir / Petugas),</p>
                        @if(isset($userQrCode) && $userQrCode)
                            <div><img src="data:image/svg+xml;base64,{{ $userQrCode }}" class="digital-ttd-qr" alt="TTD QR Pembuat"></div>
                            <div class="digital-badge">[TERTANDA DIGITAL]</div>
                        @else
                            <div class="physical-ttd-space"></div>
                        @endif
                        <div class="signature-name">
                            @if(isset($document->user) && $document->user)
                                ( {{ $document->user->name }} )
                                <br><span style="font-weight: normal; text-decoration: none; font-size: 9px; color: #555;">NIP: {{ $document->user->nip ?? ($document->user->employee->nik ?? 'EMP-' . str_pad($document->user->id, 3, '0', STR_PAD_LEFT)) }}</span>
                            @elseif(isset($document->creator) && $document->creator)
                                ( {{ $document->creator->name }} )
                                <br><span style="font-weight: normal; text-decoration: none; font-size: 9px; color: #555;">NIP: {{ $document->creator->nip ?? ($document->creator->employee->nik ?? 'EMP-' . str_pad($document->creator->id, 3, '0', STR_PAD_LEFT)) }}</span>
                            @else
                                ( Admin / Petugas )
                            @endif
                        </div>
                    </td>
                    <td style="width: 33%;">
                        <p style="margin: 0 0 5px 0; font-size: 11px;">Diperiksa Oleh,</p>
                        @if(isset($validatorQrCode) && $validatorQrCode)
                            <div><img src="data:image/svg+xml;base64,{{ $validatorQrCode }}" class="digital-ttd-qr" alt="TTD QR Pemeriksa"></div>
                            <div class="digital-badge">[TERVALIDASI]</div>
                        @else
                            <div class="physical-ttd-space"></div>
                        @endif
                        <div class="signature-name">
                            @if(isset($document->validated_by) && isset($document->validator))
                                ( {{ $document->validator->name }} )
                                <br><span style="font-weight: normal; text-decoration: none; font-size: 9px; color: #555;">NIP: {{ $document->validator->nip ?? ($document->validator->employee->nik ?? 'EMP-' . str_pad($document->validator->id, 3, '0', STR_PAD_LEFT)) }}</span>
                            @else
                                ( ____________________ )
                            @endif
                        </div>
                    </td>
                    <td style="width: 33%;">
                        <p style="margin: 0 0 5px 0; font-size: 11px;">Mengetahui (Pimpinan / Owner),</p>
                        <div class="physical-ttd-space"></div>
                        <div class="signature-name">
                            @if(isset($branch) && isset($branch->owner))
                                ( {{ $branch->owner->name }} )
                            @elseif(isset($document->approved_by) && isset($document->approver))
                                ( {{ $document->approver->name }} )
                            @else
                                ( Pimpinan Toko )
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
