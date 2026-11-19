<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use App\Enums\RosterStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Inscripción de un equipo en un torneo. */
class TournamentTeam extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['tournament_id', 'team_id', 'display_name', 'coach_name', 'group_name', 'seed', 'status', 'registered_at'];

    protected function casts(): array
    {
        return ['status' => RegistrationStatus::class, 'registered_at' => 'datetime'];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class)->withTrashed();
    }

    public function roster(): HasMany
    {
        return $this->hasMany(TeamRoster::class)->orderBy('jersey_number');
    }

    public function standing(): HasOne
    {
        return $this->hasOne(Standing::class);
    }

    public function activeRoster(): HasMany
    {
        return $this->roster()->where('status', '!=', RosterStatus::Released->value);
    }

    public function displayName(): string
    {
        return $this->display_name ?: $this->team->name;
    }

    public function coach(): ?string
    {
        return $this->coach_name ?: $this->team->coach_name;
    }
}
