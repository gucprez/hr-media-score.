<?php

use App\Enums\Role;
use App\Models\Game;
use App\Models\Standing;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentTeam;
use Carbon\Carbon;
use Database\Seeders\GustavoBehallSeeder;

function setupGames(?Tournament $t = null): array
{
    $t ??= Tournament::factory()->create(['status' => 'in_progress']);
    $a = TournamentTeam::factory()->create(['tournament_id' => $t->id, 'team_id' => Team::factory()->create(['club_id' => $t->club_id, 'name' => 'Alfa'])->id]);
    $b = TournamentTeam::factory()->create(['tournament_id' => $t->id, 'team_id' => Team::factory()->create(['club_id' => $t->club_id, 'name' => 'Beta'])->id]);
    $g = Game::create(['club_id' => $t->club_id, 'tournament_id' => $t->id, 'home_team_id' => $a->id, 'away_team_id' => $b->id,
        'stage' => 'regular', 'round_number' => 1, 'status' => 'scheduled', 'scheduled_at' => now()->addDay()->setTime(18, 0)]);

    return [$t, $a, $b, $g];
}

it('el organizador ve el calendario, genera la serie regular y programa partidos', function () {
    $user = userWithRole(Role::Organizer);
    [$t, $a, $b] = setupGames(Tournament::factory()->create(['club_id' => $user->club_id]));
    $this->actingAs($user)->get("/admin/tournaments/{$t->id}/games")->assertOk()->assertSee('Jornada 1')->assertSee('Alfa');

    $this->actingAs($user)->post("/admin/tournaments/{$t->id}/schedule")->assertSessionHas('error'); // ya completo (2 equipos)
    $this->actingAs($user)->post("/admin/tournaments/{$t->id}/games", [
        'home_team_id' => $b->id, 'away_team_id' => $a->id, 'stage' => 'regular', 'round_number' => 2, 'date' => '2026-12-05', 'time' => '19:00',
    ])->assertRedirect("/admin/tournaments/{$t->id}/games");
    expect(Game::where('round_number', 2)->first()->scheduled_at->format('Y-m-d H:i'))->toBe('2026-12-05 19:00');
});

it('valida la programación: un equipo no juega contra sí mismo ni con equipos de otro torneo', function () {
    $user = userWithRole(Role::Organizer);
    [$t, $a] = setupGames(Tournament::factory()->create(['club_id' => $user->club_id]));
    $foreign = TournamentTeam::factory()->create();
    $this->actingAs($user)->post("/admin/tournaments/{$t->id}/games", ['home_team_id' => $a->id, 'away_team_id' => $a->id, 'stage' => 'regular'])
        ->assertSessionHasErrors('away_team_id');
    $this->actingAs($user)->post("/admin/tournaments/{$t->id}/games", ['home_team_id' => $a->id, 'away_team_id' => $foreign->id, 'stage' => 'regular'])
        ->assertSessionHasErrors('away_team_id');
});

it('registra el resultado por períodos y actualiza la tabla', function () {
    $user = userWithRole(Role::Organizer);
    [$t, $a, $b, $g] = setupGames(Tournament::factory()->create(['club_id' => $user->club_id]));
    $this->actingAs($user)->get("/admin/tournaments/{$t->id}/games/{$g->id}/result")->assertOk()->assertSee('Resultado del partido');

    $this->actingAs($user)->put("/admin/tournaments/{$t->id}/games/{$g->id}/result", [
        'status' => 'final', 'mode' => 'periods',
        'periods' => [['home' => 20, 'away' => 10], ['home' => 15, 'away' => 15], ['home' => 10, 'away' => 12], ['home' => 12, 'away' => 9]],
    ])->assertSessionHasNoErrors()->assertRedirect();

    expect($g->fresh()->home_score)->toBe(57)->and($g->fresh()->away_score)->toBe(46)
        ->and(Standing::where('tournament_team_id', $a->id)->value('standing_points'))->toBe(2);
});

it('rechaza un empate como resultado final con mensaje claro', function () {
    $user = userWithRole(Role::Organizer);
    [$t, , , $g] = setupGames(Tournament::factory()->create(['club_id' => $user->club_id]));
    $this->actingAs($user)->from("/admin/tournaments/{$t->id}/games/{$g->id}/result")
        ->put("/admin/tournaments/{$t->id}/games/{$g->id}/result", ['status' => 'final', 'mode' => 'total', 'home_score' => 50, 'away_score' => 50])
        ->assertSessionHasErrors('home_score');
});

it('el anotador solo registra resultados de los partidos que tiene asignados', function () {
    [$t, , , $g] = setupGames();
    $scorer = userWithRole(Role::Scorekeeper, $t->club);
    $url = "/admin/tournaments/{$t->id}/games/{$g->id}/result";

    $this->actingAs($scorer)->get($url)->assertForbidden();
    $this->actingAs($scorer)->get('/admin/games')->assertOk()->assertDontSee('Alfa');

    $g->update(['scorekeeper_id' => $scorer->id]);
    $this->actingAs($scorer)->get('/admin/games')->assertOk()->assertSee('Alfa');
    $this->actingAs($scorer)->put($url, ['status' => 'final', 'mode' => 'total', 'home_score' => 61, 'away_score' => 55])->assertRedirect();
    expect($g->fresh()->status->value)->toBe('final');

    // No puede reprogramar ni eliminar partidos
    $this->actingAs($scorer)->get("/admin/tournaments/{$t->id}/games/{$g->id}/edit")->assertForbidden();
    $this->actingAs($scorer)->delete("/admin/tournaments/{$t->id}/games/{$g->id}")->assertForbidden();
});

it('un usuario de otra organización no accede al calendario ni a los resultados', function () {
    [$t, , , $g] = setupGames();
    $intruder = userWithRole(Role::ClubAdmin);
    $this->actingAs($intruder)->get("/admin/tournaments/{$t->id}/games")->assertForbidden();
    $this->actingAs($intruder)->put("/admin/tournaments/{$t->id}/games/{$g->id}/result", ['status' => 'final', 'home_score' => 1, 'away_score' => 0])->assertForbidden();
    $this->actingAs($intruder)->post("/admin/tournaments/{$t->id}/playoffs")->assertForbidden();
});

it('no permite acceder a un partido a través de otro torneo', function () {
    $user = userWithRole(Role::Organizer);
    $mine = Tournament::factory()->create(['club_id' => $user->club_id]);
    [, , , $g] = setupGames(Tournament::factory()->create(['club_id' => $user->club_id]));
    $this->actingAs($user)->get("/admin/tournaments/{$mine->id}/games/{$g->id}/result")->assertNotFound();
});

it('muestra la cartelera pública de la jornada y la oculta en torneos en borrador', function () {
    $this->seed(GustavoBehallSeeder::class);
    $t = Tournament::firstOrFail();
    $this->get("/torneos/{$t->id}/cartelera/2026-11-21")->assertOk()
        ->assertSee('Doble cartelera')->assertSee('Ninjas GB')->assertSee('Balkanes')
        ->assertSee('Copa La Tendencia Podcast')->assertSee('Fundado en el 1920')->assertSee('6:00 PM');
    $this->get("/torneos/{$t->id}/cartelera/2026-11-21?formato=historia")->assertOk()->assertSee('height: 1920px', false);
    $this->get("/torneos/{$t->id}/cartelera/2026-01-01")->assertNotFound();

    $t->update(['status' => 'draft']);
    $this->get("/torneos/{$t->id}/cartelera/2026-11-21")->assertNotFound();
});

it('la ficha pública del torneo agrupa el calendario por jornadas', function () {
    $this->seed(GustavoBehallSeeder::class);
    $t = Tournament::firstOrFail();
    $this->get("/torneos/{$t->id}/{$t->slug}")->assertOk()->assertSee('Jornada 1')->assertSee('Cartelera');
});

it('la portada destaca la próxima jornada', function () {
    $this->travelTo(Carbon::parse('2026-11-21 10:00', 'America/Santo_Domingo'));
    $this->seed(GustavoBehallSeeder::class);
    $this->get('/')->assertOk()->assertSee('Hoy se juega')->assertSee('Doble cartelera');
});
