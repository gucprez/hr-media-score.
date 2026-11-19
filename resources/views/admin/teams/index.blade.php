<x-layouts.admin title="Equipos" header="Equipos" :breadcrumbs="['Equipos' => null]">
    <x-slot:actions>@can('create', App\Models\Team::class)<a href="{{ route('admin.teams.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" />Nuevo equipo</a>@endcan</x-slot:actions>
    <form method="GET" class="mb-5" role="search">
        <label class="relative block"><span class="sr-only">Buscar equipo</span><x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-mute" />
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar equipo…" class="input pl-9 sm:max-w-sm"></label>
    </form>
    @if ($teams->isEmpty())
        <x-empty-state icon="shield" title="No hay equipos">{{ request('q') ? 'Sin resultados para la búsqueda.' : 'Registre los equipos de su organización.' }}</x-empty-state>
    @else
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($teams as $team)
                <a href="{{ route('admin.teams.show', $team) }}" class="card card-hover relative flex items-center gap-4 overflow-hidden p-4">
                    <span class="absolute inset-y-0 left-0 w-1" style="background: {{ $team->primary_color ?? '#222D41' }}"></span>
                    <x-team-badge :team="$team" size="lg" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-display text-xl font-bold uppercase">{{ $team->name }}</p>
                        <p class="truncate text-sm text-mute">{{ $team->coach_name ? 'DT '.$team->coach_name : 'Sin entrenador registrado' }}</p>
                        <p class="mt-1 text-xs text-mute-dim">{{ $team->registrations_count }} torneo(s) @unless($team->is_active)· <span class="text-brand-400">Inactivo</span>@endunless @if(auth()->user()->isSuperAdmin()) · {{ $team->club->name }}@endif</p>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $teams->links() }}</div>
    @endif
</x-layouts.admin>
