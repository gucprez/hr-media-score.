<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamePeriod extends Model
{
    protected $fillable = ['game_id', 'period_number', 'is_overtime', 'home_points', 'away_points'];

    protected function casts(): array
    {
        return ['is_overtime' => 'boolean'];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function label(): string
    {
        return $this->is_overtime ? 'TE'.($this->period_number - 4) : 'C'.$this->period_number;
    }
}
