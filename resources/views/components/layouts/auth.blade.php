@props(['title' => null])
<!DOCTYPE html>
<html lang="es">
<head>@include('partials.head', ['title' => $title])<meta name="robots" content="noindex"></head>
<body class="min-h-dvh">
<div class="relative grid min-h-dvh place-items-center overflow-hidden px-4 py-10">
    <div class="glow-red pointer-events-none absolute inset-0"></div>
    <div class="court-lines pointer-events-none absolute inset-0 opacity-70"></div>
    <div class="relative w-full max-w-md animate-fade-up">
        <a href="{{ route('home') }}" class="mx-auto mb-6 block w-fit" aria-label="Volver al portal">
            <img src="{{ asset('assets/branding/hr-logo-principal-640.webp') }}" alt="HR Media Score" width="640" height="404" class="h-36 w-auto drop-shadow-[0_10px_40px_rgba(217,35,50,.25)] sm:h-44">
        </a>
        <div class="card p-6 shadow-2xl shadow-black/40 sm:p-8">{{ $slot }}</div>
        <p class="mt-6 text-center text-xs text-mute-dim"><a href="{{ route('home') }}" class="hover:text-white">← Volver al portal público</a></p>
    </div>
</div>
<x-flash />
@livewireScripts
</body>
</html>
