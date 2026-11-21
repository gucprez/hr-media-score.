@php
    $periods = (int) ($tournament->rules?->periods ?? 4);
    $existing = $game->periods->map(fn ($p) => ['home' => $p->home_points, 'away' => $p->away_points])->values()->all();
    $rows = old('periods', $existing ?: array_fill(0, $periods, ['home' => null, 'away' => null]));
    $mode = old('mode', $game->periods->isNotEmpty() || ! $game->status->isFinished() ? 'periods' : 'total');
@endphp
<x-layouts.admin title="Resultado" header="Resultado del partido" :breadcrumbs="['Torneos' => route('admin.tournaments.index'), $tournament->fullName() => route('admin.tournaments.show', $tournament), 'Calendario' => route('admin.tournaments.games.index', $tournament), 'Resultado' => null]">
    <form method="POST" action="{{ route('admin.tournaments.games.result.update', [$tournament, $game]) }}" class="max-w-4xl space-y-6"
          x-data="{ status: @js(old('status', $game->status->value === 'scheduled' ? 'final' : $game->status->value)), mode: @js($mode), rows: @js(array_values($rows)), regular: {{ $periods }},
                    sum(side) { return this.rows.reduce((a, r) => a + (parseInt(r[side]) || 0), 0) },
                    addOt() { this.rows.push({ home: null, away: null }) }, removeOt() { if (this.rows.length > this.regular) this.rows.pop() } }">
        @csrf @method('PUT')
        @if ($errors->any())<div class="rounded-xl border border-brand/30 bg-brand/10 px-4 py-3 text-sm" role="alert">@foreach ($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif

        <div class="card relative overflow-hidden p-6">
            <div class="arena-bg absolute inset-0 opacity-70"></div>
            <x-matchup :game="$game" size="lg" :link="false" class="relative" />
            <p class="relative mt-4 text-center text-sm text-mute">{{ $game->stage->label() }}@if ($game->series_game_number) · Juego {{ $game->series_game_number }} de {{ $game->series->best_of }}@endif · {{ $game->scheduled_at?->translatedFormat('l j \d\e F, g:i A') ?? 'Fecha por definir' }}</p>
        </div>

        <div class="card space-y-5 p-5 sm:p-6">
            <fieldset>
                <legend class="label">Estado</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach (App\Enums\GameStatus::options() as $v => $l)
                        <label class="cursor-pointer rounded-xl border px-4 py-2 text-sm font-semibold transition" :class="status === '{{ $v }}' ? 'border-brand bg-brand/15 text-white' : 'border-white/10 text-mute hover:text-white'">
                            <input type="radio" name="status" value="{{ $v }}" x-model="status" class="sr-only">{{ $l }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <template x-if="status === 'final' || status === 'live'">
                <div class="space-y-4">
                    <div class="flex gap-1 rounded-xl bg-ink-900 p-1 sm:w-fit">
                        <button type="button" @click="mode = 'periods'" :class="mode === 'periods' ? 'bg-ink-700 text-white' : 'text-mute'" class="btn btn-sm">Por períodos</button>
                        <button type="button" @click="mode = 'total'" :class="mode === 'total' ? 'bg-ink-700 text-white' : 'text-mute'" class="btn btn-sm">Solo marcador final</button>
                    </div>
                    <input type="hidden" name="mode" :value="mode">
                    <div x-show="mode === 'periods'" class="overflow-x-auto">
                        <table class="table-base text-center">
                            <thead><tr><th class="text-left">Equipo</th><template x-for="(r, i) in rows" :key="i"><th class="text-center" x-text="i < regular ? 'C' + (i + 1) : 'TE' + (i - regular + 1)"></th></template><th class="text-center">Total</th></tr></thead>
                            <tbody>
                                @foreach (['home' => $game->homeTeam, 'away' => $game->awayTeam] as $side => $reg)
                                    <tr>
                                        <td class="text-left"><span class="flex items-center gap-2 font-semibold"><span class="size-2.5 rounded-full {{ $side === 'home' ? 'bg-home' : 'bg-away' }}"></span>{{ $reg->displayName() }}</span></td>
                                        <template x-for="(r, i) in rows" :key="i">
                                            <td><input type="number" min="0" max="150" :name="`periods[${i}][{{ $side }}]`" x-model="r.{{ $side }}" class="input w-16 px-2 py-1.5 text-center font-score text-lg" :aria-label="'{{ $reg->displayName() }} período ' + (i + 1)"></td>
                                        </template>
                                        <td class="font-score text-3xl" x-text="sum('{{ $side }}')"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-3 flex gap-2">
                            <button type="button" class="btn-secondary btn-sm" @click="addOt()"><x-icon name="plus" class="size-4" />Prórroga</button>
                            <button type="button" class="btn-ghost btn-sm" x-show="rows.length > regular" @click="removeOt()">Quitar prórroga</button>
                        </div>
                        <p class="mt-2 text-sm text-brand-400" x-show="status === 'final' && sum('home') === sum('away') && sum('home') > 0" x-cloak>Empate: agregue una prórroga.</p>
                    </div>
                    <div x-show="mode === 'total'" class="grid max-w-md grid-cols-2 gap-4">
                        <label><span class="label">{{ $game->homeTeam->displayName() }}</span><input type="number" name="home_score" min="0" max="300" value="{{ old('home_score', $game->home_score) }}" class="input text-center font-score text-3xl"></label>
                        <label><span class="label">{{ $game->awayTeam->displayName() }}</span><input type="number" name="away_score" min="0" max="300" value="{{ old('away_score', $game->away_score) }}" class="input text-center font-score text-3xl"></label>
                    </div>
                </div>
            </template>

            <template x-if="status === 'forfeit'">
                <fieldset class="space-y-2">
                    <legend class="label">¿Quién pierde por forfeit? <span class="font-normal text-mute">(marcador oficial 20–0)</span></legend>
                    <label class="flex items-center gap-2 text-sm"><input type="radio" name="forfeit_loser" value="home" @checked(old('forfeit_loser', $game->forfeit_loser_team_id === $game->home_team_id ? 'home' : null) === 'home') class="text-brand"> {{ $game->homeTeam->displayName() }}</label>
                    <label class="flex items-center gap-2 text-sm"><input type="radio" name="forfeit_loser" value="away" @checked(old('forfeit_loser', $game->forfeit_loser_team_id === $game->away_team_id ? 'away' : null) === 'away') class="text-brand"> {{ $game->awayTeam->displayName() }}</label>
                </fieldset>
            </template>

            <p class="text-sm text-mute" x-show="['scheduled', 'postponed', 'cancelled'].includes(status)" x-cloak>Sin resultado: si el partido tenía marcador, se borrará y la tabla se recalculará.</p>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ url()->previous() }}" class="btn-ghost">Cancelar</a>
            <button class="btn-primary"><x-icon name="check" class="size-4" />Guardar resultado</button>
        </div>
    </form>
</x-layouts.admin>
