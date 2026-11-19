<?php

namespace App\Models;

use App\Enums\GameEventType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Evento de la mesa de anotación (Fase 3). Nunca se borra: se anula. */
class GameEvent extends Model
{
    protected $fillable = [
        'game_id', 'sequence', 'period_number', 'clock_seconds', 'tournament_team_id', 'player_id',
        'related_player_id', 'type', 'points', 'meta', 'recorded_by', 'corrects_event_id', 'voided_at', 'voided_by',
    ];

    protected function casts(): array
    {
        return ['type' => GameEventType::class, 'meta' => 'array', 'voided_at' => 'datetime'];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function tournamentTeam(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class);
    }

    public function scopeValid(Builder $q): Builder
    {
        return $q->whereNull('voided_at');
    }
}
