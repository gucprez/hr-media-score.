<!DOCTYPE html>
<html lang="es"><head>@include('partials.head', ['title' => $title])<meta name="robots" content="noindex"></head>
<body class="min-h-dvh">
<div class="relative grid min-h-dvh place-items-center overflow-hidden px-4">
    <div class="glow-red absolute inset-0"></div>
    <div class="relative text-center">
        <img src="{{ asset('assets/branding/hr-isotipo-256.webp') }}" alt="HR Media Score" width="256" height="184" class="mx-auto h-24 w-auto">
        <p class="font-score mt-6 text-8xl text-transparent [-webkit-text-stroke:2px_var(--color-brand)]">{{ $code }}</p>
        <h1 class="heading mt-2 text-3xl">{{ $title }}</h1>
        <p class="mx-auto mt-2 max-w-md text-mute">{{ $message }}</p>
        <a href="{{ url('/') }}" class="btn-primary mt-8">Volver al inicio</a>
    </div>
</div>
</body></html>
