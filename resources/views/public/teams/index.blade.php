<x-layouts.public title="Equipos">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <p class="eyebrow">Participantes</p>
        <h1 class="heading mt-1 text-4xl sm:text-5xl">Equipos</h1>
        <div class="mt-8">
            @if ($teams->isEmpty())
                <x-empty-state icon="shield" title="Sin equipos publicados" />
            @else
                <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($teams as $t)
                        <a href="{{ route('public.teams.show', [$t->id, $t->slug]) }}" class="card card-hover group relative overflow-hidden p-6 text-center">
                            <span class="absolute inset-x-0 top-0 h-1.5" style="background: {{ $t->primary_color ?? '#222D41' }}"></span>
                            <x-team-badge :team="$t" size="xl" class="mx-auto transition group-hover:scale-105" />
                            <h2 class="mt-4 font-display text-xl font-bold uppercase sm:text-2xl">{{ $t->name }}</h2>
                            <p class="mt-1 text-xs text-mute">{{ $t->club->name }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.public>
