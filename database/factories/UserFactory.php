<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Club;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
        ];
    }

    public function forClub(Club $club): static
    {
        return $this->state(['club_id' => $club->id]);
    }

    public function role(Role $role): static
    {
        return $this->afterCreating(fn (User $u) => $u->assignRole($role->value));
    }

    public function superAdmin(): static
    {
        return $this->role(Role::SuperAdmin);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
