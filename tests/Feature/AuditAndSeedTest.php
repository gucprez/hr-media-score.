<?php

use App\Enums\Role;
use App\Livewire\Admin\AuditLogTable;
use App\Models\AuditLog;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Database\Seeders\GustavoBehallSeeder;

it('registra en auditoría la creación, modificación y eliminación', function () {
    $user = userWithRole(Role::Organizer);
    $this->actingAs($user)->post('/admin/teams', ['name' => 'Auditado', 'is_active' => 1]);
    $team = Team::firstOrFail();
    $this->actingAs($user)->put("/admin/teams/{$team->id}", ['name' => 'Auditado 2', 'is_active' => 1]);
    $this->actingAs($user)->delete("/admin/teams/{$team->id}");

    $logs = AuditLog::where('auditable_type', Team::class)->orderBy('id')->get();
    expect($logs->pluck('event')->all())->toBe(['created', 'updated', 'deleted'])
        ->and($logs[1]->old_values['name'])->toBe('Auditado')
        ->and($logs[1]->new_values['name'])->toBe('Auditado 2')
        ->and($logs[0]->user_id)->toBe($user->id)
        ->and($logs[0]->club_id)->toBe($user->club_id);
});

it('nunca guarda contraseñas en la auditoría', function () {
    $admin = userWithRole(Role::ClubAdmin);
    $this->actingAs($admin)->post('/admin/users', [
        'name' => 'Nuevo', 'email' => 'nuevo@ejemplo.com', 'password' => 'Clave12345', 'password_confirmation' => 'Clave12345',
        'role' => 'organizer', 'is_active' => 1,
    ]);
    $log = AuditLog::where('auditable_type', User::class)->where('event', 'created')->latest('id')->firstOrFail();
    expect($log->new_values)->not->toHaveKey('password')->not->toHaveKey('remember_token');
});

it('la auditoría de un club no muestra eventos de otro', function () {
    $a = userWithRole(Role::ClubAdmin);
    $b = userWithRole(Role::ClubAdmin);
    $this->actingAs($b)->post('/admin/teams', ['name' => 'Secreto de B', 'is_active' => 1]);
    $this->actingAs($a);
    Livewire\Livewire::test(AuditLogTable::class)->assertDontSee('Secreto de B');
});

it('el seeder oficial es idempotente y carga el reglamento del XIV torneo', function () {
    $this->seed(GustavoBehallSeeder::class);
    $this->seed(GustavoBehallSeeder::class);

    $t = Tournament::with('rules', 'registrations')->sole();
    expect($t->edition)->toBe('XIV')
        ->and($t->motto)->toBe('Volviendo a la Tradición')
        ->and($t->registrations)->toHaveCount(4)
        ->and($t->rules->only(['min_age', 'max_age', 'max_roster_size', 'max_reinforcements', 'games_per_team', 'teams_qualifying', 'direct_final_seeds', 'semifinal_best_of', 'final_best_of', 'min_minutes_per_player']))
        ->toBe(['min_age' => 15, 'max_age' => 18, 'max_roster_size' => 10, 'max_reinforcements' => 2, 'games_per_team' => 3, 'teams_qualifying' => 3, 'direct_final_seeds' => 1, 'semifinal_best_of' => 3, 'final_best_of' => 5, 'min_minutes_per_player' => 16]);
    expect(Team::where('name', 'Calle Duarte')->first()->secondary_colors)->toHaveCount(2);
});
