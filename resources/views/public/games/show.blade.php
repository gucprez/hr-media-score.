@php $home = $game->homeTeam; $away = $game->awayTeam; @endphp
<x-layouts.public :title="$home->displayName().' vs '.$away->displayName()">
    <section class="arena-bg relative overflow-hidden border-b divider">
        <div class="arena-lights absolute inset-0 opacity-60"></div>
        <div class="relative mx-auto max-w-5xl px-4 py-10 sm:px-6 sm:py-14">
            <p class="text-center"><a href="{{ route('public.tournaments.show', [$game->tournament->id, $game->tournament->slug]) }}" class="eyebrow hover:text-white">{{ $game->tournament->fullName() }}</a></p>
            <p class="mt-2 text-center"><span class="ribbon-gold poster-type inline-block px-8 py-1 text-base not-italic tracking-wider sm:text-xl">{{ $game->stage->label() }}@if ($game->round_number && $game->stage === App\Enums\GameStage::Regular) · Jornada {{ $game->round_number }}@endif @if ($game->series_game_number) · Juego {{ $game->series_game_number }}@endif</span></p>
            <x-matchup :game="$game" size="lg" :link="false" class="mt-8" />
            <p class="mt-8 text-center text-sm text-mute">{{ $game->scheduled_at ? $game->scheduled_at->translatedFormat('l j \d\e F, g:i A') : 'Fecha por confirmar' }}{{ $game->venue ? ' · '.$game->venue->name : '' }}</p>
            @if ($game->scheduled_at)
                <p class="mt-3 text-center"><a href="{{ route('public.tournaments.poster', [$game->tournament->id, $game->scheduled_at->toDateString()]) }}" class="btn-secondary btn-sm"><x-icon name="image" class="size-4 text-gold" />Cartelera del día</a></p>
            @endif
        </div>
    </section>
    <div class="mx-auto max-w-5xl space-y-10 px-4 py-10 sm:px-6">
        @if ($game->periods->isNotEmpty())
            <section>
                <x-section-title title="Por períodos" />
                <div class="card overflow-x-auto">
                    <table class="table-base text-center">
                        <thead><tr><th class="text-left">Equipo</th>@foreach ($game->periods as $p)<th class="text-center">{{ $p->label() }}</th>@endforeach<th class="text-center">T</th></tr></thead>
                        <tbody>
                            <tr><td class="text-left font-semibold">{{ $home->displayName() }}</td>@foreach ($game->periods as $p)<td class="font-score text-lg">{{ $p->home_points }}</td>@endforeach<td class="font-score text-xl">{{ $game->home_score }}</td></tr>
                            <tr><td class="text-left font-semibold">{{ $away->displayName() }}</td>@foreach ($game->periods as $p)<td class="font-score text-lg">{{ $p->away_points }}</td>@endforeach<td class="font-score text-xl">{{ $game->away_score }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
        <section>
            <x-section-title title="Estadísticas individuales" />
            @if ($game->playerStats->isEmpty())
                <x-empty-state icon="chart" title="Sin estadísticas">Disponibles cuando el partido se registre en la mesa de anotación digital.</x-empty-state>
            @else
                <div class="card overflow-x-auto">
                    <table class="table-base min-w-[520px]">
                        <thead><tr><th>Jugador</th><th class="text-center">PTS</th><th class="text-center">REB</th><th class="text-center">AST</th><th class="text-center">ROB</th><th class="text-center">TAP</th><th class="text-center">FP</th></tr></thead>
                        <tbody>@foreach ($game->playerStats as $s)<tr><td class="font-semibold">{{ $s->player->full_name }}</td><td class="text-center font-score text-lg">{{ $s->points }}</td><td class="text-center font-score text-lg">{{ $s->rebounds() }}</td><td class="text-center font-score text-lg">{{ $s->assists }}</td><td class="text-center font-score text-lg">{{ $s->steals }}</td><td class="text-center font-score text-lg">{{ $s->blocks }}</td><td class="text-center font-score text-lg">{{ $s->personal_fouls }}</td></tr>@endforeach</tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-layouts.public>
