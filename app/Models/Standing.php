<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Standing extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function tournamentTeam(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class);
    }
}
