<?php

namespace App\Enums;

enum PlayerPosition: string
{
    use HasOptions;

    case Pg = 'pg';
    case Sg = 'sg';
    case Sf = 'sf';
    case Pf = 'pf';
    case C = 'c';
    case G = 'g';
    case F = 'f';

    public function label(): string
    {
        return match ($this) {
            self::Pg => 'Base',
            self::Sg => 'Escolta',
            self::Sf => 'Alero',
            self::Pf => 'Ala-pívot',
            self::C => 'Pívot',
            self::G => 'Guardia',
            self::F => 'Delantero',
        };
    }
}
