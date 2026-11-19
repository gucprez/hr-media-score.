<?php

use App\Actions\AssignPlayerToRoster;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentTeam;
use Database\Seeders\GustavoBehallSeeder;

it('la portada muestra el torneo destacado y los equipos desde la base de datos', function () {
    $this->seed(GustavoBehallSeeder::class);

    $this->get('/')->assertOk()
        ->assertSee('Torneo Juvenil Infantil con Refuerzos')
        ->assertSee('Volviendo a la Tradición')
        ->assertSee('Techado Club Gustavo Behall')
        ->assertSeeInOrder(['Balkanes', 'Cacuses', 'Calle Duarte', 'Ninjas GB'])
        ->assertSee('assets/branding/hr-logo-principal', false);
});

it('la portada funciona sin datos y no inventa información', function () {
    $this->get('/')->assertOk()->assertSee('Torneos, resultados');
    $this->get('/partidos')->assertOk()->assertSee('Calendario por publicar');
});

it('muestra la ficha pública del torneo con su reglamento', function () {
    $this->seed(GustavoBehallSeeder::class);
    $t = Tournament::firstOrFail();

    $this->get("/torneos/{$t->id}/{$t->slug}")->assertOk()
        ->assertSee('Jugadores de 15 a 18 años.')
        ->assertSee('Máximo 10 jugadores por equipo.')
        ->assertSee('2 refuerzos por equipo.')
        ->assertSee('Final al mejor de 5.')
        ->assertSee('Participación mínima de 16 minutos por jugador.');
    $this->get("/torneos/{$t->id}/slug-viejo")->assertRedirect("/torneos/{$t->id}/{$t->slug}");
});

it('oculta torneos en borrador o privados', function () {
    $draft = Tournament::factory()->draft()->create(['name' => 'Torneo Secreto']);
    $private = Tournament::factory()->create(['is_public' => false]);
    $this->get("/torneos/{$draft->id}")->assertNotFound();
    $this->get("/torneos/{$private->id}")->assertNotFound();
    $this->get('/torneos')->assertDontSee('Torneo Secreto');
});

it('la ficha pública del jugador no expone datos personales', function () {
    $t = Tournament::factory()->create();
    $reg = TournamentTeam::factory()->create(['tournament_id' => $t->id]);
    $p = Player::factory()->age(16)->create([
        'club_id' => $t->club_id, 'first_name' => 'Mario', 'last_name' => 'Castillo',
        'document_number' => 'DOC999888', 'guardian_phone' => '809-555-0101', 'birth_date' => '2010-02-03',
    ]);
    app(AssignPlayerToRoster::class)->handle($reg, $p, ['jersey_number' => 11]);

    $this->get("/jugadores/{$p->id}/{$p->slug}")->assertOk()
        ->assertSee('Mario')->assertSee('#11')
        ->assertDontSee('DOC999888')->assertDontSee('809-555-0101')->assertDontSee('03/02/2010');
    $this->get('/jugadores')->assertOk()->assertSee('Castillo');
});

it('no publica jugadores inactivos ni sin plantilla', function () {
    $free = Player::factory()->create();
    $this->get("/jugadores/{$free->id}")->assertNotFound();
});

it('muestra la ficha pública del equipo con su plantilla', function () {
    $t = Tournament::factory()->create();
    $reg = TournamentTeam::factory()->create(['tournament_id' => $t->id]);
    $p = Player::factory()->age(16)->create(['club_id' => $t->club_id, 'last_name' => 'Plantillado']);
    app(AssignPlayerToRoster::class)->handle($reg, $p, ['jersey_number' => 33]);

    $this->get("/equipos/{$reg->team->id}/{$reg->team->slug}")->assertOk()->assertSee($reg->team->name)->assertSee('Plantillado');
    $this->get('/equipos')->assertOk()->assertSee($reg->team->name);
});

it('publica el manifiesto web con los iconos oficiales', function () {
    $this->get('/manifest.webmanifest')->assertOk()
        ->assertJsonPath('name', 'HR Media Score')
        ->assertJsonPath('theme_color', '#0B101A');
});

it('envía cabeceras de seguridad', function () {
    $this->get('/')->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('los recursos de marca existen en public/assets/branding', function () {
    foreach (['hr-logo-principal.png', 'hr-logo-horizontal.png', 'hr-isotipo.png', 'icons/icon-512.png', 'icons/favicon.ico'] as $f) {
        expect(public_path("assets/branding/{$f}"))->toBeFile();
    }
});
