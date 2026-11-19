<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentRule extends Model
{
    use Auditable, HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'age_reference_date' => 'date',
            'reinforcements_exempt_from_age' => 'boolean',
            'tiebreakers' => 'array',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /** Valores por defecto razonables para un torneo nuevo. */
    public static function defaults(): array
    {
        return [
            'max_roster_size' => 12,
            'max_reinforcements' => 0,
            'reinforcements_exempt_from_age' => true,
            'regular_season_format' => 'round_robin',
            'round_robin_legs' => 1,
            'periods' => 4,
            'period_minutes' => 10,
            'overtime_minutes' => 5,
            'personal_foul_limit' => 5,
            'technical_foul_ejection' => 2,
            'points_per_win' => 2,
            'points_per_loss' => 1,
            'points_per_forfeit' => 0,
            'min_players_to_start' => 5,
            'tiebreakers' => ['head_to_head', 'point_diff', 'points_for'],
        ];
    }
}
