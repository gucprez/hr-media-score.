@php $editing = $game->exists; $title = $editing ? 'Editar partido' : 'Programar partido'; @endphp
<x-layouts.admin :title="$title" :header="$title" :breadcrumbs="['Torneos' => route('admin.tournaments.index'), $tournament->fullName() => route('admin.tournaments.show', $tournament), 'Calendario' => route('admin.tournaments.games.index', $tournament), $title => null]">
    <form method="POST" action="{{ $editing ? route('admin.tournaments.games.update', [$tournament, $game]) : route('admin.tournaments.games.store', $tournament) }}" class="card max-w-3xl space-y-5 p-5 sm:p-6">
        @csrf @if ($editing) @method('PUT') @endif
        @if ($game->series_id)
            <p class="rounded-xl bg-sport/10 px-4 py-3 text-sm text-sport">Partido de playoffs: los equipos y la fase los define la serie. Aquí puede ajustar fecha, sede y anotador.</p>
            <input type="hidden" name="home_team_id" value="{{ $game->home_team_id }}"><input type="hidden" name="away_team_id" value="{{ $game->away_team_id }}"><input type="hidden" name="stage" value="{{ $game->stage->value }}">
        @else
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.select name="home_team_id" label="Local (rojo)" :options="$teams" :value="$game->home_team_id" placeholder="Seleccione…" required />
                <x-form.select name="away_team_id" label="Visitante (azul)" :options="$teams" :value="$game->away_team_id" placeholder="Seleccione…" required />
                <x-form.select name="stage" label="Fase" :options="App\Enums\GameStage::options()" :value="$game->stage" required />
                <x-form.input type="number" name="round_number" label="Jornada" :value="$game->round_number" min="1" max="200" />
            </div>
        @endif
        <div class="grid gap-5 sm:grid-cols-3">
            <x-form.input type="date" name="date" label="Fecha" :value="$game->scheduled_at?->toDateString()" />
            <x-form.input type="time" name="time" label="Hora" :value="$game->scheduled_at && $game->scheduled_at->format('H:i') !== '00:00' ? $game->scheduled_at->format('H:i') : null" />
            <x-form.select name="venue_id" label="Sede" :options="$venues" :value="$game->venue_id" placeholder="Sin sede" />
        </div>
        <x-form.select name="scorekeeper_id" label="Anotador asignado" :options="$scorekeepers" :value="$game->scorekeeper_id" placeholder="Sin asignar" hint="El anotador podrá registrar el resultado de este partido." />
        <x-form.textarea name="notes" label="Notas" :value="$game->notes" rows="2" maxlength="2000" />
        <div class="flex justify-end gap-2"><a href="{{ route('admin.tournaments.games.index', $tournament) }}" class="btn-ghost">Cancelar</a><button class="btn-primary"><x-icon name="check" class="size-4" />Guardar</button></div>
    </form>
</x-layouts.admin>
