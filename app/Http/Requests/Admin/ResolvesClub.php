<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/**
 * Determina la organización destino de un recurso: la del modelo existente,
 * la del usuario, o (solo superadministrador) la elegida en el formulario.
 */
trait ResolvesClub
{
    public function targetClubId(): ?int
    {
        foreach ($this->route()?->parameters() ?? [] as $param) {
            if (is_object($param) && isset($param->club_id)) {
                return (int) $param->club_id;
            }
        }
        if ($this->user()->isSuperAdmin()) {
            return $this->integer('club_id') ?: $this->user()->club_id;
        }

        return $this->user()->club_id;
    }

    protected function clubRules(): array
    {
        $creating = ! collect($this->route()?->parameters() ?? [])->contains(fn ($p) => is_object($p));

        return $this->user()->isSuperAdmin() && $creating
            ? ['club_id' => ['required', 'integer', Rule::exists('clubs', 'id')->whereNull('deleted_at')]]
            : [];
    }

    /** Datos validados con el club forzado (un usuario no puede cambiar la organización). */
    public function validatedForClub(): array
    {
        $data = $this->safe()->except(['logo', 'photo', 'remove_logo', 'remove_photo']);
        $data['club_id'] = $this->targetClubId();

        return $data;
    }
}
