<?php

namespace App\Support;

use App\Enums\Role;

/** Catálogo central de permisos y su asignación a roles. */
final class Permissions
{
    public const ALL = [
        'dashboard.view' => 'Ver panel',
        'clubs.view' => 'Ver organizaciones',
        'clubs.manage' => 'Editar organización',
        'clubs.create' => 'Crear organizaciones',
        'users.manage' => 'Gestionar usuarios',
        'venues.manage' => 'Gestionar sedes',
        'seasons.manage' => 'Gestionar temporadas',
        'tournaments.view' => 'Ver torneos',
        'tournaments.manage' => 'Gestionar torneos',
        'teams.manage' => 'Gestionar equipos',
        'players.manage' => 'Gestionar jugadores',
        'rosters.manage' => 'Gestionar plantillas',
        'games.manage' => 'Gestionar calendario y resultados',
        'games.score' => 'Anotar partidos asignados',
        'settings.manage' => 'Configuración',
        'audit.view' => 'Ver auditoría',
    ];

    /** @return array<string, list<string>> */
    public static function byRole(): array
    {
        $organizer = [
            'dashboard.view', 'tournaments.view', 'tournaments.manage', 'venues.manage', 'seasons.manage',
            'teams.manage', 'players.manage', 'rosters.manage', 'games.manage', 'games.score',
        ];

        return [
            Role::SuperAdmin->value => array_keys(self::ALL),
            Role::ClubAdmin->value => array_values(array_diff(array_keys(self::ALL), ['clubs.create'])),
            Role::Organizer->value => $organizer,
            Role::Scorekeeper->value => ['dashboard.view', 'tournaments.view', 'games.score'],
        ];
    }
}
