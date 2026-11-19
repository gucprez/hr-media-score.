<?php

namespace App\Enums;

enum Role: string
{
    use HasOptions;

    case SuperAdmin = 'super-admin';
    case ClubAdmin = 'club-admin';
    case Organizer = 'organizer';
    case Scorekeeper = 'scorekeeper';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Superadministrador',
            self::ClubAdmin => 'Administrador del club',
            self::Organizer => 'Organizador',
            self::Scorekeeper => 'Anotador',
        };
    }

    /** Roles que un administrador de club puede asignar (no puede crear superadmins). */
    public static function assignableByClubAdmin(): array
    {
        return [self::ClubAdmin, self::Organizer, self::Scorekeeper];
    }
}
