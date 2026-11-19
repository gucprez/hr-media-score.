<?php

namespace App\Enums;

enum TournamentStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Registration = 'registration';
    case InProgress = 'in_progress';
    case Finished = 'finished';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Registration => 'Inscripciones abiertas',
            self::InProgress => 'En curso',
            self::Finished => 'Finalizado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::InProgress => 'badge-live',
            self::Registration => 'badge-scheduled',
            self::Finished => 'badge-final',
            default => 'badge-muted',
        };
    }
}
