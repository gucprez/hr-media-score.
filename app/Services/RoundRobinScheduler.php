<?php

namespace App\Services;

use App\Enums\GameStage;
use App\Enums\GameStatus;
use App\Enums\RegistrationStatus;
use App\Models\Game;
use App\Models\Tournament;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Genera la serie regular "todos contra todos" (método del círculo).
 *
 * - Respeta las vueltas configuradas (round_robin_legs); en la 2.ª vuelta se invierte la localía.
 * - No duplica enfrentamientos ya programados (p. ej. una primera jornada cargada a mano):
 *   solo crea los que faltan y los reparte en jornadas siguientes, sin que un equipo juegue dos veces en la misma jornada.
 * - Las fechas son opcionales: si se indica una fecha de inicio, cada jornada se separa `days_between` días.
 */
class RoundRobinScheduler
{
    /** @param array{start_at?: ?string, days_between?: ?int, venue_id?: ?int} $options */
    public function generate(Tournament $tournament, array $options = []): int
    {
        $teamIds = $tournament->registrations()
            ->whereNotIn('status', [RegistrationStatus::Withdrawn->value, RegistrationStatus::Disqualified->value])
            ->join('teams', 'teams.id', '=', 'tournament_teams.team_id')
            ->orderBy('teams.name')->pluck('tournament_teams.id')->all();

        if (count($teamIds) < 2) {
            throw ValidationException::withMessages(['schedule' => 'Se necesitan al menos 2 equipos inscritos para generar el calendario.']);
        }

        $legs = max(1, (int) ($tournament->rules?->round_robin_legs ?? 1));

        return DB::transaction(function () use ($tournament, $teamIds, $legs, $options) {
            $existing = $tournament->games()->where('stage', GameStage::Regular->value)
                ->where('status', '!=', GameStatus::Cancelled->value)->get(['home_team_id', 'away_team_id', 'round_number']);

            $played = [];
            foreach ($existing as $g) {
                $key = $this->key($g->home_team_id, $g->away_team_id);
                $played[$key] = ($played[$key] ?? 0) + 1;
            }

            // Enfrentamientos pendientes, en orden de jornadas del método del círculo.
            $pending = [];
            foreach (range(1, $legs) as $leg) {
                foreach ($this->circleRounds($teamIds) as $round) {
                    foreach ($round as [$home, $away]) {
                        [$home, $away] = $leg % 2 === 0 ? [$away, $home] : [$home, $away];
                        $key = $this->key($home, $away);
                        if (($played[$key] ?? 0) > 0) {
                            $played[$key]--;   // ya existe (en cualquier localía)

                            continue;
                        }
                        $pending[] = [$home, $away];
                    }
                }
            }

            $startRound = (int) $existing->max('round_number');
            $busy = [];
            $start = ! empty($options['start_at']) ? CarbonImmutable::parse($options['start_at']) : null;
            $gap = max(1, (int) ($options['days_between'] ?? 7));
            $venue = $options['venue_id'] ?? $tournament->venue_id;

            foreach ($pending as [$home, $away]) {
                $round = $startRound + 1;
                while (isset($busy[$round][$home]) || isset($busy[$round][$away])) {
                    $round++;
                }
                $busy[$round][$home] = $busy[$round][$away] = true;

                Game::create([
                    'club_id' => $tournament->club_id,
                    'tournament_id' => $tournament->id,
                    'venue_id' => $venue,
                    'home_team_id' => $home,
                    'away_team_id' => $away,
                    'stage' => GameStage::Regular,
                    'round_number' => $round,
                    'status' => GameStatus::Scheduled,
                    'scheduled_at' => $start?->addDays(($round - $startRound - 1) * $gap),
                ]);
            }

            return count($pending);
        });
    }

    /** @return list<list<array{0:int,1:int}>> */
    public function circleRounds(array $ids): array
    {
        if (count($ids) % 2 === 1) {
            $ids[] = null; // descanso
        }
        $n = count($ids);
        $rounds = [];
        for ($r = 0; $r < $n - 1; $r++) {
            $pairs = [];
            for ($i = 0; $i < $n / 2; $i++) {
                [$a, $b] = [$ids[$i], $ids[$n - 1 - $i]];
                if ($a === null || $b === null) {
                    continue;
                }
                // Alterna la localía del equipo fijo para equilibrar.
                $pairs[] = ($i === 0 && $r % 2 === 1) ? [$b, $a] : [$a, $b];
            }
            $rounds[] = $pairs;
            $ids = array_merge([$ids[0], $ids[$n - 1]], array_slice($ids, 1, $n - 2));
        }

        return $rounds;
    }

    private function key(int $a, int $b): string
    {
        return min($a, $b).'-'.max($a, $b);
    }
}
