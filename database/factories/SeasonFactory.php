<?php

namespace Database\Factories;

use App\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeasonFactory extends Factory
{
    public function definition(): array
    {
        return ['club_id' => Club::factory(), 'name' => 'Temporada '.fake()->unique()->numberBetween(2000, 2099)];
    }
}
