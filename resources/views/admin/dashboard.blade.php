<x-layouts.admin title="Dashboard" header="Dashboard">
    <x-slot:subheader>Hola, {{ auth()->user()->name }}. Este es el estado de {{ auth()->user()->club?->name ?? 'la plataforma' }}.</x-slot:subheader>
    <x-slot:actions>
        @can('create', App\Models\Tournament::class)<a href="{{ route('admin.tournaments.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" />Nuevo torneo</a>@endcan
    </x-slot:actions>

    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <x-stat-card label="Torneos activos" :value="$stats['active_tournaments']" icon="trophy" :href="route('admin.tournaments.index')">{{ $stats['tournaments'] }} en total</x-stat-card>
        <x-stat-card label="Equipos" :value="$stats['teams']" icon="shield" accent="sport" :href="auth()->user()->can('teams.manage') ? route('admin.teams.index') : null" />
        <x-stat-card label="Jugadores" :value="$stats['players']" icon="user" accent="mute" :href="auth()->user()->can('players.manage') ? route('admin.players.index') : null">{{ $stats['rostered'] }} en plantillas</x-stat-card>
        <x-stat-card label="Partidos" :value="$stats['games']" icon="scoreboard" accent="mute" :href="route('admin.games.all')">Calendario y resultados</x-stat-card>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2">
            <x-section-title title="Torneos en curso" :href="route('admin.tournaments.index')" />
            <div class="space-y-3">
                @forelse ($tournaments as $t)
                    <a href="{{ route('admin.tournaments.show', $t) }}" class="card card-hover flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
                        <div class="grid size-14 shrink-0 place-items-center rounded-2xl bg-brand/10 text-brand-400 ring-1 ring-brand/20">
                            @if ($t->logoUrl())<img src="{{ $t->logoUrl() }}" alt="" class="size-12 object-contain">@else<x-icon name="trophy" class="size-7" />@endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2"><span class="{{ $t->status->badgeClass() }}">{{ $t->status->label() }}</span>@if($t->is_featured)<span class="badge-muted"><x-icon name="star" class="size-3" />Destacado</span>@endif</div>
                            <h3 class="mt-1.5 line-clamp-2 font-display leading-tight text-xl font-bold uppercase tracking-wide">{{ $t->fullName() }}</h3>
                            @if ($t->motto)<p class="truncate text-sm italic text-mute">«{{ $t->motto }}»</p>@endif
                        </div>
                        <dl class="grid grid-cols-3 gap-4 text-center sm:w-64">
                            <div><dt class="eyebrow text-[10px]">Equipos</dt><dd class="font-score text-2xl">{{ $t->registrations_count }}</dd></div>
                            <div><dt class="eyebrow text-[10px]">Jugadores</dt><dd class="font-score text-2xl">{{ $t->rosters_count }}</dd></div>
                            <div><dt class="eyebrow text-[10px]">Partidos</dt><dd class="font-score text-2xl">{{ $t->games_count }}</dd></div>
                        </dl>
                    </a>
                @empty
                    <x-empty-state icon="trophy" title="Sin torneos activos">Cree un torneo para comenzar a inscribir equipos.</x-empty-state>
                @endforelse
            </div>

            <div class="mt-8">
                <x-section-title title="Próximos partidos" :href="route('admin.games.all')" />
                @if ($upcoming->isEmpty())
                    <x-empty-state icon="calendar" title="Sin partidos próximos">Programe partidos desde el calendario de cada torneo.</x-empty-state>
                @else
                    <div class="grid gap-3 sm:grid-cols-2">@foreach ($upcoming as $g)<x-game-card :game="$g" compact />@endforeach</div>
                @endif
            </div>
        </section>

        <aside class="space-y-6">
            <div class="card p-5">
                <h2 class="heading mb-4 text-lg">Accesos rápidos</h2>
                <div class="grid grid-cols-2 gap-2">
                    @can('create', App\Models\Team::class)<a href="{{ route('admin.teams.create') }}" class="btn-secondary flex-col whitespace-normal py-4 text-center"><x-icon name="shield" class="text-sport" />Nuevo equipo</a>@endcan
                    @can('create', App\Models\Player::class)<a href="{{ route('admin.players.create') }}" class="btn-secondary flex-col whitespace-normal py-4 text-center"><x-icon name="user" class="text-sport" />Nuevo jugador</a>@endcan
                    @can('viewAny', App\Models\User::class)<a href="{{ route('admin.users.create') }}" class="btn-secondary flex-col whitespace-normal py-4 text-center"><x-icon name="users" class="text-sport" />Nuevo usuario</a>@endcan
                    <a href="{{ route('home') }}" target="_blank" class="btn-secondary flex-col whitespace-normal py-4 text-center"><x-icon name="external" class="text-sport" />Ver portal</a>
                </div>
            </div>
            @can('audit.view')
                <div class="card p-5">
                    <div class="mb-4 flex items-center justify-between"><h2 class="heading text-lg">Actividad reciente</h2><a href="{{ route('admin.audit.index') }}" class="text-xs font-semibold text-mute hover:text-white">Ver todo</a></div>
                    <ol class="space-y-3">
                        @forelse ($activity as $log)
                            <li class="flex gap-3 text-sm">
                                <span @class(['mt-1.5 size-2 shrink-0 rounded-full', 'bg-success' => $log->event === 'created', 'bg-sport' => $log->event === 'updated', 'bg-brand' => in_array($log->event, ['deleted', 'login_failed']), 'bg-mute' => ! in_array($log->event, ['created', 'updated', 'deleted', 'login_failed'])])></span>
                                <div class="min-w-0">
                                    <p class="truncate"><span class="font-semibold">{{ $log->user?->name ?? 'Sistema' }}</span> <span class="text-mute">· {{ mb_strtolower($log->eventLabel()) }}</span></p>
                                    <p class="truncate text-xs text-mute-dim">{{ $log->subjectLabel() }} {{ $log->description ? '· '.$log->description : '' }} · {{ $log->created_at->diffForHumans() }}</p>
                                </div>
                            </li>
                        @empty
                            <li class="text-sm text-mute">Sin actividad registrada.</li>
                        @endforelse
                    </ol>
                </div>
            @endcan
        </aside>
    </div>
</x-layouts.admin>
