<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Dokumen')</title>
    <style>
        @page {
            margin: 150px 40px 100px 40px; /* Top, Right, Bottom, Left */
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        header {
            position: fixed;
            top: -120px;
            left: 0px;
            right: 0px;
            height: 105px;
            border-bottom: 2px solid #000;
        }
        footer {
            position: fixed;
            bottom: -100px;
            left: -40px; /* Adjusting for @page margin */
            right: -40px;
            height: 120px;
            z-index: -100;
        }
        
        /* Kop Styles */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
        }
        .kop-logo {
            width: 80px;
            text-align: left;
            vertical-align: middle;
        }
        .kop-logo img {
            max-width: 100px;
            max-height: 70px;
        }
        .kop-company {
            padding-left: 10px;
            vertical-align: middle;
        }
        .kop-company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1a237e; /* Dark blue like UIB */
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }
        .kop-contact {
            width: 35%;
            text-align: right;
            font-size: 10px;
            vertical-align: middle;
            color: #333;
        }
        
        .footer-wave {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 400px; /* Size of the wave graphic */
            opacity: 0.9;
        }

        /* Content Styles */
        main {
            /* main content automatically flows inside the @page margins */
            width: 100%;
        }
        .document-header {
            text-align: center;
            margin-bottom: 15px;
        }
        .doc-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            margin-bottom: 5px;
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
        
        .signature-box {
            width: 100%;
            margin-top: 20px;
        }
        .signature-table {
            width: 100%;
            text-align: center;
            page-break-inside: avoid;
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
    <!-- HEADER BERULANG (KOP) -->
    <header>
        <table class="kop-table">
            <tr>
                <td class="kop-logo" style="width: 110px; text-align: left; vertical-align: middle;">
                    @php
                        $ownerLogoPath = null;
                        if(isset($branch) && isset($branch->owner) && $branch->owner->logo) {
                            $path = storage_path('app/public/' . $branch->owner->logo);
                            if(file_exists($path)) {
                                $ownerLogoPath = $path;
                            }
                        }
                        if(!$ownerLogoPath) {
                            $fallback = public_path('logo.png');
                            if(file_exists($fallback)) {
                                $ownerLogoPath = $fallback;
                            }
                        }
                    @endphp

                    @if($ownerLogoPath)
                        <img src="{{ $ownerLogoPath }}" alt="Logo" style="max-width: 100px; max-height: 75px; object-fit: contain;">
                    @else
                        <!-- Placeholder jika logo belum diupload -->
                        <div style="width: 70px; height: 70px; line-height: 70px; text-align: center; background: #f0f0f0; border: 1px dashed #999; font-size: 10px; font-weight: bold; color: #666;">LOGO</div>
                    @endif
                </td>
                <td class="kop-company">
                    @if(isset($branch) && isset($branch->owner))
                        <h1 class="kop-company-name">{{ $branch->owner->name }}</h1>
                        <div style="font-size: 12px; font-weight: bold; margin-bottom: 3px;">
                            {{ strtoupper($branch->name) }}
                        </div>
                        <p style="margin: 0; font-size: 10px; color: #333; line-height: 1.3;">
                            {{ $branch->address ?: $branch->owner->address }}
                        </p>
                    @elseif(isset($branch))
                        <h1 class="kop-company-name">{{ strtoupper($branch->name) }}</h1>
                        <p style="margin: 0; font-size: 10px; color: #333; line-height: 1.3;">
                            {{ $branch->address ?? '-' }}
                        </p>
                    @else
                        <h1 class="kop-company-name">NAMA PERUSAHAAN</h1>
                        <p style="margin: 0; font-size: 10px; color: #333; line-height: 1.3;">Alamat Lengkap Perusahaan, Kota</p>
                    @endif
                </td>
                <td class="kop-contact">
                    <p style="margin: 0; line-height: 1.4;">
                        @if(isset($branch))
                            @if(!empty($branch->email) || !empty($branch->owner->email))
                                <strong>Email:</strong> {{ $branch->email ?: $branch->owner->email }}<br>
                            @endif
                            <strong>Tel:</strong> {{ $branch->phone ?: ($branch->owner->phone ?? '-') }}
                        @else
                            <strong>Tel:</strong> (021) 1234567<br>
                            <strong>Email:</strong> info@perusahaan.com
                        @endif
                    </p>
                </td>
            </tr>
        </table>
    </header>

    <!-- FOOTER BERULANG (OMBak) -->
    <footer>
        <!-- Menggunakan fallback ke div biru/kuning jika gambar belum ada -->
        @if(file_exists(public_path('images/footer_wave.png')))
            <img src="{{ public_path('images/footer_wave.png') }}" class="footer-wave" alt="Footer">
        @else
            <div style="position: absolute; bottom: 0; right: 0; width: 100%; height: 30px; background-color: #f7b731; border-top: 10px solid #1a237e;"></div>
        @endif
    </footer>

    <!-- KONTEN UTAMA DOKUMEN -->
    <main>
        <!-- Judul Dokumen -->
        <div class="document-header">
            <div class="doc-title">@yield('document_title')</div>
            <div style="font-size: 11px;">
                <strong>No Dokumen:</strong> @yield('document_number') &nbsp;|&nbsp; 
                <strong>Tanggal:</strong> @yield('document_date')
            </div>
        </div>

        <div class="content">
            @yield('content')
        </div>

        <!-- Bagian Tanda Tangan (Tidak Terpotong) -->
        <div class="signature-box clearfix">
            <div class="qr-section" @if(isset($type) && $type === 'sale') style="margin: 20px auto; float: none;" @endif>
                <div><img src="data:image/svg+xml;base64,{{ $qrCode ?? '' }}" alt="QR Code"></div>
                <div class="qr-text">Scan QR Code ini untuk verifikasi keaslian dokumen</div>
            </div>
            
            @if(!isset($type) || $type !== 'sale')
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
            @endif
        </div>
    </main>
</body>
</html>
