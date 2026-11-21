@php
    $r = $tournament->rules;
    $dates = $regular->flatten()->merge($playoffGames)->filter(fn ($g) => $g->scheduled_at)->groupBy(fn ($g) => $g->scheduled_at->toDateString())->keys()->sort();
    $playable = $tournament->is_public && $tournament->status->value !== 'draft';
@endphp
<x-layouts.admin :title="'Calendario · '.$tournament->fullName()" header="Calendario y resultados" :breadcrumbs="['Torneos' => route('admin.tournaments.index'), $tournament->fullName() => route('admin.tournaments.show', $tournament), 'Calendario' => null]">
    <x-slot:subheader>{{ $tournament->fullName() }}</x-slot:subheader>
    <x-slot:actions>
        @if ($canManage)<a href="{{ route('admin.tournaments.games.create', $tournament) }}" class="btn-primary"><x-icon name="plus" class="size-4" />Programar partido</a>@endif
    </x-slot:actions>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-8 xl:col-span-2">
            {{-- Generador de calendario --}}
            @if ($canManage)
                <form method="POST" action="{{ route('admin.tournaments.schedule', $tournament) }}" class="card grid gap-4 p-5 sm:grid-cols-[1fr_1fr_auto] sm:items-end" x-data="{ busy: false }" @submit="busy = true">
                    @csrf
                    <div class="sm:col-span-3">
                        <h2 class="heading text-lg">Generar serie regular</h2>
                        <p class="text-sm text-mute">Todos contra todos ({{ $r?->round_robin_legs ?? 1 }} vuelta{{ ($r?->round_robin_legs ?? 1) > 1 ? 's' : '' }}). Respeta los partidos ya programados y solo crea los que faltan.</p>
                    </div>
                    <label class="text-sm"><span class="label">Primera jornada nueva</span><input type="datetime-local" name="start_at" class="input"></label>
                    <label class="text-sm"><span class="label">Días entre jornadas</span><input type="number" name="days_between" value="7" min="1" max="60" class="input"></label>
                    <button class="btn-secondary" :disabled="busy"><x-icon name="calendar" class="size-4" /><span x-text="busy ? 'Generando…' : 'Generar'">Generar</span></button>
                </form>
            @endif

            @if ($dates->isNotEmpty() && $playable)
                <div class="card flex flex-wrap items-center gap-2 p-4">
                    <span class="eyebrow mr-2">Carteleras</span>
                    @foreach ($dates as $d)
                        <a href="{{ route('public.tournaments.poster', [$tournament->id, $d]) }}" target="_blank" class="btn-secondary btn-sm"><x-icon name="image" class="size-4 text-gold" />{{ \Carbon\Carbon::parse($d)->translatedFormat('D j M') }}</a>
                    @endforeach
                </div>
            @endif

            @forelse ($regular as $round => $games)
                <section>
                    <x-section-title :title="$round ? 'Jornada '.$round : 'Sin jornada'" eyebrow="Serie regular" />
                    <div class="space-y-3">@foreach ($games as $g)@include('admin.games.partials.row', ['game' => $g])@endforeach</div>
                </section>
            @empty
                <x-empty-state icon="calendar" title="Sin partidos programados">Genere la serie regular o programe los partidos manualmente.</x-empty-state>
            @endforelse

            @if ($series->isNotEmpty())
                <section>
                    <x-section-title title="Playoffs" />
                    <div class="space-y-6">
                        @foreach ($series->sortBy(fn ($s) => $s->stage->value === 'final' ? 2 : 1) as $s)
                            <div class="card overflow-hidden">
                                <div class="flex flex-wrap items-center justify-between gap-3 border-b divider px-5 py-4" style="background: linear-gradient(90deg, rgba(217,35,50,.15), transparent 50%, rgba(31,91,216,.15))">
                                    <div>
                                        <p class="poster-type text-2xl">{{ $s->label() }} <span class="text-gold">· al mejor de {{ $s->best_of }}</span></p>
                                        <p class="text-sm text-mute">{{ $s->highSeed?->displayName() ?? 'Por definir' }} <b class="font-score text-white">{{ $s->high_wins }}</b> – <b class="font-score text-white">{{ $s->low_wins }}</b> {{ $s->lowSeed?->displayName() ?? 'Por definir' }}</p>
                                    </div>
                                    <span class="{{ $s->status === 'finished' ? 'badge-success' : ($s->status === 'in_progress' ? 'badge-live' : 'badge-muted') }}">{{ $s->statusLabel() }}{{ $s->winner ? ': '.$s->winner->displayName() : '' }}</span>
                                </div>
                                <div class="space-y-3 p-4">
                                    @forelse ($playoffGames->where('series_id', $s->id) as $g)@include('admin.games.partials.row', ['game' => $g])
                                    @empty<p class="text-sm text-mute">Los partidos se crearán cuando se conozca el rival.</p>@endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <aside class="space-y-6">
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between border-b divider px-5 py-4">
                    <h2 class="heading text-lg">Posiciones</h2>
                    @can('update', $tournament)
                        <form method="POST" action="{{ route('admin.tournaments.standings', $tournament) }}">@csrf<button class="btn-ghost btn-sm" title="Recalcular"><x-icon name="history" class="size-4" />Recalcular</button></form>
                    @endcan
                </div>
                @if ($standings->isEmpty())
                    <p class="p-5 text-sm text-mute">La tabla se genera con el primer resultado oficial.</p>
                @else
                    <table class="table-base [&_td]:px-3 [&_th]:px-3">
                        <thead><tr><th>#</th><th>Equipo</th><th class="text-center">G-P</th><th class="text-center">DIF</th><th class="text-center">PTS</th></tr></thead>
                        <tbody>
                            @foreach ($standings as $st)
                                <tr>
                                    <td><span @class(['inline-grid size-6 place-items-center rounded-md font-score', 'bg-brand text-white' => $r?->teams_qualifying && $st->position <= $r->teams_qualifying])>{{ $st->position }}</span></td>
                                    <td><span class="flex items-center gap-2 font-semibold"><x-team-badge :team="$st->tournamentTeam->team" size="xs" />{{ $st->tournamentTeam->displayName() }}</span></td>
                                    <td class="text-center font-score">{{ $st->won }}-{{ $st->lost }}</td>
                                    <td @class(['text-center font-score', 'text-success' => $st->point_diff > 0, 'text-brand-400' => $st->point_diff < 0])>{{ $st->point_diff > 0 ? '+' : '' }}{{ $st->point_diff }}</td>
                                    <td class="text-center font-score text-lg">{{ $st->standing_points }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
                <p class="border-t divider px-5 py-3 text-xs text-mute">Desempate: {{ collect($r?->tiebreakers ?? [])->map(fn ($c) => App\Services\StandingsCalculator::CRITERIA[$c] ?? $c)->implode(' → ') ?: '—' }}</p>
            </div>

            @if ($canManage && $series->isEmpty())
                <form method="POST" action="{{ route('admin.tournaments.playoffs', $tournament) }}" class="card space-y-3 p-5" x-data @submit="if (!confirm('¿Generar los playoffs con la tabla actual? No se podrán regenerar.')) $event.preventDefault()">
                    @csrf
                    <h2 class="heading text-lg">Playoffs</h2>
                    <p class="text-sm text-mute">
                        Clasifican {{ $r?->teams_qualifying ?? '—' }} equipos{{ $r?->direct_final_seeds ? ', el 1.º directo a la final' : '' }}.
                        Semifinal al mejor de {{ $r?->semifinal_best_of ?? '—' }}, final al mejor de {{ $r?->final_best_of ?? '—' }}.
                    </p>
                    <button class="btn-primary w-full"><x-icon name="trophy" class="size-4" />Generar playoffs</button>
                    <p class="hint">Disponible al terminar la serie regular.</p>
                </form>
            @endif
        </aside>
    </div>
</x-layouts.admin>
