<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ isset($title) && $title ? $title.' · ' : '' }}HR Media Score</title>
<meta name="description" content="{{ $description ?? 'HR Media Score: torneos, resultados, posiciones y estadísticas de baloncesto.' }}">
<meta name="theme-color" content="#0B101A">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/branding/icons/favicon-32.png') }}">
<link rel="apple-touch-icon" href="{{ asset('assets/branding/icons/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ route('manifest') }}">
<meta property="og:site_name" content="HR Media Score">
<meta property="og:title" content="{{ $title ?? 'HR Media Score' }}">
<meta property="og:image" content="{{ asset('assets/branding/og-image.jpg') }}">
<meta name="twitter:card" content="summary_large_image">
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
