<x-layouts.public title="Torneos">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <p class="eyebrow">Competiciones</p>
        <h1 class="heading mt-1 text-4xl sm:text-5xl">Torneos</h1>
        <div class="mt-8">
            @if ($tournaments->isEmpty())
                <x-empty-state icon="trophy" title="No hay torneos publicados">Vuelva pronto.</x-empty-state>
            @else
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">@foreach ($tournaments as $t)@include('public.partials.tournament-card', ['t' => $t])@endforeach</div>
                <div class="mt-8">{{ $tournaments->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.public>
