<x-layouts.public title="Jugadores">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="eyebrow">Plantillas oficiales</p><h1 class="heading mt-1 text-4xl sm:text-5xl">Jugadores</h1></div>
            <form method="GET" role="search" class="relative sm:w-80"><x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-mute" /><input type="search" name="q" value="{{ $search }}" placeholder="Buscar jugador…" class="input pl-9" aria-label="Buscar jugador" minlength="2"></form>
        </div>
        <div class="mt-8">
            @if ($players->isEmpty())
                <x-empty-state icon="user" title="{{ $search ? 'Sin resultados' : 'Plantillas por publicar' }}">{{ $search ? 'Ningún jugador coincide con la búsqueda.' : 'Los jugadores aparecerán cuando los equipos publiquen sus plantillas.' }}</x-empty-state>
            @else
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($players as $p)
                        @php $e = $p->rosterEntries->first(); @endphp
                        <a href="{{ route('public.players.show', [$p->id, $p->slug]) }}" class="card card-hover group relative overflow-hidden p-5 text-center">
                            @if ($e)<span class="absolute right-3 top-2 font-score text-5xl text-white/[.06]">{{ $e->jersey_number }}</span>@endif
                            <x-player-avatar :player="$p" size="lg" class="mx-auto" />
                            <p class="mt-3 truncate font-semibold">{{ $p->full_name }}</p>
                            @if ($e)<p class="mt-1 flex items-center justify-center gap-1.5 text-xs text-mute"><x-team-badge :team="$e->tournamentTeam->team" size="xs" />{{ $e->tournamentTeam->team->name }} · #{{ $e->jersey_number }}</p>@endif
                        </a>
                    @endforeach
                </div>
                <div class="mt-8">{{ $players->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.public>
