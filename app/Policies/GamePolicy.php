<?php

namespace App\Policies;

use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class GamePolicy extends ClubOwnedPolicy
{
    protected function permission(): string
    {
        return 'games.manage';
    }

    /** El anotador puede ver los partidos que tiene asignados. */
    public function view(User $user, Model $game): bool
    {
        return parent::view($user, $game) || $this->isAssignedScorekeeper($user, $game);
    }

    /** Registrar resultado: gestores del club o el anotador asignado al partido. */
    public function recordResult(User $user, Game $game): bool
    {
        return parent::update($user, $game) || $this->isAssignedScorekeeper($user, $game);
    }

    private function isAssignedScorekeeper(User $user, Model $game): bool
    {
        return $user->can('games.score') && $game->scorekeeper_id === $user->id && $user->belongsToClub($game->club_id);
    }
}
