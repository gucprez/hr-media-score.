<?php

namespace App\Services;

use App\Enums\GameStage;
use App\Enums\GameStatus;
use App\Enums\TournamentStatus;
use App\Models\Game;
use App\Models\PlayoffSeries;
use App\Models\Tournament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Playoffs configurables desde el reglamento:
 *   - 2 clasificados:                final 1.º vs 2.º
 *   - 3 clasificados, 1 pase directo: semifinal 2.º vs 3.º → final contra el 1.º
 *   - 4 clasificados:                 semifinales 1.º–4.º y 2.º–3.º → final
 * Cada serie es al mejor de N; los partidos se crean a medida que hacen falta
 * y el ganador avanza automáticamente.
 */
class PlayoffManager
{
    public function __construct(private StandingsCalculator $standings) {}

    public function generate(Tournament $tournament): Collection
    {
        $rules = $tournament->rules;
        $q = (int) ($rules?->teams_qualifying ?? 0);
        $direct = (int) ($rules?->direct_final_seeds ?? 0);

        if ($tournament->series()->exists()) {
            throw ValidationException::withMessages(['playoffs' => 'Los playoffs ya fueron generados.']);
        }
        $regular = $tournament->games()->where('stage', GameStage::Regular->value);
        if (! (clone $regular)->whereIn('status', [GameStatus::Final->value, GameStatus::Forfeit->value])->exists()) {
            throw ValidationException::withMessages(['playoffs' => 'No hay resultados de la serie regular.']);
        }
        $pending = (clone $regular)->whereIn('status', [GameStatus::Scheduled->value, GameStatus::Live->value, GameStatus::Postponed->value])->count();
        if ($pending > 0) {
            throw ValidationException::withMessages(['playoffs' => "La serie regular no ha terminado: quedan {$pending} partido(s) pendientes."]);
        }

        $layout = match (true) {
            $q === 2 => [['F', 'final', [1, 2], null]],
            $q === 3 && $direct === 1 => [['SF', 'semifinal', [2, 3], 'F'], ['F', 'final', [1, null], null]],
            $q === 4 && $direct === 0 => [['SF1', 'semifinal', [1, 4], 'F'], ['SF2', 'semifinal', [2, 3], 'F'], ['F', 'final', [null, null], null]],
            default => throw ValidationException::withMessages(['playoffs' => "Formato no soportado aún: {$q} clasificados con {$direct} pase(s) directo(s). Formatos disponibles: 2; 3 con 1 pase directo; 4 sin pases directos."]),
        };

        $standings = $this->standings->recalculate($tournament)->keyBy('position');
        if ($standings->count() < $q) {
            throw ValidationException::withMessages(['playoffs' => "Se necesitan {$q} equipos clasificados."]);
        }

        return DB::transaction(function () use ($tournament, $rules, $layout, $standings) {
            $created = [];
            foreach (array_reverse($layout) as [$slot, $stage, [$hi, $lo], $next]) {
                $bestOf = $stage === 'final' ? $rules->final_best_of : $rules->semifinal_best_of;
                if (! $bestOf) {
                    throw ValidationException::withMessages(['playoffs' => 'Configure en el reglamento a cuántos partidos se juega la '.($stage === 'final' ? 'final' : 'semifinal').'.']);
                }
                $created[$slot] = PlayoffSeries::create([
                    'tournament_id' => $tournament->id,
                    'stage' => $stage,
                    'slot' => $slot,
                    'best_of' => $bestOf,
                    'high_seed_team_id' => $hi ? $standings[$hi]->tournament_team_id : null,
                    'high_seed_position' => $hi,
                    'low_seed_team_id' => $lo ? $standings[$lo]->tournament_team_id : null,
                    'low_seed_position' => $lo,
                    'next_series_id' => $next ? $created[$next]->id : null,
                ]);
            }
            foreach ($created as $series) {
                if ($series->isReady()) {
                    $this->scheduleMissingGames($series);
                }
            }

            return $tournament->series()->get();
        });
    }

    /** Recalcula una serie tras un resultado: victorias, ganador, avance y partidos pendientes. */
    public function sync(PlayoffSeries $series): PlayoffSeries
    {
        return DB::transaction(function () use ($series) {
            $series = PlayoffSeries::lockForUpdate()->findOrFail($series->id);
            $games = $series->games()->get();
            $done = $games->filter(fn ($g) => $g->status->isFinished());
            $high = $done->where('winner_team_id', $series->high_seed_team_id)->count();
            $low = $done->where('winner_team_id', $series->low_seed_team_id)->count();
            $previousWinner = $series->winner_team_id;

            $winner = $high >= $series->winsNeeded() ? $series->high_seed_team_id
                : ($low >= $series->winsNeeded() ? $series->low_seed_team_id : null);

            $series->update([
                'high_wins' => $high, 'low_wins' => $low, 'winner_team_id' => $winner,
                'status' => $winner ? 'finished' : ($done->isNotEmpty() ? 'in_progress' : 'pending'),
            ]);

            if ($previousWinner && $previousWinner !== $winner) {
                $this->withdrawFromNext($series, $previousWinner);
            }

            if ($winner) {
                // Partidos que ya no hacen falta (la serie se decidió antes).
                $series->games()->whereIn('status', [GameStatus::Scheduled->value, GameStatus::Postponed->value])->delete();
                $this->advance($series);
            } else {
                $this->scheduleMissingGames($series);
            }

            return $series->fresh();
        });
    }

    /** Crea los partidos mínimos necesarios para que la serie pueda decidirse. */
    public function scheduleMissingGames(PlayoffSeries $series): void
    {
        if (! $series->isReady() || $series->winner_team_id) {
            return;
        }
        $games = $series->games()->where('status', '!=', GameStatus::Cancelled->value)->get();
        $pending = $games->filter(fn ($g) => ! $g->status->isFinished())->count();
        $needed = $series->winsNeeded() - max($series->high_wins, $series->low_wins);
        $possible = $series->best_of - $games->count();
        $toCreate = min(max(0, $needed - $pending), $possible);
        $number = (int) $games->max('series_game_number');

        for ($i = 0; $i < $toCreate; $i++) {
            $n = ++$number;
            $highHome = $this->highSeedHome($series->best_of, $n);
            Game::create([
                'club_id' => $series->tournament->club_id,
                'tournament_id' => $series->tournament_id,
                'venue_id' => $series->tournament->venue_id,
                'home_team_id' => $highHome ? $series->high_seed_team_id : $series->low_seed_team_id,
                'away_team_id' => $highHome ? $series->low_seed_team_id : $series->high_seed_team_id,
                'stage' => $series->stage,
                'series_id' => $series->id,
                'series_key' => $series->slot,
                'series_game_number' => $n,
                'status' => GameStatus::Scheduled,
            ]);
        }
    }

    /** Formato 2-2-1 (mejor de 5/7) o alterno (mejor de 3): el mejor sembrado es local en los partidos impares clave. */
    private function highSeedHome(int $bestOf, int $n): bool
    {
        return $bestOf >= 5 ? in_array($n, [1, 2, 5, 7], true) : $n % 2 === 1;
    }

    private function advance(PlayoffSeries $series): void
    {
        $next = $series->nextSeries;
        if (! $next) {
            // Final decidida: el torneo termina.
            $series->tournament->update(['status' => TournamentStatus::Finished]);

            return;
        }
        $position = $series->winner_team_id === $series->high_seed_team_id ? $series->high_seed_position : $series->low_seed_position;
        $entrants = collect([
            [$next->high_seed_team_id, $next->high_seed_position],
            [$next->low_seed_team_id, $next->low_seed_position],
            [$series->winner_team_id, $position],
        ])->filter(fn ($e) => $e[0] !== null)->unique(fn ($e) => $e[0])->sortBy(fn ($e) => $e[1])->values();

        $next->update([
            'high_seed_team_id' => $entrants[0][0] ?? null, 'high_seed_position' => $entrants[0][1] ?? null,
            'low_seed_team_id' => $entrants[1][0] ?? null, 'low_seed_position' => $entrants[1][1] ?? null,
        ]);
        $this->scheduleMissingGames($next->fresh());
    }

    private function withdrawFromNext(PlayoffSeries $series, int $teamId): void
    {
        if ($series->tournament->status === TournamentStatus::Finished) {
            $series->tournament->update(['status' => TournamentStatus::InProgress]);
        }
        $next = $series->nextSeries;
        if (! $next) {
            return;
        }
        if ($next->games()->get()->contains(fn ($g) => $g->status->isFinished())) {
            throw ValidationException::withMessages(['status' => 'No se puede cambiar este resultado: la serie siguiente ya comenzó.']);
        }
        $next->games()->delete();
        $attrs = $next->high_seed_team_id === $teamId
            ? ['high_seed_team_id' => $next->low_seed_team_id, 'high_seed_position' => $next->low_seed_position]
            : [];
        $next->update($attrs + ['low_seed_team_id' => null, 'low_seed_position' => null, 'high_wins' => 0, 'low_wins' => 0, 'status' => 'pending']);
    }
}
