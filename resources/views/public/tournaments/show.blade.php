@php $r = $tournament->rules; @endphp
<x-layouts.public :title="$tournament->fullName()" :description="$tournament->motto">
    <section class="relative overflow-hidden border-b divider">
        <div class="glow-red absolute inset-0"></div><div class="court-lines absolute inset-0"></div>
        <div class="relative mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14">
            <a href="{{ route('public.tournaments.index') }}" class="inline-flex items-center gap-1 text-sm text-mute hover:text-white"><x-icon name="chevron-left" class="size-4" />Torneos</a>
            <div class="mt-4 flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div class="animate-fade-up">
                    <span class="{{ $tournament->status->badgeClass() }}">{{ $tournament->status->label() }}</span>
                    <h1 class="mt-3 font-display font-extrabold uppercase leading-[.9]">
                        @if ($tournament->edition)<span class="block text-6xl text-transparent [-webkit-text-stroke:2px_var(--color-brand)] sm:text-8xl">{{ $tournament->edition }}</span>@endif
                        <span class="block text-4xl sm:text-5xl">{{ $tournament->name }}</span>
                    </h1>
                    @if ($tournament->motto)<p class="mt-3 font-display text-xl italic text-mute">«{{ $tournament->motto }}»</p>@endif
                </div>
                <dl class="grid grid-cols-3 gap-3 text-center">
                    <div class="card px-4 py-3"><dt class="eyebrow text-[10px]">Equipos</dt><dd class="font-score text-3xl">{{ $registrations->count() }}</dd></div>
                    <div class="card px-4 py-3"><dt class="eyebrow text-[10px]">Partidos</dt><dd class="font-score text-3xl">{{ $games->count() }}</dd></div>
                    <div class="card px-4 py-3"><dt class="eyebrow text-[10px]">Edad</dt><dd class="font-score text-3xl">{{ $r?->min_age ? $r->min_age.'–'.$r->max_age : '—' }}</dd></div>
                </dl>
            </div>
            <div class="mt-6 flex flex-wrap gap-2 text-sm">
                <span class="badge-muted normal-case tracking-normal"><x-icon name="building" class="size-3.5" />Organiza: {{ $tournament->club->name }}</span>
                @if ($tournament->venue)<span class="badge-muted normal-case tracking-normal"><x-icon name="map-pin" class="size-3.5" />{{ $tournament->venue->name }}{{ $tournament->venue->city ? ', '.$tournament->venue->city : '' }}</span>@endif
                @if ($tournament->category)<span class="badge-muted normal-case tracking-normal">{{ $tournament->category }} · {{ $tournament->gender->label() }}</span>@endif
                @if ($tournament->season)<span class="badge-muted normal-case tracking-normal">{{ $tournament->season->name }}</span>@endif
            </div>
        </div>
    </section>

    <nav class="sticky top-16 z-40 border-b divider bg-ink-950/90 backdrop-blur" aria-label="Secciones del torneo">
        <div class="mx-auto flex max-w-7xl gap-1 overflow-x-auto px-4 scrollbar-none sm:px-6">
            @foreach (['equipos' => 'Equipos', 'calendario' => 'Calendario y resultados', 'posiciones' => 'Posiciones', 'reglamento' => 'Reglamento'] as $id => $label)
                <a href="#{{ $id }}" class="shrink-0 px-3 py-3.5 text-sm font-semibold text-mute hover:text-white">{{ $label }}</a>
            @endforeach
        </div>
    </nav>

    <div class="mx-auto max-w-7xl space-y-16 px-4 py-12 sm:px-6">
        <section id="equipos" class="scroll-mt-32">
            <x-section-title title="Equipos y plantillas" />
            @if ($registrations->isEmpty())
                <x-empty-state icon="shield" title="Sin equipos confirmados" />
            @else
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($registrations as $reg)
                        <div class="card overflow-hidden" x-data="{ open: false }">
                            <div class="flex items-center gap-4 p-5" style="background: linear-gradient(110deg, {{ $reg->team->primary_color ?? '#222D41' }}33, transparent 60%)">
                                <x-team-badge :team="$reg->team" size="lg" />
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('public.teams.show', [$reg->team->id, $reg->team->slug]) }}" class="font-display text-2xl font-bold uppercase hover:text-sport">{{ $reg->displayName() }}</a>
                                    <p class="text-sm text-mute">{{ $reg->coach() ? 'DT '.$reg->coach().' · ' : '' }}{{ $reg->activeRoster->count() }} jugadores</p>
                                </div>
                                @if ($reg->activeRoster->isNotEmpty())
                                    <button type="button" @click="open = !open" class="btn-ghost btn-sm" :aria-expanded="open"><span x-text="open ? 'Ocultar' : 'Plantilla'">Plantilla</span><x-icon name="chevron-down" class="size-4 transition" ::class="open && 'rotate-180'" /></button>
                                @endif
                            </div>
                            @if ($reg->activeRoster->isNotEmpty())
                                <ul x-show="open" x-collapse x-cloak class="divide-y divide-white/5 border-t divider">
                                    @foreach ($reg->activeRoster as $e)
                                        <li><a href="{{ route('public.players.show', [$e->player->id, $e->player->slug]) }}" class="flex items-center gap-3 px-5 py-2.5 hover:bg-white/[.03]">
                                            <span class="w-8 text-right font-score text-xl text-mute">{{ $e->jersey_number }}</span>
                                            <x-player-avatar :player="$e->player" size="sm" />
                                            <span class="flex-1 font-medium">{{ $e->player->full_name }}</span>
                                            <span class="text-xs text-mute">{{ $e->position?->label() }}</span>
                                            @if ($e->is_reinforcement)<span class="badge-scheduled text-[9px]">Refuerzo</span>@endif
                                        </a></li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="border-t divider px-5 py-3 text-sm text-mute">Plantilla por publicar.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section id="calendario" class="scroll-mt-32">
            <x-section-title title="Calendario y resultados" />
            @if ($games->isEmpty())
                <x-empty-state icon="calendar" title="Calendario por publicar">La organización publicará aquí las fechas, horarios y resultados oficiales.</x-empty-state>
            @else
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@foreach ($games as $g)<x-game-card :game="$g" />@endforeach</div>
            @endif
        </section>

        <section id="posiciones" class="scroll-mt-32">
            <x-section-title title="Tabla de posiciones" />
            @include('public.partials.standings', ['standings' => $standings, 'registrations' => $registrations, 'rules' => $r])
        </section>

        <section id="reglamento" class="scroll-mt-32">
            <x-section-title title="Reglamento" />
            @if ($r)
                <div class="grid gap-4 md:grid-cols-3">
                    @php
                        $blocks = [
                            ['users', 'Participación', array_filter([
                                ($r->min_age || $r->max_age) ? 'Jugadores de '.($r->min_age ?? '—').' a '.($r->max_age ?? '—').' años.' : null,
                                'Máximo '.$r->max_roster_size.' jugadores por equipo.',
                                $r->max_reinforcements ? $r->max_reinforcements.' refuerzos por equipo.' : 'No se permiten refuerzos.',
                                $r->min_minutes_per_player ? 'Participación mínima de '.$r->min_minutes_per_player.' minutos por jugador.' : null,
                            ])],
                            ['trophy', 'Formato', array_filter([
                                $r->regular_season_format === 'round_robin' ? 'Serie regular todos contra todos'.($r->games_per_team ? " ({$r->games_per_team} partidos por equipo)." : '.') : null,
                                $r->teams_qualifying ? "Clasifican {$r->teams_qualifying} equipos." : null,
                                $r->direct_final_seeds ? 'El 1.er lugar avanza directamente a la final.' : null,
                                $r->semifinal_best_of ? ($r->direct_final_seeds && $r->teams_qualifying == 3 ? '2.º y 3.er lugar disputan la semifinal' : 'Semifinal').' al mejor de '.$r->semifinal_best_of.'.' : null,
                                $r->final_best_of ? 'Final al mejor de '.$r->final_best_of.'.' : null,
                            ])],
                            ['whistle', 'Juego', [
                                "{$r->periods} períodos de {$r->period_minutes} minutos; prórroga de {$r->overtime_minutes} minutos.",
                                "Eliminación con {$r->personal_foul_limit} faltas personales.",
                                "Expulsión con {$r->technical_foul_ejection} faltas técnicas.",
                                "Puntos: victoria {$r->points_per_win}, derrota {$r->points_per_loss}, forfeit {$r->points_per_forfeit}.",
                            ]],
                        ];
                    @endphp
                    @foreach ($blocks as [$icon, $title, $items])
                        <div class="card p-6">
                            <div class="mb-4 flex items-center gap-3"><span class="grid size-10 place-items-center rounded-xl bg-brand/15 text-brand-400"><x-icon :name="$icon" /></span><h3 class="heading text-lg">{{ $title }}</h3></div>
                            <ul class="space-y-2 text-sm text-mute">@foreach ($items as $it)<li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-sport" />{{ $it }}</li>@endforeach</ul>
                        </div>
                    @endforeach
                </div>
                @if ($r->sanctions_policy || $r->punctuality_policy || $r->additional_rules)
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        @foreach (['Sanciones' => $r->sanctions_policy, 'Puntualidad y disponibilidad' => $r->punctuality_policy, 'Disposiciones adicionales' => $r->additional_rules] as $h => $txt)
                            @if ($txt)<div @class(['card p-6', 'md:col-span-2' => $h === 'Disposiciones adicionales'])><h3 class="heading mb-3 text-lg">{{ $h }}</h3><p class="whitespace-pre-line text-sm leading-relaxed text-mute">{{ $txt }}</p></div>@endif
                        @endforeach
                    </div>
                @endif
            @endif
        </section>
    </div>
</x-layouts.public>
