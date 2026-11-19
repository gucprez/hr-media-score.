<?php

namespace App\Enums;

enum TournamentGender: string
{
    use HasOptions;

    case Male = 'male';
    case Female = 'female';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Masculino',
            self::Female => 'Femenino',
            self::Mixed => 'Mixto',
        };
    }
}
