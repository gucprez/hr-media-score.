<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Public;
use App\Models\Tournament;
use App\Models\TournamentTeam;
use Illuminate\Support\Facades\Route;

/*
| Portal público
*/
Route::get('/', Public\HomeController::class)->name('home');
Route::get('/manifest.webmanifest', Public\ManifestController::class)->name('manifest');
Route::name('public.')->group(function () {
    Route::get('/torneos', [Public\TournamentController::class, 'index'])->name('tournaments.index');
    Route::get('/torneos/{id}/{slug?}', [Public\TournamentController::class, 'show'])->whereNumber('id')->name('tournaments.show');
    Route::get('/torneos/{id}/cartelera/{date}', Public\PosterController::class)->whereNumber('id')
        ->where('date', '\d{4}-\d{2}-\d{2}')->name('tournaments.poster');
    Route::get('/equipos', [Public\TeamController::class, 'index'])->name('teams.index');
    Route::get('/equipos/{id}/{slug?}', [Public\TeamController::class, 'show'])->whereNumber('id')->name('teams.show');
    Route::get('/jugadores', [Public\PlayerController::class, 'index'])->name('players.index');
    Route::get('/jugadores/{id}/{slug?}', [Public\PlayerController::class, 'show'])->whereNumber('id')->name('players.show');
    Route::get('/partidos', [Public\GameController::class, 'index'])->name('games.index');
    Route::get('/partidos/{game}', [Public\GameController::class, 'show'])->whereNumber('game')->name('games.show');
});

/*
| Autenticación
*/
Route::middleware('guest')->group(function () {
    Route::get('/acceso', [LoginController::class, 'create'])->name('login');
    Route::post('/acceso', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::get('/recuperar-contrasena', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/recuperar-contrasena', [PasswordResetController::class, 'email'])->middleware('throttle:password-reset')->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/restablecer-contrasena', [PasswordResetController::class, 'update'])->middleware('throttle:password-reset')->name('password.update');
});
Route::post('/salir', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
| Panel administrativo
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'active'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');

    Route::resource('clubs', Admin\ClubController::class)->except('show');
    Route::resource('venues', Admin\VenueController::class)->except('show');
    Route::resource('seasons', Admin\SeasonController::class)->except('show');
    Route::resource('tournaments', Admin\TournamentController::class);
    Route::resource('teams', Admin\TeamController::class);
    Route::resource('players', Admin\PlayerController::class);
    Route::resource('users', Admin\UserController::class)->except('show');

    Route::get('tournaments/{tournament}/teams', fn (Tournament $tournament) => view('admin.tournaments.registrations', compact('tournament')))
        ->can('manageRosters', 'tournament')->name('tournaments.registrations');
    Route::get('tournaments/{tournament}/teams/{registration}/roster', function (Tournament $tournament, TournamentTeam $registration) {
        abort_unless($registration->tournament_id === $tournament->id, 404);

        return view('admin.tournaments.roster', compact('tournament', 'registration'));
    })->can('manageRosters', 'tournament')->name('tournaments.roster');

    // Fase 2 — Competiciones
    Route::get('games', [Admin\GameController::class, 'all'])->name('games.all');
    Route::prefix('tournaments/{tournament}')->name('tournaments.')->group(function () {
        Route::get('games', [Admin\GameController::class, 'index'])->name('games.index');
        Route::get('games/create', [Admin\GameController::class, 'create'])->name('games.create');
        Route::post('games', [Admin\GameController::class, 'store'])->name('games.store');
        Route::get('games/{game}/edit', [Admin\GameController::class, 'edit'])->name('games.edit');
        Route::put('games/{game}', [Admin\GameController::class, 'update'])->name('games.update');
        Route::delete('games/{game}', [Admin\GameController::class, 'destroy'])->name('games.destroy');
        Route::get('games/{game}/result', [Admin\GameController::class, 'editResult'])->name('games.result');
        Route::put('games/{game}/result', [Admin\GameController::class, 'updateResult'])->name('games.result.update');
        Route::post('schedule', [Admin\GameController::class, 'generate'])->name('schedule');
        Route::post('standings', [Admin\GameController::class, 'recalculate'])->name('standings');
        Route::post('playoffs', [Admin\GameController::class, 'generatePlayoffs'])->name('playoffs');
    });

    Route::get('audit', [Admin\AuditLogController::class, 'index'])->name('audit.index');

    Route::get('settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings/profile', [Admin\SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('settings/password', [Admin\SettingsController::class, 'updatePassword'])->name('settings.password');
});
