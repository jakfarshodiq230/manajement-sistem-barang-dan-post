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
        <!-- svg logo -->
        <svg width="86" height="46" viewBox="0 0 268 150" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect width="50.289" height="143.953" rx="25.144" transform="matrix(-.8652 .50142 .49859 .86684 195.571 0)"
            fill="var(--initial-loader-color)" />
          <rect width="50.289" height="143.953" rx="25.144" transform="matrix(-.8652 .50142 .49859 .86684 196.084 0)"
            fill="url(#a)" fill-opacity=".4" />
          <rect width="50.289" height="143.953" rx="25.144" transform="rotate(30.094 86.573 322.042) skewX(.187)"
            fill="var(--initial-loader-color)" />
          <rect width="50.289" height="143.953" rx="25.144" transform="matrix(-.8652 .50142 .49859 .86684 94.197 0)"
            fill="var(--initial-loader-color)" />
          <rect width="50.289" height="143.953" rx="25.144" transform="matrix(-.8652 .50142 .49859 .86684 94.197 0)"
            fill="url(#b)" fill-opacity=".4" />
          <rect width="50.289" height="143.953" rx="25.144" transform="rotate(30.094 35.886 133.493) skewX(.187)"
            fill="var(--initial-loader-color)" />
          <defs>
            <linearGradient id="a" x1="25.144" y1="0" x2="25.144" y2="143.953" gradientUnits="userSpaceOnUse">
              <stop />
              <stop offset="1" stop-opacity="0" />
            </linearGradient>
            <linearGradient id="b" x1="25.144" y1="0" x2="25.144" y2="143.953" gradientUnits="userSpaceOnUse">
              <stop />
              <stop offset="1" stop-opacity="0" />
            </linearGradient>
          </defs>
        </svg>
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
