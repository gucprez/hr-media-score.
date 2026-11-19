<?php

use App\Enums\Role;
use App\Models\Club;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class))
    ->in('Feature');

/** Crea un usuario con rol, opcionalmente asociado a un club. */
function userWithRole(Role $role, ?Club $club = null): User
{
    $user = User::factory()->create(['club_id' => $role === Role::SuperAdmin ? null : ($club ?? Club::factory()->create())->id]);
    $user->assignRole($role->value);

    return $user;
}

pest()->extend(TestCase::class)->in('Unit');
