<?php

namespace App\Models;

use App\Enums\GameStage;
use App\Enums\GameStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClub;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
    use Auditable, BelongsToClub, HasFactory, SoftDeletes;

    protected $fillable = [
        'club_id', 'tournament_id', 'venue_id', 'home_team_id', 'away_team_id', 'stage', 'round_number',
        'series_key', 'series_id', 'series_game_number', 'scheduled_at', 'status', 'home_score', 'away_score',
        'current_period', 'winner_team_id', 'forfeit_loser_team_id', 'scorekeeper_id', 'started_at', 'finished_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => GameStatus::class,
            'stage' => GameStage::class,
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'away_team_id');
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(PlayoffSeries::class, 'series_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'winner_team_id');
    }

    public function scorekeeper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scorekeeper_id');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(GamePeriod::class)->orderBy('period_number');
    }

    public function events(): HasMany
    {
        return $this->hasMany(GameEvent::class)->orderBy('sequence');
    }

    public function playerStats(): HasMany
    {
        return $this->hasMany(PlayerGameStat::class);
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->whereIn('status', [GameStatus::Scheduled->value, GameStatus::Live->value])
            ->where(fn ($w) => $w->whereNull('scheduled_at')->orWhere('scheduled_at', '>=', now()->startOfDay()))
            ->orderBy('scheduled_at');
    }

    public function scopeCompleted(Builder $q): Builder
    {
        return $q->whereIn('status', [GameStatus::Final->value, GameStatus::Forfeit->value])->orderByDesc('scheduled_at');
    }

    public function scopeInPublishedTournament(Builder $q): Builder
    {
        return $q->whereHas('tournament', fn ($t) => $t->published());
    }

    /** El partido enfrenta a este equipo inscrito. */
    public function involves(int $tournamentTeamId): bool
    {
        return in_array($tournamentTeamId, [$this->home_team_id, $this->away_team_id], true);
    }

    public function loserTeamId(): ?int
    {
        if (! $this->winner_team_id) {
            return null;
        }

        return $this->winner_team_id === $this->home_team_id ? $this->away_team_id : $this->home_team_id;
    }
}
