<?php

namespace App\Services;

use App\Enums\GameStage;
use App\Enums\GameStatus;
use App\Enums\RegistrationStatus;
use App\Models\Standing;
use App\Models\Tournament;
use App\Models\TournamentRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Recalcula la tabla de posiciones de la serie regular a partir de los resultados
 * oficiales, aplicando la puntuación y los criterios de desempate del reglamento.
 */
class StandingsCalculator
{
    public const CRITERIA = [
        'head_to_head' => 'Enfrentamiento directo (puntos)',
        'head_to_head_diff' => 'Enfrentamiento directo (diferencia)',
        'point_diff' => 'Diferencia de puntos general',
        'points_for' => 'Puntos anotados',
        'fewest_points_against' => 'Menos puntos recibidos',
    ];

    public function recalculate(Tournament $tournament): Collection
    {
        return DB::transaction(function () use ($tournament) {
            $rules = $tournament->rules ?? new TournamentRule(TournamentRule::defaults());
            $regs = $tournament->registrations()->with('team')
                ->whereNotIn('status', [RegistrationStatus::Withdrawn->value, RegistrationStatus::Disqualified->value])->get()->keyBy('id');

            $games = $tournament->games()->where('stage', GameStage::Regular->value)
                ->whereIn('status', [GameStatus::Final->value, GameStatus::Forfeit->value])->get()
                ->filter(fn ($g) => $regs->has($g->home_team_id) && $regs->has($g->away_team_id))->values();

            $rows = [];
            foreach ($regs as $id => $reg) {
                $rows[$id] = ['played' => 0, 'won' => 0, 'lost' => 0, 'forfeits' => 0, 'points_for' => 0, 'points_against' => 0, 'standing_points' => 0, 'name' => $reg->displayName()];
            }

            foreach ($games as $g) {
                $winner = $g->winner_team_id ?? ($g->home_score > $g->away_score ? $g->home_team_id : $g->away_team_id);
                $loser = $winner === $g->home_team_id ? $g->away_team_id : $g->home_team_id;
                foreach ([[$g->home_team_id, $g->home_score, $g->away_score], [$g->away_team_id, $g->away_score, $g->home_score]] as [$id, $for, $against]) {
                    $rows[$id]['played']++;
                    $rows[$id]['points_for'] += $for;
                    $rows[$id]['points_against'] += $against;
                }
                $rows[$winner]['won']++;
                $rows[$winner]['standing_points'] += $rules->points_per_win;
                $rows[$loser]['lost']++;
                if ($g->status === GameStatus::Forfeit && $g->forfeit_loser_team_id === $loser) {
                    $rows[$loser]['forfeits']++;
                    $rows[$loser]['standing_points'] += $rules->points_per_forfeit;
                } else {
                    $rows[$loser]['standing_points'] += $rules->points_per_loss;
                }
            }

            $order = $this->order(array_keys($rows), $rows, $games, $rules);

            Standing::where('tournament_id', $tournament->id)->whereNotIn('tournament_team_id', array_keys($rows))->delete();
            foreach ($order as $i => $id) {
                $r = $rows[$id];
                Standing::updateOrCreate(
                    ['tournament_id' => $tournament->id, 'tournament_team_id' => $id],
                    ['position' => $i + 1, 'played' => $r['played'], 'won' => $r['won'], 'lost' => $r['lost'], 'forfeits' => $r['forfeits'],
                        'points_for' => $r['points_for'], 'points_against' => $r['points_against'],
                        'point_diff' => $r['points_for'] - $r['points_against'], 'standing_points' => $r['standing_points']],
                );
            }

            return $tournament->standings()->with('tournamentTeam.team')->get();
        });
    }

    /** Ordena por puntos y desempata cada grupo empatado con los criterios configurados. */
    public function order(array $ids, array $rows, Collection $games, TournamentRule $rules): array
    {
        $criteria = array_values(array_intersect((array) ($rules->tiebreakers ?: ['head_to_head', 'head_to_head_diff', 'point_diff', 'points_for']), array_keys(self::CRITERIA)));
        $byPoints = collect($ids)->groupBy(fn ($id) => $rows[$id]['standing_points'])->sortKeysDesc();

        return $byPoints->flatMap(fn ($group) => $this->resolve($group->all(), $criteria, $rows, $games))->values()->all();
    }

    private function resolve(array $ids, array $criteria, array $rows, Collection $games): array
    {
        if (count($ids) <= 1 || $criteria === []) {
            usort($ids, fn ($a, $b) => strcasecmp($rows[$a]['name'], $rows[$b]['name']));

            return $ids;
        }
        $criterion = array_shift($criteria);
        $metric = $this->metric($criterion, $ids, $rows, $games);

        return collect($ids)->groupBy(fn ($id) => (string) $metric[$id])
            ->sortKeysDesc(SORT_NUMERIC)
            ->flatMap(fn ($group) => $this->resolve($group->all(), $criteria, $rows, $games))->values()->all();
    }

    /** @return array<int, int|float> valor mayor = mejor */
    private function metric(string $criterion, array $ids, array $rows, Collection $games): array
    {
        $out = array_fill_keys($ids, 0);
        if (in_array($criterion, ['head_to_head', 'head_to_head_diff'], true)) {
            foreach ($games as $g) {
                if (! in_array($g->home_team_id, $ids, true) || ! in_array($g->away_team_id, $ids, true)) {
                    continue;
                }
                if ($criterion === 'head_to_head') {
                    $winner = $g->winner_team_id ?? ($g->home_score > $g->away_score ? $g->home_team_id : $g->away_team_id);
                    $out[$winner] += 2;
                    $out[$winner === $g->home_team_id ? $g->away_team_id : $g->home_team_id] += 1;
                } else {
                    $out[$g->home_team_id] += $g->home_score - $g->away_score;
                    $out[$g->away_team_id] += $g->away_score - $g->home_score;
                }
            }

            return $out;
        }
        foreach ($ids as $id) {
            $out[$id] = match ($criterion) {
                'point_diff' => $rows[$id]['points_for'] - $rows[$id]['points_against'],
                'points_for' => $rows[$id]['points_for'],
                'fewest_points_against' => -$rows[$id]['points_against'],
            };
        }

        return $out;
    }
}
