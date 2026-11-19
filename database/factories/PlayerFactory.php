<?php

namespace Database\Factories;

use App\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlayerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'first_name' => fake()->firstNameMale(),
            'last_name' => fake()->unique()->lastName(),
            'birth_date' => now()->subYears(16)->subDays(fake()->numberBetween(1, 300))->toDateString(),
            'position' => fake()->randomElement(['pg', 'sg', 'sf', 'pf', 'c']),
            'is_active' => true,
        ];
    }

    public function age(int $years): static
    {
        return $this->state(['birth_date' => now()->subYears($years)->subDays(30)->toDateString()]);
    }
}
