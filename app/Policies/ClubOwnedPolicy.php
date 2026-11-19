<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Política base para recursos que pertenecen a un club.
 * Cada subclase define el permiso que la gobierna.
 */
abstract class ClubOwnedPolicy
{
    abstract protected function permission(): string;

    protected function viewPermission(): string
    {
        return $this->permission();
    }

    public function viewAny(User $user): bool
    {
        return $user->can($this->viewPermission());
    }

    public function view(User $user, Model $model): bool
    {
        return $user->can($this->viewPermission()) && $user->belongsToClub($model->club_id);
    }

    public function create(User $user): bool
    {
        return $user->can($this->permission()) && ($user->isSuperAdmin() || $user->club_id !== null);
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can($this->permission()) && $user->belongsToClub($model->club_id);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->update($user, $model);
    }
}
