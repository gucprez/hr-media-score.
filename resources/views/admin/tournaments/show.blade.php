@php $r = $tournament->rules; @endphp
<x-layouts.admin :title="$tournament->fullName()" :breadcrumbs="['Torneos' => route('admin.tournaments.index'), $tournament->fullName() => null]">
    <div class="card relative mb-6 overflow-hidden">
        <div class="glow-red absolute inset-0"></div>
        <div class="relative flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:p-8">
            <div class="grid size-20 shrink-0 place-items-center rounded-2xl bg-ink-900/80 ring-1 ring-white/10">
                @if ($tournament->logoUrl())<img src="{{ $tournament->logoUrl() }}" alt="" class="size-16 object-contain">@else<x-icon name="trophy" class="size-9 text-sport" />@endif
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap gap-2"><span class="{{ $tournament->status->badgeClass() }}">{{ $tournament->status->label() }}</span><span class="badge-muted">{{ $tournament->is_public ? 'Público' : 'Privado' }}</span></div>
                <h1 class="heading mt-2 text-3xl sm:text-4xl">{{ $tournament->fullName() }}</h1>
                @if ($tournament->motto)<p class="mt-1 text-lg italic text-mute">«{{ $tournament->motto }}»</p>@endif
                <p class="mt-2 text-sm text-mute">{{ collect([$tournament->club->name, $tournament->category, $tournament->gender?->label(), $tournament->venue?->name, $tournament->season?->name])->filter()->implode(' · ') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($tournament->is_public && $tournament->status->value !== 'draft')<a href="{{ route('public.tournaments.show', [$tournament->id, $tournament->slug]) }}" target="_blank" class="btn-secondary"><x-icon name="external" class="size-4" />Portal</a>@endif
                <a href="{{ route('admin.tournaments.games.index', $tournament) }}" class="btn-primary"><x-icon name="calendar" class="size-4" />Calendario y resultados</a>
                @can('update', $tournament)<a href="{{ route('admin.tournaments.edit', $tournament) }}" class="btn-secondary"><x-icon name="pencil" class="size-4" />Editar</a>@endcan
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2">
            <x-section-title title="Equipos inscritos" :href="auth()->user()->can('manageRosters', $tournament) ? route('admin.tournaments.registrations', $tournament) : null" link-text="Gestionar inscripciones" />
            @if ($tournament->registrations->isEmpty())
                <x-empty-state icon="shield" title="Sin equipos inscritos">
                    Inscriba los equipos participantes para armar sus plantillas.
                    <x-slot:action>@can('manageRosters', $tournament)<a href="{{ route('admin.tournaments.registrations', $tournament) }}" class="btn-primary">Inscribir equipos</a>@endcan</x-slot:action>
                </x-empty-state>
            @else
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($tournament->registrations->sortBy(fn ($r) => $r->team->name) as $reg)
                        @php $full = $r && $reg->active_roster_count >= $r->max_roster_size; @endphp
                        <a href="{{ route('admin.tournaments.roster', [$tournament, $reg]) }}" class="card card-hover flex items-center gap-4 p-4">
                            <x-team-badge :team="$reg->team" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold">{{ $reg->displayName() }}</p>
                                <p class="text-xs text-mute">{{ $reg->status->label() }}{{ $reg->coach() ? ' · DT '.$reg->coach() : '' }}</p>
                            </div>
                            <div class="text-right">
                                <p @class(['font-score text-2xl', 'text-success' => $full])>{{ $reg->active_roster_count }}<span class="text-base text-mute">/{{ $r?->max_roster_size ?? '—' }}</span></p>
                                <p class="text-[10px] uppercase tracking-wider text-mute">Plantilla</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <aside class="card h-fit p-5 sm:p-6">
            <h2 class="heading mb-4 text-lg">Reglamento</h2>
            @if ($r)
                <dl class="space-y-3 text-sm">
                    @foreach ([
                        'Edad' => ($r->min_age || $r->max_age) ? ($r->min_age ?? '—').' a '.($r->max_age ?? '—').' años' : 'Libre',
                        'Jugadores por equipo' => 'Máximo '.$r->max_roster_size,
                        'Refuerzos' => $r->max_reinforcements ? $r->max_reinforcements.' por equipo'.($r->reinforcements_exempt_from_age ? ' (exentos de edad)' : '') : 'No permitidos',
                        'Minutos mínimos' => $r->min_minutes_per_player ? $r->min_minutes_per_player.' por jugador' : '—',
                        'Serie regular' => ['round_robin' => 'Todos contra todos', 'groups' => 'Por grupos', 'none' => 'No aplica'][$r->regular_season_format].($r->games_per_team ? " · {$r->games_per_team} partidos" : ''),
                        'Clasifican' => $r->teams_qualifying ? $r->teams_qualifying.' equipos'.($r->direct_final_seeds ? " · {$r->direct_final_seeds}.º directo a la final" : '') : '—',
                        'Semifinal' => $r->semifinal_best_of ? 'Al mejor de '.$r->semifinal_best_of : '—',
                        'Final' => $r->final_best_of ? 'Al mejor de '.$r->final_best_of : '—',
                        'Partido' => "{$r->periods} × {$r->period_minutes} min · prórroga {$r->overtime_minutes} min",
                        'Puntos en tabla' => "V {$r->points_per_win} · D {$r->points_per_loss} · F {$r->points_per_forfeit}",
                    ] as $k => $v)
                        <div class="flex justify-between gap-4 border-b divider pb-3 last:border-0 last:pb-0"><dt class="text-mute">{{ $k }}</dt><dd class="text-right font-medium">{{ $v }}</dd></div>
                    @endforeach
                </dl>
            @endif
            @can('delete', $tournament)
                <div class="mt-6 border-t divider pt-5">
                    <x-confirm-delete :action="route('admin.tournaments.destroy', $tournament)" label="Eliminar torneo" message="¿Eliminar este torneo? Se conservará en el historial (borrado lógico)." />
                </div>
            @endcan
        </aside>
    </div>
</x-layouts.admin>
