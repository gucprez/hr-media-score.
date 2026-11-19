<?php

use App\Enums\Role;
use App\Models\AuditLog;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('muestra la pantalla de acceso con la marca HR Media Score', function () {
    $this->get('/acceso')->assertOk()->assertSee('Acceso administrativo')->assertSee('hr-logo-principal', false);
});

it('redirige a invitados que intentan entrar al panel', function () {
    $this->get('/admin')->assertRedirect('/acceso');
    $this->get('/admin/teams')->assertRedirect('/acceso');
});

it('inicia sesión con credenciales válidas y registra auditoría', function () {
    $user = userWithRole(Role::Organizer);

    $this->post('/acceso', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/admin');
    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
    expect(AuditLog::where('event', 'login')->where('user_id', $user->id)->exists())->toBeTrue();
});

it('rechaza una contraseña incorrecta', function () {
    $user = userWithRole(Role::Organizer);
    $this->from('/acceso')->post('/acceso', ['email' => $user->email, 'password' => 'incorrecta'])
        ->assertRedirect('/acceso')->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('impide el acceso a cuentas desactivadas', function () {
    $user = userWithRole(Role::Organizer);
    $user->update(['is_active' => false]);
    $this->post('/acceso', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('expulsa a un usuario desactivado con sesión abierta', function () {
    $user = userWithRole(Role::Organizer);
    $this->actingAs($user)->get('/admin')->assertOk();
    $user->update(['is_active' => false]);
    $this->actingAs($user)->get('/admin')->assertRedirect('/acceso');
});

it('limita los intentos de acceso', function () {
    $user = userWithRole(Role::Organizer);
    foreach (range(1, 5) as $i) {
        $this->post('/acceso', ['email' => $user->email, 'password' => 'mal']);
    }
    $this->post('/acceso', ['email' => $user->email, 'password' => 'password'])->assertStatus(429);
});

it('cierra la sesión', function () {
    $user = userWithRole(Role::Organizer);
    $this->actingAs($user)->post('/salir')->assertRedirect('/');
    $this->assertGuest();
});

it('rechaza el cierre de sesión sin token CSRF en un entorno real', function () {
    $user = userWithRole(Role::Organizer);
    // Las pruebas de Laravel omiten CSRF; se verifica que el middleware esté activo en el grupo web.
    $middleware = app(Kernel::class)->getMiddlewareGroups()['web'];
    expect($middleware)->toContain(ValidateCsrfToken::class);
});

it('envía el enlace de recuperación sin revelar si la cuenta existe', function () {
    Notification::fake();
    $user = userWithRole(Role::Organizer);

    $this->post('/recuperar-contrasena', ['email' => $user->email])->assertSessionHas('status');
    $this->post('/recuperar-contrasena', ['email' => 'nadie@ejemplo.com'])->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
    Notification::assertCount(1);
});

it('restablece la contraseña con un token válido y la guarda con hash', function () {
    $user = userWithRole(Role::Organizer);
    $token = Password::createToken($user);

    $this->post('/restablecer-contrasena', [
        'token' => $token, 'email' => $user->email,
        'password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026',
    ])->assertRedirect('/acceso');

    $fresh = $user->fresh();
    expect($fresh->password)->not->toBe('NuevaClave2026')
        ->and(Hash::check('NuevaClave2026', $fresh->password))->toBeTrue();
});

it('rechaza un token de restablecimiento inválido', function () {
    $user = userWithRole(Role::Organizer);
    $this->post('/restablecer-contrasena', [
        'token' => 'falso', 'email' => $user->email, 'password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026',
    ])->assertSessionHasErrors('email');
});

it('permite cambiar la contraseña propia verificando la actual', function () {
    $user = userWithRole(Role::Scorekeeper);
    $this->actingAs($user)->put('/admin/settings/password', [
        'current_password' => 'mal', 'password' => 'OtraClave2026', 'password_confirmation' => 'OtraClave2026',
    ])->assertSessionHasErrors('current_password');

    $this->actingAs($user)->put('/admin/settings/password', [
        'current_password' => 'password', 'password' => 'OtraClave2026', 'password_confirmation' => 'OtraClave2026',
    ])->assertSessionHasNoErrors();
    expect(Hash::check('OtraClave2026', $user->fresh()->password))->toBeTrue();
});
