<?php

use App\Actions\AssignPlayerToRoster;
use App\Actions\RemovePlayerFromRoster;
use App\Enums\Role;
use App\Enums\RosterStatus;
use App\Livewire\Admin\RosterManager;
use App\Livewire\Admin\TournamentRegistrations;
use App\Models\Game;
use App\Models\Player;
use App\Models\PlayerGameStat;
use App\Models\Team;
use App\Models\TeamRoster;
use App\Models\Tournament;
use App\Models\TournamentTeam;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/** Torneo con el reglamento del XIV Torneo Juvenil Infantil (15–18 años, 10 jugadores, 2 refuerzos). */
function gbTournament(): Tournament
{
    $t = Tournament::factory()->create(['starts_on' => now()->toDateString()]);
    $t->rules()->update(['min_age' => 15, 'max_age' => 18, 'max_roster_size' => 10, 'max_reinforcements' => 2, 'reinforcements_exempt_from_age' => true]);

    return $t->fresh('rules');
}

function assign(TournamentTeam $reg, Player $p, int $jersey, bool $reinforcement = false): TeamRoster
{
    return app(AssignPlayerToRoster::class)->handle($reg, $p, ['jersey_number' => $jersey, 'is_reinforcement' => $reinforcement]);
}

function errorsOf(callable $fn): array
{
    try {
        $fn();
    } catch (ValidationException $e) {
        return $e->errors();
    }

    return [];
}

it('asigna un jugador elegible a la plantilla', function () {
    $t = gbTournament();
    $reg = TournamentTeam::factory()->create(['tournament_id' => $t->id]);
    $entry = assign($reg, Player::factory()->age(16)->create(['club_id' => $t->club_id]), 10);

    expect($entry->status)->toBe(RosterStatus::Active)->and($entry->tournament_id)->toBe($t->id);
});

it('respeta el máximo de jugadores por equipo', function () {
    $t = gbTournament();
    $reg = TournamentTeam::factory()->create(['tournament_id' => $t->id]);
    foreach (range(1, 10) as $n) {
        assign($reg, Player::factory()->age(16)->create(['club_id' => $t->club_id]), $n);
    }
    $errors = errorsOf(fn () => assign($reg, Player::factory()->age(16)->create(['club_id' => $t->club_id]), 11));
    expect($errors['player_id'][0])->toContain('máximo de 10');
});

it('aplica el rango de edad y exime a los refuerzos', function () {
    $t = gbTournament();
    $reg = TournamentTeam::factory()->create(['tournament_id' => $t->id]);

    expect(errorsOf(fn () => assign($reg, Player::factory()->age(14)->create(['club_id' => $t->club_id]), 1))['player_id'][0])->toContain('mínimo 15');
    expect(errorsOf(fn () => assign($reg, Player::factory()->age(20)->create(['club_id' => $t->club_id]), 2))['player_id'][0])->toContain('máximo 18');

    $reinforcement = assign($reg, Player::factory()->age(20)->create(['club_id' => $t->club_id]), 3, true);
    expect($reinforcement->is_reinforcement)->toBeTrue();
});

it('exige fecha de nacimiento cuando el reglamento limita la edad', function () {
    $t = gbTournament();
    $reg = TournamentTeam::factory()->create(['tournament_id' => $t->id]);
    $errors = errorsOf(fn () => assign($reg, Player::factory()->create(['club_id' => $t->club_id, 'birth_date' => null]), 4));
    expect($errors['player_id'][0])->toContain('fecha de nacimiento');
});

it('limita la cantidad de refuerzos por equipo', function () {
    $t = gbTournament();
    $reg = TournamentTeam::factory()->create(['tournament_id' => $t->id]);
    assign($reg, Player::factory()->age(22)->create(['club_id' => $t->club_id]), 1, true);
    assign($reg, Player::factory()->age(22)->create(['club_id' => $t->club_id]), 2, true);
    $errors = errorsOf(fn () => assign($reg, Player::factory()->age(22)->create(['club_id' => $t->club_id]), 3, true));
    expect($errors['is_reinforcement'][0])->toContain('2 refuerzos');
});

it('las reglas son configurables por torneo y no fijas para un club', function () {
    $t = Tournament::factory()->create();
    $t->rules()->update(['min_age' => null, 'max_age' => null, 'max_roster_size' => 15, 'max_reinforcements' => 0]);
    $reg = TournamentTeam::factory()->create(['tournament_id' => $t->id]);

    assign($reg, Player::factory()->age(35)->create(['club_id' => $t->club_id]), 1);
    expect(errorsOf(fn () => assign($reg, Player::factory()->age(20)->create(['club_id' => $t->club_id]), 2, true))['is_reinforcement'][0])
        ->toContain('no admite refuerzos');
});

it('impide que un jugador esté en dos equipos del mismo torneo', function () {
    $t = gbTournament();
    [$a, $b] = TournamentTeam::factory()->count(2)->create(['tournament_id' => $t->id]);
    $p = Player::factory()->age(16)->create(['club_id' => $t->club_id]);
    assign($a, $p, 5);

    expect(errorsOf(fn () => assign($b, $p, 5))['player_id'][0])->toContain('ya está inscrito en este torneo');
    expect(errorsOf(fn () => assign($a, $p, 6))['player_id'][0])->toContain('ya forma parte');
});

it('impide dorsales repetidos en el mismo equipo', function () {
    $t = gbTournament();
    $reg = TournamentTeam::factory()->create(['tournament_id' => $t->id]);
    assign($reg, Player::factory()->age(16)->create(['club_id' => $t->club_id]), 23);
    expect(errorsOf(fn () => assign($reg, Player::factory()->age(16)->create(['club_id' => $t->club_id]), 23))['jersey_number'][0])->toContain('#23');
});

it('no permite inscribir jugadores de otra organización', function () {
    $t = gbTournament();
    $reg = TournamentTeam::factory()->create(['tournament_id' => $t->id]);
    expect(errorsOf(fn () => assign($reg, Player::factory()->age(16)->create(), 1))['player_id'][0])->toContain('otra organización');
});

it('conserva el historial del jugador al cambiar de equipo entre torneos', function () {
    $t1 = gbTournament();
    $t2 = Tournament::factory()->create(['club_id' => $t1->club_id]);
    $p = Player::factory()->age(16)->create(['club_id' => $t1->club_id]);
    assign(TournamentTeam::factory()->create(['tournament_id' => $t1->id]), $p, 8);
    assign(TournamentTeam::factory()->create(['tournament_id' => $t2->id]), $p, 12);

    expect($p->rosterEntries()->count())->toBe(2)
        ->and($p->rosterEntries()->pluck('tournament_team_id')->unique())->toHaveCount(2);
});

it('da de baja (sin borrar) a un jugador con participación registrada', function () {
    $t = gbTournament();
    [$home, $away] = TournamentTeam::factory()->count(2)->create(['tournament_id' => $t->id]);
    $p = Player::factory()->age(16)->create(['club_id' => $t->club_id]);
    $entry = assign($home, $p, 9);
    $game = Game::create(['club_id' => $t->club_id, 'tournament_id' => $t->id, 'home_team_id' => $home->id, 'away_team_id' => $away->id, 'status' => 'final']);
    PlayerGameStat::create(['game_id' => $game->id, 'player_id' => $p->id, 'tournament_team_id' => $home->id, 'points' => 12]);

    expect(app(RemovePlayerFromRoster::class)->handle($entry))->toBe('released');
    expect($entry->fresh()->status)->toBe(RosterStatus::Released);

    $other = assign($home, Player::factory()->age(16)->create(['club_id' => $t->club_id]), 10);
    expect(app(RemovePlayerFromRoster::class)->handle($other))->toBe('deleted');
    expect(TeamRoster::find($other->id))->toBeNull();
});

it('gestiona la plantilla desde el componente Livewire mostrando errores del reglamento', function () {
    $user = userWithRole(Role::Organizer);
    $t = gbTournament();
    $t->update(['club_id' => $user->club_id]);
    $reg = TournamentTeam::factory()->create(['tournament_id' => $t->id]);
    $ok = Player::factory()->age(16)->create(['club_id' => $user->club_id]);
    $old = Player::factory()->age(25)->create(['club_id' => $user->club_id]);

    $this->actingAs($user);
    Livewire::test(RosterManager::class, ['registration' => $reg])
        ->call('selectPlayer', $ok->id)->set('jerseyNumber', 4)->call('add')
        ->assertHasNoErrors()->assertDispatched('notify')
        ->call('selectPlayer', $old->id)->set('jerseyNumber', 4)->call('add')
        ->assertHasErrors(['playerId', 'jerseyNumber']);

    expect(TeamRoster::count())->toBe(1);
});

it('el componente de plantillas rechaza usuarios sin permiso', function () {
    $t = gbTournament();
    $reg = TournamentTeam::factory()->create(['tournament_id' => $t->id]);
    $this->actingAs(userWithRole(Role::Scorekeeper, $t->club));
    Livewire::test(RosterManager::class, ['registration' => $reg])->assertForbidden();
});

it('inscribe equipos desde el componente Livewire', function () {
    $user = userWithRole(Role::Organizer);
    $t = Tournament::factory()->create(['club_id' => $user->club_id]);
    $team = Team::factory()->create(['club_id' => $user->club_id, 'name' => 'Cacuses']);

    $this->actingAs($user);
    Livewire::test(TournamentRegistrations::class, ['tournament' => $t])
        ->set('teamId', $team->id)->call('register')->assertHasNoErrors()->assertSee('Cacuses');
    expect($t->registrations()->count())->toBe(1);
});
