<?php

namespace App\Actions;

use App\Enums\RosterStatus;
use App\Models\PlayerGameStat;
use App\Models\TeamRoster;

/**
 * Si el jugador ya tiene participación registrada, se da de baja (se conserva
 * el historial); de lo contrario se elimina la inscripción.
 */
class RemovePlayerFromRoster
{
    public function handle(TeamRoster $entry): string
    {
        $hasHistory = PlayerGameStat::where('player_id', $entry->player_id)
            ->where('tournament_team_id', $entry->tournament_team_id)->exists();

        if ($hasHistory) {
            $entry->update(['status' => RosterStatus::Released, 'left_on' => now()->toDateString()]);

            return 'released';
        }

        $entry->delete();

        return 'deleted';
    }
}
