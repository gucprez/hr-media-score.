<?php

namespace App\Actions;

use App\Enums\GameStage;
use App\Enums\GameStatus;
use App\Models\Game;
use App\Services\PlayoffManager;
use App\Services\StandingsCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registra el estado y resultado de un partido y propaga sus efectos:
 * tabla de posiciones (serie regular) y series de playoffs.
 *
 * $data: status, home_score?, away_score?, periods? [[home, away], …], forfeit_loser? (home|away)
 */
class RecordGameResult
{
    /** Marcador oficial de un forfeit (reglamento FIBA). */
    public const FORFEIT_SCORE = 20;

    public function __construct(private StandingsCalculator $standings, private PlayoffManager $playoffs) {}

    public function handle(Game $game, array $data): Game
    {
        return DB::transaction(function () use ($game, $data) {
            $game = Game::lockForUpdate()->with('tournament.rules')->findOrFail($game->id);
            $status = $data['status'] instanceof GameStatus ? $data['status'] : GameStatus::from($data['status']);
            $regularPeriods = (int) ($game->tournament->rules?->periods ?? 4);

            $periods = collect($data['periods'] ?? [])
                ->filter(fn ($p) => is_numeric($p['home'] ?? null) && is_numeric($p['away'] ?? null))
                ->values()->map(fn ($p, $i) => ['period_number' => $i + 1, 'home_points' => (int) $p['home'], 'away_points' => (int) $p['away'], 'is_overtime' => $i + 1 > $regularPeriods]);

            $attrs = ['status' => $status, 'winner_team_id' => null, 'forfeit_loser_team_id' => null];

            switch ($status) {
                case GameStatus::Final:
                case GameStatus::Live:
                    $home = $periods->isNotEmpty() ? $periods->sum('home_points') : (int) ($data['home_score'] ?? 0);
                    $away = $periods->isNotEmpty() ? $periods->sum('away_points') : (int) ($data['away_score'] ?? 0);
                    if ($status === GameStatus::Final) {
                        if ($home === $away) {
                            throw ValidationException::withMessages(['home_score' => 'En baloncesto no hay empates: registre la prórroga hasta que haya un ganador.']);
                        }
                        if ($periods->isNotEmpty() && $periods->count() < $regularPeriods) {
                            throw ValidationException::withMessages(['periods' => "Faltan períodos: el reglamento establece {$regularPeriods}."]);
                        }
                        $attrs['winner_team_id'] = $home > $away ? $game->home_team_id : $game->away_team_id;
                        $attrs['finished_at'] = $game->finished_at ?? now();
                    }
                    $attrs += ['home_score' => $home, 'away_score' => $away, 'current_period' => $periods->count() ?: null];
                    $attrs['started_at'] = $game->started_at ?? now();
                    break;

                case GameStatus::Forfeit:
                    $loserSide = $data['forfeit_loser'] ?? null;
                    if (! in_array($loserSide, ['home', 'away'], true)) {
                        throw ValidationException::withMessages(['forfeit_loser' => 'Indique qué equipo pierde por forfeit.']);
                    }
                    $loser = $loserSide === 'home' ? $game->home_team_id : $game->away_team_id;
                    $attrs = array_merge($attrs, [
                        'forfeit_loser_team_id' => $loser,
                        'winner_team_id' => $loser === $game->home_team_id ? $game->away_team_id : $game->home_team_id,
                        'home_score' => $loserSide === 'home' ? 0 : self::FORFEIT_SCORE,
                        'away_score' => $loserSide === 'away' ? 0 : self::FORFEIT_SCORE,
                        'finished_at' => now(),
                    ]);
                    $periods = collect();
                    break;

                default: // programado, pospuesto, cancelado: sin resultado
                    $attrs += ['home_score' => 0, 'away_score' => 0, 'current_period' => null, 'started_at' => null, 'finished_at' => null];
                    $periods = collect();
            }

            $game->update($attrs);
            $game->periods()->delete();
            foreach ($periods as $p) {
                $game->periods()->create($p);
            }

            if ($game->stage === GameStage::Regular) {
                $this->standings->recalculate($game->tournament);
            }
            if ($game->series_id) {
                $this->playoffs->sync($game->series);
            }

            return $game->fresh(['periods', 'homeTeam.team', 'awayTeam.team']);
        });
    }
}
