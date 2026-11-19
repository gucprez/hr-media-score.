@props(['game', 'compact' => false])
@php
    $home = $game->homeTeam; $away = $game->awayTeam;
    $done = $game->status->isFinished();
    $live = $game->status === \App\Enums\GameStatus::Live;
    $homeWon = $done && $game->home_score > $game->away_score;
    $awayWon = $done && $game->away_score > $game->home_score;
@endphp
<a href="{{ route('public.games.show', $game) }}" {{ $attributes->merge(['class' => 'card card-hover group block p-4']) }}>
    <div class="mb-3 flex items-center justify-between gap-2 text-xs text-mute">
        <span class="truncate">{{ $game->stage->label() }}@if ($game->series_game_number) · Juego {{ $game->series_game_number }}@endif</span>
        @if ($live)
            <span class="badge-live"><span class="size-1.5 rounded-full bg-brand-400 animate-pulse-dot"></span>En vivo</span>
        @else
            <span class="{{ $game->status->badgeClass() }}">{{ $done ? 'Final' : $game->status->label() }}</span>
        @endif
    </div>
    @foreach ([[$home, $game->home_score, $homeWon], [$away, $game->away_score, $awayWon]] as [$side, $score, $won])
        <div class="flex items-center gap-3 py-1.5">
            <x-team-badge :team="$side->team" size="sm" />
            <span @class(['flex-1 truncate font-semibold', 'text-white' => $won || ! $done, 'text-mute' => $done && ! $won])>{{ $side->displayName() }}</span>
            @if ($done || $live)
                <span @class(['font-score text-2xl', 'text-white' => $won || $live, 'text-mute' => $done && ! $won])>{{ $score }}</span>
            @endif
        </div>
    @endforeach
    <div class="mt-3 flex items-center gap-2 border-t divider pt-3 text-xs text-mute">
        <x-icon name="calendar" class="size-3.5" />
        <span>{{ $game->scheduled_at ? $game->scheduled_at->translatedFormat('D j M · g:i A') : 'Fecha por confirmar' }}</span>
        @if ($game->venue && ! $compact)<span class="truncate">· {{ $game->venue->name }}</span>@endif
    </div>
</a>
