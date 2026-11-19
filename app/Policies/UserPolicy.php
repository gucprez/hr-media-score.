<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function update(User $user, User $target): bool
    {
        if (! $user->can('users.manage')) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Un administrador de club solo gestiona usuarios de su club y nunca a un superadministrador.
        return $target->club_id === $user->club_id && ! $target->isSuperAdmin();
    }

    public function delete(User $user, User $target): bool
    {
        return $user->id !== $target->id && $this->update($user, $target);
    }
}
