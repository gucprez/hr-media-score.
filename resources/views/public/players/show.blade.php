{{-- Ficha pública: no muestra fecha de nacimiento, documento ni datos del tutor (protección de menores). --}}
<x-layouts.public :title="$player->full_name">
    @php $team = $current?->tournamentTeam->team; @endphp
    <section class="relative overflow-hidden border-b divider">
        <div class="absolute inset-0 opacity-35" style="background: radial-gradient(60% 90% at 80% 10%, {{ $team?->primary_color ?? '#D92332' }}, transparent 65%)"></div>
        <div class="relative mx-auto flex max-w-7xl flex-col items-center gap-8 px-4 py-12 sm:flex-row sm:px-6">
            <x-player-avatar :player="$player" size="xl" class="size-40! text-5xl!" />
            <div class="text-center sm:text-left">
                @if ($current)<p class="font-score text-6xl text-sport">#{{ $current->jersey_number }}</p>@endif
                <h1 class="font-display text-5xl font-extrabold uppercase leading-none sm:text-6xl">{{ $player->first_name }}<br><span class="text-mute">{{ $player->last_name }}</span></h1>
                <div class="mt-4 flex flex-wrap justify-center gap-2 sm:justify-start">
                    @if ($team)<a href="{{ route('public.teams.show', [$team->id, $team->slug]) }}" class="badge-muted normal-case tracking-normal hover:text-white"><x-team-badge :team="$team" size="xs" />{{ $team->name }}</a>@endif
                    @if ($current?->position ?? $player->position)<span class="badge-scheduled">{{ ($current?->position ?? $player->position)->label() }}</span>@endif
                    @if ($player->height_cm)<span class="badge-muted">{{ number_format($player->height_cm / 100, 2) }} m</span>@endif
                    @if ($current?->is_reinforcement)<span class="badge-muted">Refuerzo</span>@endif
                </div>
            </div>
        </div>
    </section>
    <div class="mx-auto max-w-7xl space-y-12 px-4 py-12 sm:px-6">
        <section>
            <x-section-title title="Estadísticas por torneo" />
            @if ($stats->isEmpty())
                <x-empty-state icon="chart" title="Sin estadísticas aún">Las estadísticas individuales se registrarán con la mesa de anotación digital.</x-empty-state>
            @else
                <div class="card overflow-x-auto">
                    <table class="table-base min-w-[560px]">
                        <thead><tr><th>Torneo</th><th class="text-center">PJ</th><th class="text-center">PTS</th><th class="text-center">PPJ</th><th class="text-center">REB</th><th class="text-center">AST</th><th class="text-center">ROB</th><th class="text-center">TAP</th></tr></thead>
                        <tbody>
                            @foreach ($history as $e)
                                @if ($s = $stats->get($e->tournament_id))
                                    <tr><td class="font-semibold">{{ $e->tournament->fullName() }}</td>
                                        <td class="text-center font-score text-lg">{{ $s->gp }}</td><td class="text-center font-score text-lg">{{ $s->pts }}</td>
                                        <td class="text-center font-score text-lg text-sport">{{ number_format($s->pts / max(1, $s->gp), 1) }}</td>
                                        <td class="text-center font-score text-lg">{{ $s->reb }}</td><td class="text-center font-score text-lg">{{ $s->ast }}</td><td class="text-center font-score text-lg">{{ $s->stl }}</td><td class="text-center font-score text-lg">{{ $s->blk }}</td></tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
        <section>
            <x-section-title title="Historial deportivo" />
            <ol class="relative space-y-4 border-l-2 border-white/10 pl-6">
                @foreach ($history as $e)
                    <li class="relative">
                        <span class="absolute -left-[31px] top-5 size-3 rounded-full bg-brand ring-4 ring-ink-950"></span>
                        <div class="card flex items-center gap-4 p-4">
                            <x-team-badge :team="$e->tournamentTeam->team" />
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold">{{ $e->tournamentTeam->displayName() }}</p>
                                <a href="{{ route('public.tournaments.show', [$e->tournament->id, $e->tournament->slug]) }}" class="text-sm text-mute hover:text-white">{{ $e->tournament->fullName() }}{{ $e->tournament->season ? ' · '.$e->tournament->season->name : '' }}</a>
                            </div>
                            <span class="font-score text-3xl">#{{ $e->jersey_number }}</span>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>
</x-layouts.public>
