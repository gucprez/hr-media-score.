<?php

namespace App\Models;

use App\Enums\PlayerPosition;
use App\Enums\RosterStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Jugador inscrito en la plantilla de un equipo para un torneo. */
class TeamRoster extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'tournament_team_id', 'tournament_id', 'player_id', 'jersey_number', 'position',
        'is_reinforcement', 'is_captain', 'status', 'joined_on', 'left_on',
    ];

    protected function casts(): array
    {
        return [
            'status' => RosterStatus::class,
            'position' => PlayerPosition::class,
            'is_reinforcement' => 'boolean',
            'is_captain' => 'boolean',
            'joined_on' => 'date',
            'left_on' => 'date',
        ];
    }

    public function tournamentTeam(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class)->withTrashed();
    }
}
