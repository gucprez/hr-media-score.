<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Crea el superadministrador inicial a partir de ADMIN_EMAIL / ADMIN_PASSWORD.
 * Si no hay contraseña definida, genera una aleatoria y la muestra una sola vez.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@hrmediascore.local');
        if (User::where('email', $email)->exists()) {
            $this->command?->info("Superadministrador {$email} ya existe; no se modifica.");

            return;
        }

        $password = env('ADMIN_PASSWORD') ?: Str::password(16, symbols: false);
        $user = User::create([
            'name' => env('ADMIN_NAME', 'Administrador HR Media'),
            'email' => $email,
            'password' => $password,
            'is_active' => true,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole(Role::SuperAdmin->value);

        if (! env('ADMIN_PASSWORD')) {
            $this->command?->warn("Superadministrador creado: {$email}  /  contraseña: {$password}");
            $this->command?->warn('Guarde esta contraseña y cámbiela tras el primer acceso.');
        }
    }
}
