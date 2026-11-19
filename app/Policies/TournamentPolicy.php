<?php

namespace App\Policies;

use App\Models\Tournament;
use App\Models\User;

class TournamentPolicy extends ClubOwnedPolicy
{
    protected function permission(): string
    {
        return 'tournaments.manage';
    }

    protected function viewPermission(): string
    {
        return 'tournaments.view';
    }

    /** Inscribir equipos y gestionar plantillas del torneo. */
    public function manageRosters(User $user, Tournament $tournament): bool
    {
        return $user->can('rosters.manage') && $user->belongsToClub($tournament->club_id);
    }
}
