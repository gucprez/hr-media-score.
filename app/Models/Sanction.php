<?php

namespace App\Models;

use App\Enums\SanctionType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClub;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sanction extends Model
{
    use Auditable, BelongsToClub;

    protected $fillable = [
        'club_id', 'tournament_id', 'game_id', 'player_id', 'tournament_team_id', 'type',
        'games_suspended', 'fine_amount', 'description', 'issued_on', 'issued_by',
    ];

    protected function casts(): array
    {
        return ['type' => SanctionType::class, 'issued_on' => 'date', 'fine_amount' => 'decimal:2'];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
