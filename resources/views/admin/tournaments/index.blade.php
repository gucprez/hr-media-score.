<x-layouts.admin title="Torneos" header="Torneos" :breadcrumbs="['Torneos' => null]">
    <x-slot:actions>
        @can('create', App\Models\Tournament::class)<a href="{{ route('admin.tournaments.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" />Nuevo torneo</a>@endcan
    </x-slot:actions>
    <form method="GET" class="mb-5 flex flex-col gap-2 sm:flex-row" role="search">
        <label class="relative flex-1"><span class="sr-only">Buscar</span>
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-mute" />
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre, edición o categoría…" class="input pl-9">
        </label>
        <select name="status" class="input sm:w-56" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            @foreach (App\Enums\TournamentStatus::options() as $v => $l)<option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>@endforeach
        </select>
        <button class="btn-secondary">Filtrar</button>
    </form>
    @if ($tournaments->isEmpty())
        <x-empty-state icon="trophy" title="No hay torneos">
            {{ request()->hasAny(['q', 'status']) ? 'Ningún torneo coincide con la búsqueda.' : 'Cree el primer torneo de su organización.' }}
        </x-empty-state>
    @else
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($tournaments as $t)
                <a href="{{ route('admin.tournaments.show', $t) }}" class="card card-hover relative overflow-hidden p-5">
                    @if ($t->is_featured)<span class="absolute right-0 top-0 h-1 w-24 bg-brand"></span>@endif
                    <div class="flex items-start gap-4">
                        <div class="grid size-12 shrink-0 place-items-center rounded-xl bg-white/5 text-sport">
                            @if ($t->logoUrl())<img src="{{ $t->logoUrl() }}" alt="" class="size-10 object-contain">@else<x-icon name="trophy" />@endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap gap-2"><span class="{{ $t->status->badgeClass() }}">{{ $t->status->label() }}</span>@unless($t->is_public)<span class="badge-muted">Privado</span>@endunless</div>
                            <h3 class="mt-2 font-display text-xl font-bold uppercase leading-tight">{{ $t->fullName() }}</h3>
                            <p class="mt-1 text-sm text-mute">{{ collect([$t->category, $t->gender?->label(), $t->season?->name])->filter()->implode(' · ') }}</p>
                            @if (auth()->user()->isSuperAdmin())<p class="mt-1 text-xs text-mute-dim">{{ $t->club->name }}</p>@endif
                        </div>
                    </div>
                    <div class="mt-4 flex gap-6 border-t divider pt-4 text-sm">
                        <span><b class="font-score text-lg">{{ $t->registrations_count }}</b> <span class="text-mute">equipos</span></span>
                        <span><b class="font-score text-lg">{{ $t->rosters_count }}</b> <span class="text-mute">jugadores</span></span>
                        @if ($t->rules)<span class="ml-auto text-mute">Edad {{ $t->rules->min_age ?? '—' }}–{{ $t->rules->max_age ?? '—' }}</span>@endif
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $tournaments->links() }}</div>
    @endif
</x-layouts.admin>
