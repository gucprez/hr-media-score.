@php $done = $game->status->isFinished(); @endphp
<div class="card relative overflow-hidden">
    <div class="pointer-events-none absolute inset-0" style="background: linear-gradient(90deg, rgba(217,35,50,.12), transparent 35%, transparent 65%, rgba(31,91,216,.12))"></div>
    <div class="relative flex flex-wrap items-center justify-between gap-2 border-b divider px-4 py-2.5">
        <p class="text-sm"><span class="font-semibold">{{ $game->scheduled_at ? $game->scheduled_at->translatedFormat('D j M') : 'Fecha por definir' }}</span>
            <span class="text-mute">· {{ $game->scheduled_at && $game->scheduled_at->format('H:i') !== '00:00' ? $game->scheduled_at->format('g:i A') : 'Hora por definir' }}@if ($game->series_game_number) · Juego {{ $game->series_game_number }}@endif @if ($game->venue) · {{ $game->venue->name }}@endif</span></p>
        <div class="flex flex-wrap items-center gap-2">
            <span class="{{ $game->status->badgeClass() }}">{{ $game->status->label() }}</span>
            @if ($game->scorekeeper)<span class="badge-muted normal-case tracking-normal" title="Anotador asignado"><x-icon name="clipboard" class="size-3" />{{ \Illuminate\Support\Str::before($game->scorekeeper->name, ' ') }}</span>@endif
            @can('recordResult', $game)<a href="{{ route('admin.tournaments.games.result', [$game->tournament_id, $game]) }}" class="btn-primary btn-sm"><x-icon name="scoreboard" class="size-4" />{{ $done ? 'Corregir' : 'Resultado' }}</a>@endcan
            @can('update', $game)<a href="{{ route('admin.tournaments.games.edit', [$game->tournament_id, $game]) }}" class="btn-ghost btn-sm" aria-label="Editar partido"><x-icon name="pencil" class="size-4" /></a>@endcan
            @if (! $game->series_id)@can('delete', $game)<x-confirm-delete :action="route('admin.tournaments.games.destroy', [$game->tournament_id, $game])" label="" message="¿Eliminar este partido? Si tenía resultado, la tabla se recalculará." />@endcan @endif
        </div>
    </div>
    <div class="relative grid grid-cols-[1fr_auto_1fr] items-center gap-3 px-4 py-3.5">
        <span class="flex min-w-0 items-center justify-end gap-2.5 text-right"><span class="truncate font-display text-lg font-bold uppercase">{{ $game->homeTeam->displayName() }}</span><x-team-badge :team="$game->homeTeam->team" size="sm" class="glow-home rounded-full! ring-0!" /></span>
        @if ($done || $game->status === App\Enums\GameStatus::Live)
            <span class="font-score whitespace-nowrap text-3xl"><span @class(['text-white/45' => $done && $game->home_score < $game->away_score])>{{ $game->home_score }}</span><span class="mx-1.5 text-mute">–</span><span @class(['text-white/45' => $done && $game->away_score < $game->home_score])>{{ $game->away_score }}</span></span>
        @else
            <span class="vs-mark px-2 text-3xl">VS</span>
        @endif
        <span class="flex min-w-0 items-center gap-2.5"><x-team-badge :team="$game->awayTeam->team" size="sm" class="glow-away rounded-full! ring-0!" /><span class="truncate font-display text-lg font-bold uppercase">{{ $game->awayTeam->displayName() }}</span></span>
    </div>
</div>
