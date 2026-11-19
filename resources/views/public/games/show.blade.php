@php $home = $game->homeTeam; $away = $game->awayTeam; $done = $game->status->isFinished(); $live = $game->status === App\Enums\GameStatus::Live; @endphp
<x-layouts.public :title="$home->displayName().' vs '.$away->displayName()">
    <section class="relative overflow-hidden border-b divider">
        <div class="absolute inset-0" style="background: linear-gradient(90deg, {{ $home->team->primary_color ?? '#222D41' }}40, transparent 40%, transparent 60%, {{ $away->team->primary_color ?? '#222D41' }}40)"></div>
        <div class="relative mx-auto max-w-5xl px-4 py-10 sm:px-6 sm:py-14">
            <p class="text-center"><a href="{{ route('public.tournaments.show', [$game->tournament->id, $game->tournament->slug]) }}" class="eyebrow hover:text-white">{{ $game->tournament->fullName() }} · {{ $game->stage->label() }}</a></p>
            <div class="mt-6 grid grid-cols-[1fr_auto_1fr] items-center gap-4">
                @foreach ([[$home, $game->home_score, 'Local'], null, [$away, $game->away_score, 'Visitante']] as $side)
                    @if ($side === null)
                        <div class="text-center">
                            @if ($done || $live)
                                <p class="font-score text-5xl sm:text-8xl">{{ $game->home_score }}<span class="mx-2 text-mute">–</span>{{ $game->away_score }}</p>
                                <span class="{{ $live ? 'badge-live' : 'badge-final' }} mt-2">{{ $live ? 'En vivo' : 'Final' }}</span>
                            @else
                                <p class="font-display text-3xl font-bold text-mute sm:text-5xl">VS</p>
                                <span class="{{ $game->status->badgeClass() }} mt-2">{{ $game->status->label() }}</span>
                            @endif
                        </div>
                    @else
                        <a href="{{ route('public.teams.show', [$side[0]->team->id, $side[0]->team->slug]) }}" class="flex flex-col items-center gap-3 text-center">
                            <x-team-badge :team="$side[0]->team" size="xl" class="size-16! sm:size-28!" />
                            <span class="font-display text-lg font-bold uppercase sm:text-3xl">{{ $side[0]->displayName() }}</span>
                            <span class="text-xs text-mute">{{ $side[2] }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
            <p class="mt-6 text-center text-sm text-mute">{{ $game->scheduled_at ? $game->scheduled_at->translatedFormat('l j \d\e F, g:i A') : 'Fecha por confirmar' }}{{ $game->venue ? ' · '.$game->venue->name : '' }}</p>
        </div>
    </section>
    <div class="mx-auto max-w-5xl space-y-10 px-4 py-10 sm:px-6">
        @if ($game->periods->isNotEmpty())
            <section>
                <x-section-title title="Por períodos" />
                <div class="card overflow-x-auto">
                    <table class="table-base text-center">
                        <thead><tr><th class="text-left">Equipo</th>@foreach ($game->periods as $p)<th class="text-center">{{ $p->label() }}</th>@endforeach<th class="text-center">T</th></tr></thead>
                        <tbody>
                            <tr><td class="text-left font-semibold">{{ $home->displayName() }}</td>@foreach ($game->periods as $p)<td class="font-score text-lg">{{ $p->home_points }}</td>@endforeach<td class="font-score text-xl">{{ $game->home_score }}</td></tr>
                            <tr><td class="text-left font-semibold">{{ $away->displayName() }}</td>@foreach ($game->periods as $p)<td class="font-score text-lg">{{ $p->away_points }}</td>@endforeach<td class="font-score text-xl">{{ $game->away_score }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
        <section>
            <x-section-title title="Estadísticas individuales" />
            @if ($game->playerStats->isEmpty())
                <x-empty-state icon="chart" title="Sin estadísticas">Disponibles cuando el partido se registre en la mesa de anotación digital.</x-empty-state>
            @else
                <div class="card overflow-x-auto">
                    <table class="table-base min-w-[520px]">
                        <thead><tr><th>Jugador</th><th class="text-center">PTS</th><th class="text-center">REB</th><th class="text-center">AST</th><th class="text-center">ROB</th><th class="text-center">TAP</th><th class="text-center">FP</th></tr></thead>
                        <tbody>@foreach ($game->playerStats as $s)<tr><td class="font-semibold">{{ $s->player->full_name }}</td><td class="text-center font-score text-lg">{{ $s->points }}</td><td class="text-center font-score text-lg">{{ $s->rebounds() }}</td><td class="text-center font-score text-lg">{{ $s->assists }}</td><td class="text-center font-score text-lg">{{ $s->steals }}</td><td class="text-center font-score text-lg">{{ $s->blocks }}</td><td class="text-center font-score text-lg">{{ $s->personal_fouls }}</td></tr>@endforeach</tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-layouts.public>
