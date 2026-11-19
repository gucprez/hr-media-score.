<?php

namespace App\Enums;

enum GameStatus: string
{
    use HasOptions;

    case Scheduled = 'scheduled';
    case Live = 'live';
    case Final = 'final';
    case Postponed = 'postponed';
    case Cancelled = 'cancelled';
    case Forfeit = 'forfeit';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Programado',
            self::Live => 'En vivo',
            self::Final => 'Final',
            self::Postponed => 'Pospuesto',
            self::Cancelled => 'Cancelado',
            self::Forfeit => 'Forfeit',
        };
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Final, self::Forfeit], true);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Live => 'badge-live',
            self::Final, self::Forfeit => 'badge-final',
            self::Postponed, self::Cancelled => 'badge-muted',
            default => 'badge-scheduled',
        };
    }
}
