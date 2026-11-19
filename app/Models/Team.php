<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClub;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Team extends Model
{
    use Auditable, BelongsToClub, HasFactory, HasSlug, SoftDeletes;

    protected $fillable = [
        'club_id', 'name', 'short_name', 'primary_color', 'primary_color_name', 'secondary_colors',
        'coach_name', 'assistant_coach_name', 'delegate_name', 'contact_phone', 'is_active',
    ];

    protected function casts(): array
    {
        return ['secondary_colors' => 'array', 'is_active' => 'boolean'];
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(TournamentTeam::class);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function abbreviation(): string
    {
        return $this->short_name ?: Str::upper(Str::substr(Str::ascii($this->name), 0, 3));
    }

    /** Color de texto legible sobre el color principal. */
    public function contrastColor(): string
    {
        $hex = ltrim($this->primary_color ?: '#182131', '#');
        [$r, $g, $b] = array_map('hexdec', str_split($hex, 2));

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 150 ? '#0B101A' : '#FFFFFF';
    }
}
