<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToClub;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    use Auditable, BelongsToClub, HasFactory;

    protected $fillable = ['club_id', 'name', 'address', 'city', 'capacity'];

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }
}
