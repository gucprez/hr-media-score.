<?php

use App\Enums\Role;
use App\Models\Club;
use App\Models\User;

dataset('admin_pages', ['/admin/teams', '/admin/players', '/admin/tournaments/create', '/admin/venues', '/admin/seasons']);

it('el anotador solo accede al dashboard y a torneos en lectura', function (string $url) {
    $this->actingAs(userWithRole(Role::Scorekeeper))->get($url)->assertForbidden();
})->with('admin_pages');

it('el anotador puede ver el dashboard y la lista de torneos', function () {
    $u = userWithRole(Role::Scorekeeper);
    $this->actingAs($u)->get('/admin')->assertOk();
    $this->actingAs($u)->get('/admin/tournaments')->assertOk();
});

it('el organizador gestiona la competición pero no usuarios ni auditoría', function () {
    $u = userWithRole(Role::Organizer);
    foreach (['/admin/teams', '/admin/players', '/admin/tournaments/create', '/admin/venues'] as $url) {
        $this->actingAs($u)->get($url)->assertOk();
    }
    $this->actingAs($u)->get('/admin/users')->assertForbidden();
    $this->actingAs($u)->get('/admin/audit')->assertForbidden();
    $this->actingAs($u)->get('/admin/clubs/create')->assertForbidden();
});

it('el administrador de club gestiona usuarios y auditoría de su club', function () {
    $u = userWithRole(Role::ClubAdmin);
    $this->actingAs($u)->get('/admin/users')->assertOk();
    $this->actingAs($u)->get('/admin/audit')->assertOk();
    $this->actingAs($u)->get('/admin/clubs')->assertRedirect("/admin/clubs/{$u->club_id}/edit");
    $this->actingAs($u)->get('/admin/clubs/create')->assertForbidden();
});

it('el superadministrador accede a todo', function () {
    $u = userWithRole(Role::SuperAdmin);
    foreach (['/admin', '/admin/clubs', '/admin/clubs/create', '/admin/users', '/admin/audit', '/admin/teams'] as $url) {
        $this->actingAs($u)->get($url)->assertOk();
    }
});

it('un administrador de club no puede crear superadministradores', function () {
    $admin = userWithRole(Role::ClubAdmin);
    $this->actingAs($admin)->post('/admin/users', [
        'name' => 'Intruso', 'email' => 'x@ejemplo.com', 'password' => 'Clave12345', 'password_confirmation' => 'Clave12345',
        'role' => Role::SuperAdmin->value, 'is_active' => 1,
    ])->assertSessionHasErrors('role');
    expect(User::where('email', 'x@ejemplo.com')->exists())->toBeFalse();
});

it('un administrador de club crea usuarios solo en su propia organización', function () {
    $admin = userWithRole(Role::ClubAdmin);
    $other = Club::factory()->create();

    $this->actingAs($admin)->post('/admin/users', [
        'name' => 'Anotador', 'email' => 'anotador@ejemplo.com', 'password' => 'Clave12345', 'password_confirmation' => 'Clave12345',
        'role' => Role::Scorekeeper->value, 'club_id' => $other->id, 'is_active' => 1,
    ])->assertSessionHasErrors('club_id');

    $this->actingAs($admin)->post('/admin/users', [
        'name' => 'Anotador', 'email' => 'anotador@ejemplo.com', 'password' => 'Clave12345', 'password_confirmation' => 'Clave12345',
        'role' => Role::Scorekeeper->value, 'is_active' => 1,
    ])->assertRedirect('/admin/users');

    $created = User::where('email', 'anotador@ejemplo.com')->first();
    expect($created->club_id)->toBe($admin->club_id)->and($created->hasRole('scorekeeper'))->toBeTrue();
});

it('un administrador de club no puede editar usuarios de otro club ni superadministradores', function () {
    $admin = userWithRole(Role::ClubAdmin);
    $foreign = userWithRole(Role::Organizer);
    $super = userWithRole(Role::SuperAdmin);
    $this->actingAs($admin)->get("/admin/users/{$foreign->id}/edit")->assertForbidden();
    $this->actingAs($admin)->get("/admin/users/{$super->id}/edit")->assertForbidden();
    $this->actingAs($admin)->delete("/admin/users/{$foreign->id}")->assertForbidden();
});

it('un usuario no puede eliminarse a sí mismo', function () {
    $admin = userWithRole(Role::ClubAdmin);
    $this->actingAs($admin)->delete("/admin/users/{$admin->id}")->assertForbidden();
});

it('protege a los superadministradores: nadie se elimina a sí mismo y siempre queda uno', function () {
    $super = userWithRole(Role::SuperAdmin);
    $this->actingAs($super)->delete("/admin/users/{$super->id}")->assertForbidden();

    $super2 = userWithRole(Role::SuperAdmin);
    $this->actingAs($super)->delete("/admin/users/{$super2->id}")->assertSessionHas('success');
    expect(User::role('super-admin')->count())->toBe(1)->and(User::find($super->id))->not->toBeNull();
});

it('muestra solo los módulos permitidos en el menú', function () {
    $this->actingAs(userWithRole(Role::Scorekeeper))->get('/admin')
        ->assertDontSee('Usuarios y permisos')->assertDontSee('Auditoría');
    $this->actingAs(userWithRole(Role::ClubAdmin))->get('/admin')
        ->assertSee('Usuarios y permisos')->assertSee('Auditoría');
});
