<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>Ms.POS</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

        <link rel="stylesheet" href="{{ asset('css/tailadmin.css') }}">
        
        <!-- Scripts -->
        @php
            $manifestPath = public_path('build/manifest.json');
            $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
        @endphp

        @if(file_exists(public_path('hot')))
            @php
                $viteUrl = file_get_contents(public_path('hot'));
                $viteUrl = str_starts_with($viteUrl, 'http') ? rtrim($viteUrl) : 'http://localhost:5174';
            @endphp
            <script type="module" src="{{ $viteUrl }}/@@vite/client"></script>
            <script type="module" src="{{ $viteUrl }}/resources/js/main.js"></script>
        @else
            @if(isset($manifest['resources/js/main.js']))
                @foreach($manifest['resources/js/main.js']['css'] ?? [] as $css)
                    <link rel="stylesheet" href="{{ asset('build/' . $css) }}">
                @endforeach
                <script type="module" src="{{ asset('build/' . $manifest['resources/js/main.js']['file']) }}"></script>
            @endif
        @endif
    </head>
    <body class="font-sans antialiased">
        <div id="app"></div>
    </body>
</html>
