<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Expulsa a usuarios desactivados (o de una organización desactivada) aunque tengan sesión abierta. */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && (! $user->is_active || ($user->club_id && ! $user->club?->is_active))) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Su cuenta está desactivada. Contacte al administrador.']);
        }

        return $next($request);
    }
}
