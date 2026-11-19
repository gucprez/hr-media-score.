<x-layouts.public title="Partidos">
    <div class="mx-auto max-w-7xl space-y-14 px-4 py-10 sm:px-6">
        <div><p class="eyebrow">Calendario</p><h1 class="heading mt-1 text-4xl sm:text-5xl">Partidos</h1></div>
        <section>
            <x-section-title title="Próximos" />
            @if ($upcoming->isEmpty())<x-empty-state icon="calendar" title="Calendario por publicar">Las fechas oficiales aparecerán aquí cuando la organización las confirme.</x-empty-state>
            @else<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@foreach ($upcoming as $g)<x-game-card :game="$g" />@endforeach</div>@endif
        </section>
        <section>
            <x-section-title title="Resultados" />
            @if ($results->isEmpty())<x-empty-state icon="scoreboard" title="Aún no hay resultados" />
            @else<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@foreach ($results as $g)<x-game-card :game="$g" />@endforeach</div>@endif
        </section>
    </div>
</x-layouts.public>
