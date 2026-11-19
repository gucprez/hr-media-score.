<?php

namespace Database\Factories;

use App\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

class VenueFactory extends Factory
{
    public function definition(): array
    {
        return ['club_id' => Club::factory(), 'name' => 'Techado '.fake()->unique()->lastName(), 'city' => fake()->city()];
    }
}
