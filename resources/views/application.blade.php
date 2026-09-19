<!DOCTYPE html>
<html lang="en">

<head>
  @php
    $owner = \App\Models\Owner::first();
    $ownerName = $owner ? $owner->name : 'Sistem Manajemen POS';
    $seoTitle = $ownerName . ' - Sistem POS & Inventaris';
    $seoDesc = 'Sistem Manajemen POS, Inventaris Barang, dan Penggajian Karyawan Terpadu - ' . $ownerName;
    $seoImage = $owner && $owner->logo ? asset('storage/' . $owner->logo) : asset('favicon.ico');
  @endphp
  <meta charset="UTF-8" />
  <link rel="icon" href="{{ $seoImage }}" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  
  <!-- SEO & Link Preview Meta Tags -->
  <title>{{ $seoTitle }}</title>
  <meta name="description" content="{{ $seoDesc }}" />
  
  <!-- Open Graph / Facebook / WhatsApp -->
  <meta property="og:type" content="website" />
  <meta property="og:title" content="{{ $seoTitle }}" />
  <meta property="og:description" content="{{ $seoDesc }}" />
  <meta property="og:url" content="{{ url()->current() }}" />
  <meta property="og:site_name" content="{{ $ownerName }}" />
  <meta property="og:image" content="{{ $seoImage }}" />
  
  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="{{ $seoTitle }}" />
  <meta name="twitter:description" content="{{ $seoDesc }}" />
  <meta name="twitter:image" content="{{ $seoImage }}" />

  <link rel="stylesheet" type="text/css" href="{{ asset('loader.css') }}" />
  @vite(['resources/js/main.js'])
</head>

<body>
  <div id="app">
    <div id="loading-bg">
      <div class="loading-logo">
        <!-- Owner Logo -->
        <img src="{{ $seoImage }}" alt="{{ $ownerName }} Logo" style="max-height: 80px;" onerror="this.src='{{ asset('logo.png') }}'">
      </div>
      <div class=" loading">
        <div class="effect-1 effects"></div>
        <div class="effect-2 effects"></div>
        <div class="effect-3 effects"></div>
      </div>
    </div>
  </div>
  
  <script>
    window.appConfig = {
      appName: {!! json_encode($ownerName) !!},
      appLogo: {!! json_encode($seoImage) !!}
    };

    const loaderColor = localStorage.getItem('materialize-initial-loader-bg') || '#FFFFFF'
    const primaryColor = localStorage.getItem('materialize-initial-loader-color') || '#666CFF'

    if (loaderColor)
      document.documentElement.style.setProperty('--initial-loader-bg', loaderColor)
    if (loaderColor)
      document.documentElement.style.setProperty('--initial-loader-bg', loaderColor)

    if (primaryColor)
      document.documentElement.style.setProperty('--initial-loader-color', primaryColor)
  </script>
</body>

</html>
