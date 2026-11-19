<?php

namespace App\Actions;

use App\Enums\RegistrationStatus;
use App\Enums\RosterStatus;
use App\Models\Player;
use App\Models\TeamRoster;
use App\Models\TournamentTeam;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Inscribe un jugador en la plantilla de un equipo aplicando el reglamento
 * configurable del torneo (tamaño máximo, refuerzos, edades, dorsales).
 */
class AssignPlayerToRoster
{
    public function handle(TournamentTeam $registration, Player $player, array $data): TeamRoster
    {
        return DB::transaction(function () use ($registration, $player, $data) {
            // Serializa altas concurrentes sobre el mismo equipo.
            $registration = TournamentTeam::whereKey($registration->id)->lockForUpdate()->firstOrFail();
            $tournament = $registration->tournament()->with('rules')->firstOrFail();
            $rules = $tournament->rules;
            $isReinforcement = (bool) ($data['is_reinforcement'] ?? false);
            $errors = [];

            if ($player->club_id !== $tournament->club_id) {
                $errors['player_id'] = 'El jugador pertenece a otra organización.';
            }
            if (in_array($registration->status, [RegistrationStatus::Withdrawn, RegistrationStatus::Disqualified], true)) {
                $errors['player_id'] = 'El equipo no está activo en este torneo.';
            }

            $existing = TeamRoster::where('tournament_id', $tournament->id)->where('player_id', $player->id)
                ->with('tournamentTeam.team')->first();
            if ($existing) {
                $errors['player_id'] = $existing->tournament_team_id === $registration->id
                    ? 'El jugador ya forma parte de esta plantilla.'
                    : "El jugador ya está inscrito en este torneo con {$existing->tournamentTeam->displayName()}.";
            }

            if (TeamRoster::where('tournament_team_id', $registration->id)->where('jersey_number', $data['jersey_number'])->exists()) {
                $errors['jersey_number'] = "El dorsal #{$data['jersey_number']} ya está asignado en este equipo.";
            }

            $active = TeamRoster::where('tournament_team_id', $registration->id)
                ->where('status', '!=', RosterStatus::Released->value);

            if ($rules) {
                if ((clone $active)->count() >= $rules->max_roster_size) {
                    $errors['player_id'] = "La plantilla alcanzó el máximo de {$rules->max_roster_size} jugadores.";
                }
                if ($isReinforcement && (clone $active)->where('is_reinforcement', true)->count() >= $rules->max_reinforcements) {
                    $errors['is_reinforcement'] = $rules->max_reinforcements === 0
                        ? 'Este torneo no admite refuerzos.'
                        : "Solo se permiten {$rules->max_reinforcements} refuerzos por equipo.";
                }
                if ($ageError = $this->ageError($player, $rules, $tournament, $isReinforcement)) {
                    $errors['player_id'] = $ageError;
                }
            }

            if ($errors) {
                throw ValidationException::withMessages($errors);
            }

            return TeamRoster::create([
                'tournament_team_id' => $registration->id,
                'tournament_id' => $tournament->id,
                'player_id' => $player->id,
                'jersey_number' => $data['jersey_number'],
                'position' => $data['position'] ?? $player->position?->value,
                'is_reinforcement' => $isReinforcement,
                'is_captain' => (bool) ($data['is_captain'] ?? false),
                'status' => RosterStatus::Active,
                'joined_on' => now()->toDateString(),
            ]);
        });
    }

    protected function ageError(Player $player, $rules, $tournament, bool $isReinforcement): ?string
    {
        $checkRange = ! ($isReinforcement && $rules->reinforcements_exempt_from_age);
        $maxForReinforcement = $isReinforcement ? $rules->reinforcement_max_age : null;

        if (! $checkRange && $maxForReinforcement === null) {
            return null;
        }
        if (($rules->min_age || $rules->max_age || $maxForReinforcement) && ! $player->birth_date) {
            return 'El reglamento exige edad: registre la fecha de nacimiento del jugador.';
        }

        $refDate = $rules->age_reference_date ?? $tournament->starts_on ?? now();
        $age = $player->ageOn($refDate);

        if ($checkRange && $rules->min_age && $age < $rules->min_age) {
            return "Edad no permitida: {$age} años (mínimo {$rules->min_age}).";
        }
        if ($checkRange && $rules->max_age && $age > $rules->max_age) {
            return "Edad no permitida: {$age} años (máximo {$rules->max_age}). Puede inscribirse como refuerzo si el reglamento lo permite.";
        }
        if ($maxForReinforcement && $age > $maxForReinforcement) {
            return "El refuerzo supera la edad máxima permitida ({$maxForReinforcement} años).";
        }

        return null;
    }
}
