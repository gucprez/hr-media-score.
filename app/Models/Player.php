<?php

namespace App\Models;

use App\Enums\PlayerPosition;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClub;
use App\Models\Concerns\HasSlug;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Player extends Model
{
    use Auditable, BelongsToClub, HasFactory, HasSlug, SoftDeletes;

    protected $fillable = [
        'club_id', 'first_name', 'last_name', 'nickname', 'birth_date', 'document_number', 'gender',
        'position', 'height_cm', 'weight_kg', 'dominant_hand', 'guardian_name', 'guardian_phone',
        'notes', 'is_active',
    ];

    /** Datos personales sensibles (menores de edad): nunca se exponen en el portal. */
    protected $hidden = ['document_number', 'guardian_name', 'guardian_phone', 'notes', 'birth_date'];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'position' => PlayerPosition::class, 'is_active' => 'boolean'];
    }

    protected function slugSourceAttributes(): array
    {
        return ['first_name', 'last_name'];
    }

    protected function slugSource(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->first_name} {$this->last_name}"));
    }

    public function rosterEntries(): HasMany
    {
        return $this->hasMany(TeamRoster::class);
    }

    public function gameStats(): HasMany
    {
        return $this->hasMany(PlayerGameStat::class);
    }

    public function ageOn(?CarbonInterface $date = null): ?int
    {
        return $this->birth_date ? (int) floor($this->birth_date->diffInYears($date ?? now())) : null;
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }
}
