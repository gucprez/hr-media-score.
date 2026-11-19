@props(['title' => null, 'header' => null, 'actions' => null, 'breadcrumbs' => []])
@php
    $u = auth()->user();
    $groups = [
        'General' => [
            ['admin.dashboard', 'Dashboard', 'home', 'admin.dashboard', 'dashboard.view'],
        ],
        'Competición' => [
            ['admin.tournaments.index', 'Torneos', 'trophy', 'admin.tournaments.*', 'tournaments.view'],
            ['admin.seasons.index', 'Temporadas', 'layers', 'admin.seasons.*', 'seasons.manage'],
            ['admin.teams.index', 'Equipos', 'shield', 'admin.teams.*', 'teams.manage'],
            ['admin.players.index', 'Jugadores', 'user', 'admin.players.*', 'players.manage'],
            ['admin.venues.index', 'Sedes', 'map-pin', 'admin.venues.*', 'venues.manage'],
        ],
        'Organización' => [
            ['admin.clubs.index', $u->isSuperAdmin() ? 'Organizaciones' : 'Mi club', 'building', 'admin.clubs.*', 'clubs.view'],
            ['admin.users.index', 'Usuarios y permisos', 'users', 'admin.users.*', 'users.manage'],
            ['admin.audit.index', 'Auditoría', 'history', 'admin.audit.*', 'audit.view'],
            ['admin.settings.edit', 'Configuración', 'settings', 'admin.settings.*', null],
        ],
    ];
    // Módulos de la Fase 2 (aún no disponibles): se muestran deshabilitados, sin enlaces falsos.
    $upcoming = [['Calendarios', 'calendar'], ['Partidos y resultados', 'scoreboard'], ['Posiciones', 'list'], ['Mesa de anotación', 'clipboard']];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>@include('partials.head', ['title' => $title])<meta name="robots" content="noindex"></head>
<body class="min-h-dvh bg-ink-950" x-data="{ nav: false }">
<a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[70] btn-primary">Saltar al contenido</a>

{{-- Menú lateral --}}
<div x-show="nav" x-transition.opacity class="fixed inset-0 z-40 bg-black/60 lg:hidden" @click="nav = false" x-cloak></div>
<aside :class="nav ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r divider bg-ink-900 transition-transform duration-300 lg:translate-x-0" aria-label="Menú administrativo">
    <div class="flex h-16 items-center justify-between gap-3 border-b divider px-5">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5" aria-label="Panel HR Media Score">
            <img src="{{ asset('assets/branding/hr-logo-horizontal-400.webp') }}" srcset="{{ asset('assets/branding/hr-logo-horizontal-800.webp') }} 2x" alt="HR Media Score" width="400" height="129" class="h-9 w-auto">
        </a>
        <button class="btn-ghost btn-sm lg:hidden" @click="nav = false" aria-label="Cerrar menú"><x-icon name="x" /></button>
    </div>
    @if ($u->club)
        <div class="mx-4 mt-4 flex items-center gap-3 rounded-xl bg-white/[.03] px-3 py-2.5 ring-1 ring-white/5">
            <span class="grid size-8 place-items-center rounded-lg bg-brand/15 font-display text-sm font-bold text-brand-400">{{ $u->club->short_name ?? mb_substr($u->club->name, 0, 2) }}</span>
            <div class="min-w-0"><p class="truncate text-sm font-semibold">{{ $u->club->name }}</p><p class="text-xs text-mute">{{ $u->roleLabel() }}</p></div>
        </div>
    @endif
    <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-5">
        @foreach ($groups as $group => $items)
            @php $visible = array_filter($items, fn ($i) => $i[4] === null || $u->can($i[4])); @endphp
            @if ($visible)
                <div>
                    <p class="eyebrow mb-2 px-3 text-[10px]">{{ $group }}</p>
                    <div class="space-y-1">
                        @foreach ($visible as [$route, $label, $icon, $pattern])
                            <a href="{{ route($route) }}" @class(['side-link', 'side-link-active' => request()->routeIs($pattern)]) @if(request()->routeIs($pattern)) aria-current="page" @endif>
                                <x-icon :name="$icon" class="size-[18px]" />{{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
        <div>
            <p class="eyebrow mb-2 px-3 text-[10px]">Próximamente · Fase 2–3</p>
            <div class="space-y-1">
                @foreach ($upcoming as [$label, $icon])
                    <span class="side-link cursor-not-allowed opacity-40" aria-disabled="true"><x-icon :name="$icon" class="size-[18px]" />{{ $label }}</span>
                @endforeach
            </div>
        </div>
    </nav>
    <div class="border-t divider p-4">
        <a href="{{ route('home') }}" target="_blank" class="side-link"><x-icon name="external" class="size-[18px]" />Ver portal público</a>
    </div>
</aside>

<div class="lg:pl-72">
    <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b divider bg-ink-950/85 px-4 backdrop-blur-xl sm:px-6">
        <button class="btn-ghost btn-sm lg:hidden" @click="nav = true" aria-label="Abrir menú"><x-icon name="menu" class="size-6" /></button>
        <nav class="hidden min-w-0 items-center gap-1.5 text-sm text-mute sm:flex" aria-label="Ruta">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-white">Panel</a>
            @foreach ($breadcrumbs as $label => $url)
                <x-icon name="chevron-right" class="size-3.5 shrink-0" />
                @if ($url)<a href="{{ $url }}" class="truncate hover:text-white">{{ $label }}</a>@else<span class="truncate text-white">{{ $label }}</span>@endif
            @endforeach
        </nav>
        <div class="ml-auto flex items-center gap-2" x-data="{ open: false }" @click.outside="open = false">
            <button @click="open = !open" class="flex items-center gap-2.5 rounded-xl px-2 py-1.5 hover:bg-white/5" :aria-expanded="open" aria-haspopup="menu">
                <span class="grid size-8 place-items-center rounded-full bg-brand font-display text-sm font-bold">{{ $u->initials() }}</span>
                <span class="hidden text-left sm:block"><span class="block text-sm font-semibold leading-tight">{{ $u->name }}</span><span class="block text-xs text-mute">{{ $u->roleLabel() }}</span></span>
                <x-icon name="chevron-down" class="size-4 text-mute" />
            </button>
            <div x-show="open" x-transition.origin.top.right x-cloak role="menu" class="absolute right-4 top-14 w-56 rounded-xl border divider bg-ink-800 p-1.5 shadow-2xl sm:right-6">
                <a href="{{ route('admin.settings.edit') }}" class="side-link" role="menuitem"><x-icon name="settings" class="size-4" />Mi cuenta</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="side-link w-full" role="menuitem"><x-icon name="logout" class="size-4" />Cerrar sesión</button>
                </form>
            </div>
        </div>
    </header>

    <main id="contenido" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8">
        @if ($header)
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <h1 class="heading text-3xl sm:text-4xl">{{ $header }}</h1>
                    @isset($subheader)<p class="mt-1 text-sm text-mute">{{ $subheader }}</p>@endisset
                </div>
                @if ($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endif
            </div>
        @endif
        {{ $slot }}
    </main>
</div>
<x-flash />
@livewireScripts
</body>
</html>
