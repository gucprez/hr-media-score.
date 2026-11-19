<?php

use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\TeamRoster;
use App\Models\Tournament;
use App\Models\TournamentRule;
use App\Models\TournamentTeam;
use Illuminate\Database\QueryException;

it('la base de datos rechaza inscripciones duplicadas aunque se salte la aplicación', function () {
    $reg = TournamentTeam::factory()->create();
    expect(fn () => TournamentTeam::create(['tournament_id' => $reg->tournament_id, 'team_id' => $reg->team_id]))
        ->toThrow(QueryException::class);
});

it('la base de datos impide un jugador dos veces en el mismo torneo y dorsales repetidos', function () {
    $reg = TournamentTeam::factory()->create();
    $other = TournamentTeam::factory()->create(['tournament_id' => $reg->tournament_id]);
    $p = Player::factory()->create(['club_id' => $reg->tournament->club_id]);
    $p2 = Player::factory()->create(['club_id' => $reg->tournament->club_id]);
    $base = ['tournament_id' => $reg->tournament_id, 'status' => 'active'];
    TeamRoster::create($base + ['tournament_team_id' => $reg->id, 'player_id' => $p->id, 'jersey_number' => 1]);

    expect(fn () => TeamRoster::create($base + ['tournament_team_id' => $other->id, 'player_id' => $p->id, 'jersey_number' => 2]))->toThrow(QueryException::class);
    expect(fn () => TeamRoster::create($base + ['tournament_team_id' => $reg->id, 'player_id' => $p2->id, 'jersey_number' => 1]))->toThrow(QueryException::class);
});

it('las claves foráneas impiden borrar un equipo o jugador con historial', function () {
    $reg = TournamentTeam::factory()->create();
    expect(fn () => Team::whereKey($reg->team_id)->forceDelete())->toThrow(QueryException::class);

    $p = Player::factory()->create(['club_id' => $reg->tournament->club_id]);
    TeamRoster::create(['tournament_id' => $reg->tournament_id, 'tournament_team_id' => $reg->id, 'player_id' => $p->id, 'jersey_number' => 3]);
    expect(fn () => Player::whereKey($p->id)->forceDelete())->toThrow(QueryException::class);
});

it('un partido no puede enfrentar a un equipo consigo mismo', function () {
    $reg = TournamentTeam::factory()->create();
    expect(fn () => Game::create(['club_id' => $reg->tournament->club_id, 'tournament_id' => $reg->tournament_id, 'home_team_id' => $reg->id, 'away_team_id' => $reg->id]))
        ->toThrow(QueryException::class);
});

it('las restricciones únicas de nombre por organización están en la base de datos', function () {
    $team = Team::factory()->create();
    expect(fn () => Team::forceCreate(['club_id' => $team->club_id, 'name' => $team->name, 'slug' => 'otro-slug']))->toThrow(QueryException::class);
});

it('genera slugs únicos por organización', function () {
    $t1 = Team::factory()->create(['name' => 'Los Tigres']);
    $t2 = Team::factory()->create(['name' => 'Los Tigres']); // otro club
    $p1 = Player::factory()->create(['first_name' => 'Ana', 'last_name' => 'Ruiz', 'club_id' => $t1->club_id]);
    $p2 = Player::factory()->create(['first_name' => 'Ana', 'last_name' => 'Ruiz', 'club_id' => $t1->club_id, 'birth_date' => '2009-01-01']);
    expect($t1->slug)->toBe('los-tigres')->and($t2->slug)->toBe('los-tigres')
        ->and($p1->slug)->toBe('ana-ruiz')->and($p2->slug)->toBe('ana-ruiz-2');
});

it('borrar un torneo en cascada elimina sus inscripciones y reglas, no los equipos', function () {
    $reg = TournamentTeam::factory()->create();
    $teamId = $reg->team_id;
    Tournament::whereKey($reg->tournament_id)->forceDelete();
    expect(TournamentTeam::count())->toBe(0)->and(Team::find($teamId))->not->toBeNull()
        ->and(TournamentRule::count())->toBe(0);
});
