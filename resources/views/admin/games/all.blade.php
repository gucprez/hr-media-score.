<x-layouts.admin title="Partidos" header="Partidos" :breadcrumbs="['Partidos' => null]">
    <x-slot:subheader>{{ auth()->user()->can('games.manage') ? 'Todos los partidos de su organización.' : 'Partidos que tiene asignados como anotador.' }}</x-slot:subheader>
    <form method="GET" class="mb-5">
        <select name="status" class="input sm:w-56" onchange="this.form.submit()" aria-label="Estado">
            <option value="">Todos los estados</option>
            @foreach (App\Enums\GameStatus::options() as $v => $l)<option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>@endforeach
        </select>
    </form>
    @if ($games->isEmpty())
        <x-empty-state icon="scoreboard" title="Sin partidos">{{ auth()->user()->can('games.manage') ? 'Programe partidos desde el calendario de cada torneo.' : 'Aún no tiene partidos asignados.' }}</x-empty-state>
    @else
        <div class="space-y-3">
            @foreach ($games as $g)
                <div><p class="mb-1 text-xs text-mute">{{ $g->tournament->fullName() }} · {{ $g->stage->label() }}</p>@include('admin.games.partials.row', ['game' => $g])</div>
            @endforeach
        </div>
        <div class="mt-6">{{ $games->links() }}</div>
    @endif
</x-layouts.admin>
