{{-- Tarjeta de partido estilo cartelera (compacta). --}}
@props(['game', 'compact' => false])
@php $live = $game->status === \App\Enums\GameStatus::Live; @endphp
<div {{ $attributes->merge(['class' => 'card card-hover relative overflow-hidden']) }}>
    <div class="pointer-events-none absolute inset-0" style="background: radial-gradient(60% 90% at 0% 50%, rgba(217,35,50,.22), transparent 70%), radial-gradient(60% 90% at 100% 50%, rgba(31,91,216,.22), transparent 70%)"></div>
    <div class="relative flex items-center justify-between gap-2 border-b divider px-4 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-mute">
        <span class="truncate">{{ $game->stage->label() }}@if ($game->round_number && $game->stage === \App\Enums\GameStage::Regular) · Jornada {{ $game->round_number }}@endif @if ($game->series_game_number) · Juego {{ $game->series_game_number }}@endif</span>
        @if ($live)<span class="badge-live"><span class="size-1.5 rounded-full bg-brand-400 animate-pulse-dot"></span>En vivo</span>
        @elseif (! $game->status->isFinished() && $game->status !== \App\Enums\GameStatus::Scheduled)<span class="{{ $game->status->badgeClass() }}">{{ $game->status->label() }}</span>@endif
    </div>
    <x-matchup :game="$game" class="relative px-3 py-5 sm:px-4" />
    <div class="relative flex items-center justify-center gap-2 border-t divider px-4 py-2.5 text-xs text-mute">
        <x-icon name="calendar" class="size-3.5" />
        <span>{{ $game->scheduled_at ? $game->scheduled_at->translatedFormat('D j M') : 'Fecha por confirmar' }}</span>
        @if ($game->venue && ! $compact)<span class="truncate">· {{ $game->venue->name }}</span>@endif
    </div>
</div>
