@props(['player', 'size' => 'md'])
@php $cls = ['sm' => 'size-9 text-xs', 'md' => 'size-12 text-sm', 'lg' => 'size-20 text-xl', 'xl' => 'size-32 text-4xl'][$size] ?? 'size-12 text-sm'; @endphp
@if ($player->photoUrl())
    <img src="{{ $player->photoUrl() }}" alt="{{ $player->full_name }}" loading="lazy" {{ $attributes->merge(['class' => "$cls shrink-0 rounded-full object-cover ring-2 ring-white/10"]) }}>
@else
    <span {{ $attributes->merge(['class' => "$cls shrink-0 grid place-items-center rounded-full bg-gradient-to-br from-ink-600 to-ink-700 font-display font-bold text-mute ring-2 ring-white/10"]) }}>{{ $player->initials() }}</span>
@endif
