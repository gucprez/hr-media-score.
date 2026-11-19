<?php

use App\Enums\Role;
use App\Models\Club;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentTeam;

beforeEach(function () {
    $this->clubA = Club::factory()->create();
    $this->clubB = Club::factory()->create();
    $this->userA = userWithRole(Role::ClubAdmin, $this->clubA);
    $this->teamB = Team::factory()->create(['club_id' => $this->clubB->id, 'name' => 'Equipo Ajeno']);
    $this->playerB = Player::factory()->create(['club_id' => $this->clubB->id]);
    $this->tournamentB = Tournament::factory()->create(['club_id' => $this->clubB->id]);
});

it('no permite ver ni editar recursos de otra organización', function () {
    $a = $this->actingAs($this->userA);
    $a->get("/admin/teams/{$this->teamB->id}")->assertForbidden();
    $a->get("/admin/teams/{$this->teamB->id}/edit")->assertForbidden();
    $a->put("/admin/teams/{$this->teamB->id}", ['name' => 'Hackeado'])->assertForbidden();
    $a->delete("/admin/teams/{$this->teamB->id}")->assertForbidden();
    $a->get("/admin/players/{$this->playerB->id}")->assertForbidden();
    $a->put("/admin/players/{$this->playerB->id}", ['first_name' => 'X', 'last_name' => 'Y'])->assertForbidden();
    $a->get("/admin/tournaments/{$this->tournamentB->id}")->assertForbidden();
    $a->get("/admin/tournaments/{$this->tournamentB->id}/teams")->assertForbidden();
    $a->get("/admin/clubs/{$this->clubB->id}/edit")->assertForbidden();
    expect($this->teamB->fresh()->name)->toBe('Equipo Ajeno');
});

it('los listados solo muestran datos de la propia organización', function () {
    Team::factory()->create(['club_id' => $this->clubA->id, 'name' => 'Equipo Propio']);
    $this->actingAs($this->userA)->get('/admin/teams')->assertSee('Equipo Propio')->assertDontSee('Equipo Ajeno');
});

it('no permite acceder a una plantilla de otro torneo por manipulación de URL', function () {
    $tA = Tournament::factory()->create(['club_id' => $this->clubA->id]);
    $regB = TournamentTeam::factory()->create(['tournament_id' => $this->tournamentB->id]);
    $this->actingAs($this->userA)->get("/admin/tournaments/{$tA->id}/teams/{$regB->id}/roster")->assertNotFound();
});

it('el superadministrador puede operar en todas las organizaciones', function () {
    $super = userWithRole(Role::SuperAdmin);
    $this->actingAs($super)->get("/admin/teams/{$this->teamB->id}")->assertOk();
    $this->actingAs($super)->post('/admin/teams', ['name' => 'Nuevo', 'is_active' => 1])->assertSessionHasErrors('club_id');
    $this->actingAs($super)->post('/admin/teams', ['name' => 'Nuevo', 'club_id' => $this->clubB->id, 'is_active' => 1])->assertSessionHasNoErrors();
    expect(Team::where('name', 'Nuevo')->value('club_id'))->toBe($this->clubB->id);
});

it('desactivar una organización bloquea a sus usuarios', function () {
    $this->clubA->update(['is_active' => false]);
    $this->actingAs($this->userA)->get('/admin')->assertRedirect('/acceso');
});
