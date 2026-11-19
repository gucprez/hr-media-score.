<?php

namespace App\Models;

use App\Enums\TournamentGender;
use App\Enums\TournamentStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClub;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Tournament extends Model
{
    use Auditable, BelongsToClub, HasFactory, HasSlug, SoftDeletes;

    protected $fillable = [
        'club_id', 'season_id', 'venue_id', 'name', 'edition', 'motto', 'category',
        'gender', 'status', 'starts_on', 'ends_on', 'description', 'is_featured', 'is_public',
    ];

    protected function casts(): array
    {
        return [
            'status' => TournamentStatus::class,
            'gender' => TournamentGender::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_featured' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    protected function slugSourceAttributes(): array
    {
        return ['name', 'edition'];
    }

    protected function slugSource(): string
    {
        return trim(($this->edition ? $this->edition.' ' : '').$this->name);
    }

    public function fullName(): string
    {
        return $this->edition && ! str_starts_with($this->name, $this->edition)
            ? "{$this->edition} {$this->name}" : $this->name;
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function rules(): HasOne
    {
        return $this->hasOne(TournamentRule::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(TournamentTeam::class);
    }

    public function rosters(): HasMany
    {
        return $this->hasMany(TeamRoster::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function standings(): HasMany
    {
        return $this->hasMany(Standing::class)->orderByRaw('position IS NULL, position')->orderByDesc('standing_points');
    }

    public function sanctions(): HasMany
    {
        return $this->hasMany(Sanction::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'tournament_teams')
            ->withPivot(['id', 'status', 'seed', 'group_name'])->withTimestamps();
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_public', true)->where('status', '!=', TournamentStatus::Draft->value);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
