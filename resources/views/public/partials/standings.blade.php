{{-- Tabla de posiciones. Sin partidos jugados se muestran los equipos en cero (no se inventan resultados). --}}
@php
    $qualifying = $rules?->teams_qualifying;
    $rows = $standings->isNotEmpty()
        ? $standings->map(fn ($s) => ['reg' => $s->tournamentTeam, 'pj' => $s->played, 'g' => $s->won, 'p' => $s->lost, 'pf' => $s->points_for, 'pc' => $s->points_against, 'dif' => $s->point_diff, 'pts' => $s->standing_points])
        : collect($registrations)->values()->map(fn ($r) => ['reg' => $r, 'pj' => 0, 'g' => 0, 'p' => 0, 'pf' => 0, 'pc' => 0, 'dif' => 0, 'pts' => 0]);
@endphp
@if ($rows->isEmpty())
    <x-empty-state icon="list" title="Sin equipos">La tabla se mostrará cuando haya equipos inscritos.</x-empty-state>
@else
    <div class="card overflow-x-auto">
        <table class="table-base [&_td]:px-2 [&_th]:px-2 sm:[&_td]:px-4 sm:[&_th]:px-4">
            <thead><tr><th class="w-12 text-center">Pos</th><th>Equipo</th><th class="text-center">PJ</th><th class="text-center">G</th><th class="text-center">P</th><th class="hidden text-center sm:table-cell">PF</th><th class="hidden text-center sm:table-cell">PC</th><th class="hidden text-center min-[400px]:table-cell">DIF</th><th class="text-center">PTS</th></tr></thead>
            <tbody>
                @foreach ($rows as $i => $row)
                    <tr>
                        <td class="text-center"><span @class(['inline-grid size-7 place-items-center rounded-lg font-score text-base', 'bg-brand text-white' => $standings->isNotEmpty() && $qualifying && $i < $qualifying, 'text-mute' => ! ($standings->isNotEmpty() && $qualifying && $i < $qualifying)])>{{ $standings->isNotEmpty() ? $i + 1 : '–' }}</span></td>
                        <td><a href="{{ route('public.teams.show', [$row['reg']->team->id, $row['reg']->team->slug]) }}" class="flex items-center gap-3 font-semibold hover:text-sport"><x-team-badge :team="$row['reg']->team" size="sm" />{{ $row['reg']->displayName() }}</a></td>
                        @foreach (['pj', 'g', 'p'] as $k)<td class="text-center font-score text-lg">{{ $row[$k] }}</td>@endforeach
                        <td class="hidden text-center font-score text-lg text-mute sm:table-cell">{{ $row['pf'] }}</td>
                        <td class="hidden text-center font-score text-lg text-mute sm:table-cell">{{ $row['pc'] }}</td>
                        <td @class(['hidden text-center font-score text-lg min-[400px]:table-cell', 'text-success' => $row['dif'] > 0, 'text-brand-400' => $row['dif'] < 0])>{{ $row['dif'] > 0 ? '+' : '' }}{{ $row['dif'] }}</td>
                        <td class="text-center font-score text-xl text-white">{{ $row['pts'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="border-t divider px-4 py-3 text-xs text-mute">
            @if ($standings->isEmpty())La clasificación se actualizará con el primer resultado oficial. @endif
            @if ($qualifying)Clasifican {{ $qualifying }} equipos{{ $rules?->direct_final_seeds ? '; el 1.º avanza directo a la final' : '' }}.@endif
        </p>
    </div>
@endif
