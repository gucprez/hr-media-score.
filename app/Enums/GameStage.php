<?php

namespace App\Enums;

enum GameStage: string
{
    use HasOptions;

    case Regular = 'regular';
    case Quarterfinal = 'quarterfinal';
    case Semifinal = 'semifinal';
    case Final = 'final';
    case ThirdPlace = 'third_place';
    case Friendly = 'friendly';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Serie regular',
            self::Quarterfinal => 'Cuartos de final',
            self::Semifinal => 'Semifinal',
            self::Final => 'Final',
            self::ThirdPlace => 'Tercer lugar',
            self::Friendly => 'Amistoso',
        };
    }
}
