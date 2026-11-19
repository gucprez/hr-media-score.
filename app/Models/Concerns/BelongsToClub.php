<?php

namespace App\Models\Concerns;

use App\Models\Club;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Recurso propiedad de una organización (aislamiento multi-organización).
 *
 * - Al crear, si no se indica club_id, se toma el del usuario autenticado.
 * - scopeVisibleTo() limita las consultas administrativas al club del usuario
 *   (el superadministrador ve todo).
 */
trait BelongsToClub
{
    public static function bootBelongsToClub(): void
    {
        static::creating(function ($model) {
            if (empty($model->club_id) && ($user = auth()->user()) && $user->club_id) {
                $model->club_id = $user->club_id;
            }
        });
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isSuperAdmin()
            ? $query
            : $query->where($this->qualifyColumn('club_id'), $user->club_id);
    }

    public function scopeForClub(Builder $query, Club|int $club): Builder
    {
        return $query->where($this->qualifyColumn('club_id'), $club instanceof Club ? $club->id : $club);
    }
}
