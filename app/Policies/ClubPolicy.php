<?php

namespace App\Policies;

use App\Models\Club;
use App\Models\User;

class ClubPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('clubs.view');
    }

    public function view(User $user, Club $club): bool
    {
        return $user->can('clubs.view') && $user->belongsToClub($club->id);
    }

    public function create(User $user): bool
    {
        return $user->can('clubs.create');
    }

    public function update(User $user, Club $club): bool
    {
        return $user->can('clubs.manage') && $user->belongsToClub($club->id);
    }

    public function delete(User $user, Club $club): bool
    {
        return $user->can('clubs.create') && $user->club_id !== $club->id;
    }
}
