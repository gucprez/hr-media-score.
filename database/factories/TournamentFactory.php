<?php

namespace Database\Factories;

use App\Enums\TournamentStatus;
use App\Models\Club;
use App\Models\Tournament;
use App\Models\TournamentRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class TournamentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'name' => 'Torneo '.fake()->unique()->words(2, true),
            'category' => 'Juvenil',
            'gender' => 'male',
            'status' => TournamentStatus::Registration,
            'starts_on' => now()->addWeek()->toDateString(),
            'is_public' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(fn (Tournament $t) => $t->rules()->create(TournamentRule::defaults()));
    }

    public function withRules(array $rules): static
    {
        return $this->afterCreating(fn (Tournament $t) => $t->rules()->update($rules));
    }

    public function draft(): static
    {
        return $this->state(['status' => TournamentStatus::Draft]);
    }
}
