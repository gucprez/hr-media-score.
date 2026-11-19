<?php

namespace App\Enums;

enum RosterStatus: string
{
    use HasOptions;

    case Active = 'active';
    case Injured = 'injured';
    case Suspended = 'suspended';
    case Released = 'released';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Injured => 'Lesionado',
            self::Suspended => 'Suspendido',
            self::Released => 'Dado de baja',
        };
    }
}
