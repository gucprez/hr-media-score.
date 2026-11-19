<?php

namespace App\Actions;

use App\Enums\RegistrationStatus;
use App\Enums\TournamentStatus;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentTeam;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterTeamInTournament
{
    public function handle(Tournament $tournament, Team $team, array $attributes = []): TournamentTeam
    {
        if ($team->club_id !== $tournament->club_id) {
            throw ValidationException::withMessages(['team_id' => 'El equipo pertenece a otra organización.']);
        }
        if (in_array($tournament->status, [TournamentStatus::Finished, TournamentStatus::Cancelled], true)) {
            throw ValidationException::withMessages(['team_id' => 'El torneo está cerrado y no admite inscripciones.']);
        }

        return DB::transaction(function () use ($tournament, $team, $attributes) {
            Tournament::whereKey($tournament->id)->lockForUpdate()->first();

            if (TournamentTeam::where('tournament_id', $tournament->id)->where('team_id', $team->id)->exists()) {
                throw ValidationException::withMessages(['team_id' => 'Este equipo ya está inscrito en el torneo.']);
            }

            return TournamentTeam::create(array_merge([
                'status' => RegistrationStatus::Registered,
                'registered_at' => now(),
            ], $attributes, [
                'tournament_id' => $tournament->id,
                'team_id' => $team->id,
            ]));
        });
    }
}
