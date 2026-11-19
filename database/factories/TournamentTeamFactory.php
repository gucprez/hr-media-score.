<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

class TournamentTeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'team_id' => fn (array $a) => Team::factory()->create([
                'club_id' => Tournament::find($a['tournament_id'])->club_id,
            ])->id,
            'status' => 'confirmed',
            'registered_at' => now(),
        ];
    }
}
