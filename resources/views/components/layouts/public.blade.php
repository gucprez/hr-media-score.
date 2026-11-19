@props(['title' => null, 'description' => null])
@php
    $nav = [
        ['home', 'Inicio', 'home'],
        ['public.tournaments.index', 'Torneos', 'public.tournaments.*'],
        ['public.games.index', 'Partidos', 'public.games.*'],
        ['public.teams.index', 'Equipos', 'public.teams.*'],
        ['public.players.index', 'Jugadores', 'public.players.*'],
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>@include('partials.head', ['title' => $title, 'description' => $description])</head>
<body class="min-h-dvh">
<a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[70] btn-primary">Saltar al contenido</a>

<header x-data="{ open: false }" class="sticky top-0 z-50 border-b divider bg-ink-950/85 backdrop-blur-xl">
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6">
        <a href="{{ route('home') }}" class="shrink-0" aria-label="HR Media Score — Inicio">
            <img src="{{ asset('assets/branding/hr-logo-horizontal-400.webp') }}" srcset="{{ asset('assets/branding/hr-logo-horizontal-800.webp') }} 2x"
                 alt="HR Media Score" width="400" height="129" class="h-9 w-auto sm:h-10">
        </a>
        <nav class="hidden flex-1 items-center gap-1 md:flex" aria-label="Principal">
            @foreach ($nav as [$route, $label, $pattern])
                <a href="{{ route($route) }}" @class(['nav-link', 'nav-link-active' => request()->routeIs($pattern)]) @if(request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="ml-auto flex items-center gap-2">
            @auth
                <a href="{{ route('admin.dashboard') }}" class="btn-secondary btn-sm hidden sm:inline-flex"><x-icon name="scoreboard" class="size-4" />Panel</a>
            @else
                <a href="{{ route('login') }}" class="btn-ghost btn-sm hidden sm:inline-flex"><x-icon name="lock" class="size-4" />Acceso</a>
            @endauth
            <button type="button" class="btn-ghost btn-sm md:hidden" @click="open = !open" :aria-expanded="open" aria-controls="menu-movil" aria-label="Abrir menú">
                <x-icon name="menu" class="size-6" x-show="!open" /><x-icon name="x" class="size-6" x-show="open" x-cloak />
            </button>
        </div>
    </div>
    <nav id="menu-movil" x-show="open" x-collapse x-cloak class="border-t divider md:hidden" aria-label="Principal móvil">
        <div class="space-y-1 px-4 py-3">
            @foreach ($nav as [$route, $label, $pattern])
                <a href="{{ route($route) }}" @class(['block rounded-xl px-4 py-3 font-display text-lg font-semibold uppercase tracking-wide', 'bg-brand/10 text-white' => request()->routeIs($pattern), 'text-mute' => ! request()->routeIs($pattern)])>{{ $label }}</a>
            @endforeach
            <a href="{{ auth()->check() ? route('admin.dashboard') : route('login') }}" class="block rounded-xl px-4 py-3 text-sm text-mute">{{ auth()->check() ? 'Panel administrativo' : 'Acceso administrativo' }}</a>
        </div>
    </nav>
</header>

<main id="contenido">{{ $slot }}</main>

<footer class="mt-20 border-t divider bg-ink-900">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-3">
        <div>
            <img src="{{ asset('assets/branding/hr-logo-principal-320.webp') }}" alt="HR Media Score" width="320" height="202" class="h-20 w-auto" loading="lazy">
            <p class="mt-4 max-w-xs text-sm text-mute">Plataforma de gestión deportiva para torneos de baloncesto. Desarrollada por HR Media.</p>
        </div>
        <div>
            <p class="eyebrow mb-3">Explorar</p>
            <ul class="space-y-2 text-sm">
                @foreach ($nav as [$route, $label])<li><a href="{{ route($route) }}" class="text-mute hover:text-white">{{ $label }}</a></li>@endforeach
            </ul>
        </div>
        <div>
            <p class="eyebrow mb-3">Organizaciones</p>
            <p class="text-sm text-mute">¿Tu liga o club quiere administrar sus torneos con HR Media Score?</p>
            <a href="{{ route('login') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-sport hover:underline">Acceso para organizadores <x-icon name="arrow-right" class="size-4" /></a>
        </div>
    </div>
    <div class="border-t divider">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-5 text-xs text-mute-dim sm:flex-row sm:px-6">
            <p>© {{ now()->year }} HR Media. Todos los derechos reservados.</p>
            <p class="flex items-center gap-2"><span class="stripes inline-block h-2 w-6 rounded-sm opacity-80"></span> HR Media Score</p>
        </div>
    </div>
</footer>
<x-flash />
@livewireScripts
</body>
</html>
