<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    use HasOptions;

    case Registered = 'registered';
    case Confirmed = 'confirmed';
    case Withdrawn = 'withdrawn';
    case Disqualified = 'disqualified';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Inscrito',
            self::Confirmed => 'Confirmado',
            self::Withdrawn => 'Retirado',
            self::Disqualified => 'Descalificado',
        };
    }
}
