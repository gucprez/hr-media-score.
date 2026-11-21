<?php

namespace App\Models;

use App\Enums\GameStage;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Serie de playoffs al mejor de N partidos. */
class PlayoffSeries extends Model
{
    use Auditable;

    protected $table = 'playoff_series';

    protected $fillable = [
        'tournament_id', 'stage', 'slot', 'best_of', 'high_seed_team_id', 'low_seed_team_id',
        'high_seed_position', 'low_seed_position', 'high_wins', 'low_wins', 'winner_team_id',
        'next_series_id', 'next_slot', 'status',
    ];

    protected function casts(): array
    {
        return ['stage' => GameStage::class];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function highSeed(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'high_seed_team_id');
    }

    public function lowSeed(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'low_seed_team_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'winner_team_id');
    }

    public function nextSeries(): BelongsTo
    {
        return $this->belongsTo(self::class, 'next_series_id');
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class, 'series_id')->orderBy('series_game_number');
    }

    public function winsNeeded(): int
    {
        return intdiv($this->best_of, 2) + 1;
    }

    public function isReady(): bool
    {
        return $this->high_seed_team_id !== null && $this->low_seed_team_id !== null;
    }

    public function label(): string
    {
        return $this->stage->label().(str_starts_with($this->slot, 'SF') && $this->slot !== 'SF' ? ' '.substr($this->slot, 2) : '');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'finished' => 'Finalizada',
            'in_progress' => 'En juego',
            default => $this->isReady() ? 'Por comenzar' : 'Esperando rival',
        };
    }
}
