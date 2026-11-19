<x-layouts.public :title="$team->name">
    <section class="relative overflow-hidden border-b divider">
        <div class="absolute inset-0 opacity-40" style="background: radial-gradient(70% 90% at 15% 20%, {{ $team->primary_color ?? '#D92332' }}, transparent 65%)"></div>
        <div class="court-lines absolute inset-0"></div>
        <div class="relative mx-auto flex max-w-7xl flex-col items-center gap-6 px-4 py-12 text-center sm:flex-row sm:px-6 sm:text-left">
            <x-team-badge :team="$team" size="xl" class="size-32! text-4xl! sm:size-40!" />
            <div class="animate-fade-up">
                <p class="eyebrow">{{ $team->club->name }}</p>
                <h1 class="font-display text-5xl font-extrabold uppercase leading-none sm:text-7xl">{{ $team->name }}</h1>
                <div class="mt-4 flex flex-wrap justify-center gap-2 sm:justify-start">
                    @if ($team->primary_color)<span class="badge-muted normal-case tracking-normal"><span class="size-3 rounded-full ring-1 ring-white/30" style="background: {{ $team->primary_color }}"></span>{{ $team->primary_color_name ?? 'Principal' }}</span>@endif
                    @foreach ($team->secondary_colors ?? [] as $c)<span class="badge-muted normal-case tracking-normal"><span class="size-3 rounded-full ring-1 ring-white/30" style="background: {{ $c['hex'] ?? '#fff' }}"></span>{{ $c['name'] ?? '' }}</span>@endforeach
                    @if ($current?->coach())<span class="badge-muted normal-case tracking-normal"><x-icon name="whistle" class="size-3.5" />DT {{ $current->coach() }}</span>@endif
                </div>
            </div>
        </div>
    </section>
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-3">
        <section class="lg:col-span-2">
            <x-section-title title="Plantilla" :eyebrow="$current?->tournament->fullName()" />
            @if (! $current || $current->activeRoster->isEmpty())
                <x-empty-state icon="users" title="Plantilla por publicar">La lista oficial de jugadores se publicará al cerrar las inscripciones.</x-empty-state>
            @else
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($current->activeRoster as $e)
                        <a href="{{ route('public.players.show', [$e->player->id, $e->player->slug]) }}" class="card card-hover flex items-center gap-4 p-4">
                            <span class="w-12 text-center font-score text-4xl" style="color: {{ $team->primary_color && $team->primary_color !== '#111111' ? $team->primary_color : '#FF7A00' }}">{{ $e->jersey_number }}</span>
                            <x-player-avatar :player="$e->player" />
                            <div class="min-w-0"><p class="truncate font-semibold">{{ $e->player->full_name }}</p><p class="text-xs text-mute">{{ $e->position?->label() ?? $e->player->position?->label() ?? '—' }}{{ $e->is_captain ? ' · Capitán' : '' }}{{ $e->is_reinforcement ? ' · Refuerzo' : '' }}</p></div>
                        </a>
                    @endforeach
                </div>
            @endif
            <div class="mt-12">
                <x-section-title title="Partidos" />
                @if ($games->isEmpty())
                    <x-empty-state icon="calendar" title="Sin partidos programados" />
                @else
                    <div class="grid gap-3 sm:grid-cols-2">@foreach ($games as $g)<x-game-card :game="$g" />@endforeach</div>
                @endif
            </div>
        </section>
        <aside class="space-y-4">
            <x-section-title title="Torneos" />
            @foreach ($registrations as $reg)
                <a href="{{ route('public.tournaments.show', [$reg->tournament->id, $reg->tournament->slug]) }}" class="card card-hover block p-5">
                    <p class="font-display text-lg font-bold uppercase leading-tight">{{ $reg->tournament->fullName() }}</p>
                    @if ($reg->standing)
                        <p class="mt-2 text-sm text-mute">{{ $reg->standing->won }}G – {{ $reg->standing->lost }}P · {{ $reg->standing->standing_points }} pts</p>
                    @else
                        <p class="mt-2 text-sm text-mute">{{ $reg->tournament->status->label() }}</p>
                    @endif
                </a>
            @endforeach
        </aside>
    </div>
</x-layouts.public>
