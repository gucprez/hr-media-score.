{{-- Escudo del equipo: logo oficial si existe; si no, iniciales sobre sus colores (no es un logo sustituto). --}}
@props(['team', 'size' => 'md'])
@php
    $sizes = ['xs' => 'size-6 text-[9px]', 'sm' => 'size-8 text-[10px]', 'md' => 'size-11 text-xs', 'lg' => 'size-16 text-base', 'xl' => 'size-24 text-2xl'];
    $cls = $sizes[$size] ?? $sizes['md'];
    $secondary = collect($team?->secondary_colors ?? [])->pluck('hex')->filter()->first();
@endphp
@if ($team?->logoUrl())
    <img src="{{ $team->logoUrl() }}" alt="Logo de {{ $team->name }}" loading="lazy"
         {{ $attributes->merge(['class' => "$cls shrink-0 rounded-xl object-contain bg-white/5 p-1"]) }}>
@else
    <span {{ $attributes->merge(['class' => "$cls shrink-0 grid place-items-center rounded-xl font-display font-bold tracking-wide ring-1 ring-white/10"]) }}
          style="background: {{ $team?->primary_color ?? '#222D41' }}; color: {{ $team?->contrastColor() ?? '#fff' }}; {{ $secondary ? "box-shadow: inset 0 -4px 0 {$secondary};" : '' }}"
          title="{{ $team?->name }}" aria-label="{{ $team?->name }}">{{ $team?->abbreviation() ?? '?' }}</span>
@endif
