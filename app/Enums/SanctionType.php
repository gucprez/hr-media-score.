<?php

namespace App\Enums;

enum SanctionType: string
{
    use HasOptions;

    case Technical = 'technical';
    case Ejection = 'ejection';
    case Aggression = 'aggression';
    case Lateness = 'lateness';
    case NoShow = 'no_show';
    case Unsportsmanlike = 'unsportsmanlike';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Technical => 'Falta técnica',
            self::Ejection => 'Expulsión',
            self::Aggression => 'Agresión',
            self::Lateness => 'Impuntualidad',
            self::NoShow => 'Incomparecencia',
            self::Unsportsmanlike => 'Conducta antideportiva',
            self::Other => 'Otra',
        };
    }
}
