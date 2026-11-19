<x-layouts.admin :title="$team->name" :breadcrumbs="['Equipos' => route('admin.teams.index'), $team->name => null]">
    <div class="card relative mb-6 overflow-hidden">
        <div class="absolute inset-0 opacity-25" style="background: linear-gradient(120deg, {{ $team->primary_color ?? '#222D41' }}, transparent 60%)"></div>
        <div class="relative flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:p-8">
            <x-team-badge :team="$team" size="xl" />
            <div class="min-w-0 flex-1">
                <h1 class="heading text-4xl">{{ $team->name }}</h1>
                <p class="mt-1 text-mute">{{ $team->club->name }}{{ $team->coach_name ? ' · DT '.$team->coach_name : '' }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @if ($team->primary_color)<span class="badge-muted"><span class="size-3 rounded-full ring-1 ring-white/30" style="background: {{ $team->primary_color }}"></span>{{ $team->primary_color_name ?? $team->primary_color }}</span>@endif
                    @foreach ($team->secondary_colors ?? [] as $c)<span class="badge-muted"><span class="size-3 rounded-full ring-1 ring-white/30" style="background: {{ $c['hex'] ?? '#fff' }}"></span>{{ $c['name'] ?? $c['hex'] }}</span>@endforeach
                    @unless ($team->is_active)<span class="badge-live">Inactivo</span>@endunless
                </div>
            </div>
            <div class="flex gap-2">
                @can('update', $team)<a href="{{ route('admin.teams.edit', $team) }}" class="btn-secondary"><x-icon name="pencil" class="size-4" />Editar</a>@endcan
                @can('delete', $team)<x-confirm-delete :action="route('admin.teams.destroy', $team)" message="¿Eliminar definitivamente este equipo?" />@endcan
            </div>
        </div>
    </div>
    <x-section-title title="Participación en torneos" />
    @forelse ($team->registrations as $reg)
        <div class="card mb-4 overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b divider px-5 py-4">
                <div><p class="font-display text-lg font-bold uppercase">{{ $reg->tournament->fullName() }}</p><p class="text-xs text-mute">{{ $reg->status->label() }} · {{ $reg->activeRoster->count() }} jugadores</p></div>
                <a href="{{ route('admin.tournaments.roster', [$reg->tournament, $reg]) }}" class="btn-secondary btn-sm">Gestionar plantilla</a>
            </div>
            @if ($reg->activeRoster->isNotEmpty())
                <div class="flex flex-wrap gap-2 p-4">
                    @foreach ($reg->activeRoster as $e)<a href="{{ route('admin.players.show', $e->player) }}" class="badge-muted normal-case tracking-normal hover:text-white"><b class="font-score text-white">#{{ $e->jersey_number }}</b> {{ $e->player->full_name }}</a>@endforeach
                </div>
            @endif
        </div>
    @empty
        <x-empty-state icon="trophy" title="Sin torneos">Inscriba el equipo desde la ficha de un torneo.</x-empty-state>
    @endforelse
</x-layouts.admin>
