<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Paginator::defaultView('components.pagination');

        // El superadministrador tiene acceso total.
        Gate::before(fn (User $user) => $user->hasRole(Role::SuperAdmin->value) ? true : null);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->letters()->mixedCase()->numbers()->uncompromised()
            : Password::min(8)->letters()->numbers());

        RateLimiter::for('login', fn (Request $r) => [
            Limit::perMinute(5)->by(mb_strtolower((string) $r->input('email')).'|'.$r->ip()),
            Limit::perMinute(20)->by($r->ip()),
        ]);
        RateLimiter::for('password-reset', fn (Request $r) => Limit::perMinute(3)->by($r->ip()));

        Event::listen(Login::class, function (Login $e) {
            $e->user->forceFill(['last_login_at' => now()])->saveQuietly();
            AuditLogger::auth('login', $e->user);
        });
        Event::listen(Logout::class, fn (Logout $e) => $e->user ? AuditLogger::auth('logout', $e->user) : null);
        Event::listen(Failed::class, fn (Failed $e) => AuditLogger::auth('login_failed', $e->user,
            'Intento con '.mb_substr((string) ($e->credentials['email'] ?? ''), 0, 120)));
        Event::listen(PasswordReset::class, fn (PasswordReset $e) => AuditLogger::auth('password_reset', $e->user));
    }
}
