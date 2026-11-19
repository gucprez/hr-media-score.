<?php

use App\Enums\Role;
use App\Models\Club;
use App\Models\Season;
use App\Models\Tournament;

function tournamentPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => 'Copa de Verano', 'edition' => 'I', 'motto' => 'Juego limpio', 'category' => 'Juvenil',
        'gender' => 'male', 'status' => 'registration', 'is_public' => 1, 'is_featured' => 0,
        'rules' => [
            'min_age' => 15, 'max_age' => 18, 'max_roster_size' => 10, 'max_reinforcements' => 2,
            'reinforcements_exempt_from_age' => 1, 'regular_season_format' => 'round_robin', 'round_robin_legs' => 1,
            'games_per_team' => 3, 'teams_qualifying' => 3, 'direct_final_seeds' => 1, 'semifinal_best_of' => 3, 'final_best_of' => 5,
            'periods' => 4, 'period_minutes' => 10, 'overtime_minutes' => 5, 'personal_foul_limit' => 5, 'technical_foul_ejection' => 2,
            'points_per_win' => 2, 'points_per_loss' => 1, 'points_per_forfeit' => 0, 'min_players_to_start' => 5,
        ],
    ], $overrides);
}

it('crea un torneo con su reglamento en la organización del usuario', function () {
    $user = userWithRole(Role::Organizer);

    $this->actingAs($user)->post('/admin/tournaments', tournamentPayload())->assertSessionHasNoErrors()->assertRedirect();

    $t = Tournament::with('rules')->firstOrFail();
    expect($t->club_id)->toBe($user->club_id)
        ->and($t->slug)->toBe('i-copa-de-verano')
        ->and($t->rules->max_roster_size)->toBe(10)
        ->and($t->rules->final_best_of)->toBe(5)
        ->and($t->rules->reinforcements_exempt_from_age)->toBeTrue();
});

it('ignora un club_id inyectado por un usuario que no es superadministrador', function () {
    $user = userWithRole(Role::Organizer);
    $other = Club::factory()->create();

    $this->actingAs($user)->post('/admin/tournaments', tournamentPayload(['club_id' => $other->id]))->assertRedirect();
    expect(Tournament::first()->club_id)->toBe($user->club_id);
});

it('valida los datos del torneo y del reglamento', function () {
    $user = userWithRole(Role::Organizer);
    $this->actingAs($user)->post('/admin/tournaments', tournamentPayload([
        'name' => '', 'status' => 'inventado',
        'rules' => ['min_age' => 18, 'max_age' => 15, 'final_best_of' => 4, 'max_roster_size' => 2],
    ]))->assertSessionHasErrors(['name', 'status', 'rules.max_age', 'rules.final_best_of', 'rules.max_roster_size']);
    expect(Tournament::count())->toBe(0);
});

it('impide duplicar un torneo con el mismo nombre y edición en la organización', function () {
    $user = userWithRole(Role::Organizer);
    $this->actingAs($user)->post('/admin/tournaments', tournamentPayload());
    $this->actingAs($user)->post('/admin/tournaments', tournamentPayload())->assertSessionHasErrors('edition');
    expect(Tournament::count())->toBe(1);
});

it('rechaza una temporada de otra organización', function () {
    $user = userWithRole(Role::Organizer);
    $foreignSeason = Season::factory()->create();
    $this->actingAs($user)->post('/admin/tournaments', tournamentPayload(['season_id' => $foreignSeason->id]))
        ->assertSessionHasErrors('season_id');
});

it('actualiza el reglamento de un torneo existente', function () {
    $user = userWithRole(Role::Organizer);
    $t = Tournament::factory()->create(['club_id' => $user->club_id]);

    $this->actingAs($user)->put("/admin/tournaments/{$t->id}", tournamentPayload(['rules' => ['max_roster_size' => 12, 'max_reinforcements' => 3]]))
        ->assertRedirect("/admin/tournaments/{$t->id}");
    expect($t->rules()->first()->max_roster_size)->toBe(12)->and($t->rules()->first()->max_reinforcements)->toBe(3);
});

it('mantiene un solo torneo destacado por organización', function () {
    $user = userWithRole(Role::Organizer);
    $old = Tournament::factory()->create(['club_id' => $user->club_id, 'is_featured' => true]);
    $this->actingAs($user)->post('/admin/tournaments', tournamentPayload(['is_featured' => 1]));
    expect($old->fresh()->is_featured)->toBeFalse()->and(Tournament::where('is_featured', true)->count())->toBe(1);
});

it('elimina torneos de forma lógica conservando el historial', function () {
    $user = userWithRole(Role::Organizer);
    $t = Tournament::factory()->create(['club_id' => $user->club_id]);
    $this->actingAs($user)->delete("/admin/tournaments/{$t->id}")->assertRedirect('/admin/tournaments');
    expect(Tournament::count())->toBe(0)->and(Tournament::withTrashed()->count())->toBe(1);
});

it('muestra la ficha administrativa del torneo', function () {
    $user = userWithRole(Role::Organizer);
    $t = Tournament::factory()->create(['club_id' => $user->club_id, 'motto' => 'Volviendo a la Tradición']);
    $this->actingAs($user)->get("/admin/tournaments/{$t->id}")->assertOk()->assertSee('Volviendo a la Tradición');
    $this->actingAs($user)->get("/admin/tournaments/{$t->id}/edit")->assertOk()->assertSee('Reglamento');
});
