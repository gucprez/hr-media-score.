<?php

use App\Actions\RegisterTeamInTournament;
use App\Enums\Role;
use App\Livewire\Admin\PlayersTable;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentTeam;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => Storage::fake('public'));

it('registra un equipo con colores principales y secundarios', function () {
    $user = userWithRole(Role::Organizer);
    $this->actingAs($user)->post('/admin/teams', [
        'name' => 'Calle Duarte', 'short_name' => 'cdu', 'primary_color' => '#FFFFFF', 'primary_color_name' => 'Blanco',
        'secondary_colors' => [['name' => 'Azul rey', 'hex' => '#1D4ED8'], ['name' => 'Azul marino', 'hex' => '#0A1F44'], ['name' => '', 'hex' => '']],
        'is_active' => 1,
    ])->assertSessionHasNoErrors();

    $team = Team::firstOrFail();
    expect($team->club_id)->toBe($user->club_id)
        ->and($team->short_name)->toBe('CDU')
        ->and($team->secondary_colors)->toHaveCount(2)
        ->and($team->slug)->toBe('calle-duarte');
});

it('evita equipos duplicados dentro de la misma organización, no entre organizaciones', function () {
    $a = userWithRole(Role::Organizer);
    $b = userWithRole(Role::Organizer);
    $this->actingAs($a)->post('/admin/teams', ['name' => 'Ninjas GB', 'is_active' => 1])->assertSessionHasNoErrors();
    $this->actingAs($a)->post('/admin/teams', ['name' => 'Ninjas GB', 'is_active' => 1])->assertSessionHasErrors('name');
    $this->actingAs($b)->post('/admin/teams', ['name' => 'Ninjas GB', 'is_active' => 1])->assertSessionHasNoErrors();
    expect(Team::count())->toBe(2);
});

it('sube el logo oficial del equipo re-codificado y conservando transparencia', function () {
    $user = userWithRole(Role::Organizer);
    $this->actingAs($user)->post('/admin/teams', [
        'name' => 'Balkanes', 'is_active' => 1, 'logo' => UploadedFile::fake()->image('logo.png', 300, 300),
    ])->assertSessionHasNoErrors();

    $team = Team::firstOrFail();
    expect($team->logo_path)->toEndWith('.webp');
    Storage::disk('public')->assertExists($team->logo_path);
});

it('rechaza archivos que no son imágenes válidas', function () {
    $user = userWithRole(Role::Organizer);
    $this->actingAs($user)->post('/admin/teams', [
        'name' => 'Balkanes', 'is_active' => 1, 'logo' => UploadedFile::fake()->create('logo.php', 10, 'application/x-php'),
    ])->assertSessionHasErrors('logo');
    $this->actingAs($user)->post('/admin/teams', [
        'name' => 'Balkanes', 'is_active' => 1, 'logo' => UploadedFile::fake()->image('enorme.png')->size(6000),
    ])->assertSessionHasErrors('logo');
    expect(Team::count())->toBe(0);
});

it('inscribe un equipo en un torneo y evita la doble inscripción', function () {
    $tournament = Tournament::factory()->create();
    $team = Team::factory()->create(['club_id' => $tournament->club_id]);
    $action = app(RegisterTeamInTournament::class);

    $action->handle($tournament, $team);
    expect(fn () => $action->handle($tournament, $team))->toThrow(ValidationException::class);
    expect(TournamentTeam::count())->toBe(1);
});

it('no inscribe equipos de otra organización ni en torneos cerrados', function () {
    $tournament = Tournament::factory()->create();
    expect(fn () => app(RegisterTeamInTournament::class)->handle($tournament, Team::factory()->create()))
        ->toThrow(ValidationException::class);

    $closed = Tournament::factory()->create(['status' => 'finished']);
    expect(fn () => app(RegisterTeamInTournament::class)->handle($closed, Team::factory()->create(['club_id' => $closed->club_id])))
        ->toThrow(ValidationException::class);
});

it('no elimina equipos con historial de inscripciones', function () {
    $user = userWithRole(Role::Organizer);
    $reg = TournamentTeam::factory()->create(['tournament_id' => Tournament::factory()->create(['club_id' => $user->club_id])->id]);
    $this->actingAs($user)->delete("/admin/teams/{$reg->team_id}")->assertSessionHas('error');
    expect(Team::find($reg->team_id))->not->toBeNull();
});

it('registra un jugador y normaliza sus datos', function () {
    $user = userWithRole(Role::Organizer);
    $this->actingAs($user)->post('/admin/players', [
        'first_name' => '  Juan   Carlos ', 'last_name' => 'Pérez', 'birth_date' => now()->subYears(16)->toDateString(),
        'document_number' => '402 1234567-8', 'position' => 'pg', 'is_active' => 1,
    ])->assertSessionHasNoErrors();

    $p = Player::firstOrFail();
    expect($p->first_name)->toBe('Juan Carlos')->and($p->document_number)->toBe('4021234567-8')->and($p->club_id)->toBe($user->club_id);
});

it('valida los datos del jugador', function () {
    $user = userWithRole(Role::Organizer);
    $this->actingAs($user)->post('/admin/players', [
        'first_name' => '', 'last_name' => 'X', 'birth_date' => now()->addDay()->toDateString(), 'position' => 'zz', 'height_cm' => 20,
        'photo' => UploadedFile::fake()->create('foto.pdf', 10, 'application/pdf'),
    ])->assertSessionHasErrors(['first_name', 'birth_date', 'position', 'height_cm', 'photo']);
});

it('evita jugadores duplicados por identidad y por documento', function () {
    $user = userWithRole(Role::Organizer);
    $data = ['first_name' => 'Luis', 'last_name' => 'Gómez', 'birth_date' => '2010-05-05', 'document_number' => 'ABC123', 'is_active' => 1];
    $this->actingAs($user)->post('/admin/players', $data)->assertSessionHasNoErrors();
    $this->actingAs($user)->post('/admin/players', array_merge($data, ['document_number' => null]))->assertSessionHasErrors('birth_date');
    $this->actingAs($user)->post('/admin/players', array_merge($data, ['first_name' => 'Otro']))->assertSessionHasErrors('document_number');
    expect(Player::count())->toBe(1);
});

it('lista jugadores con búsqueda en vivo (Livewire)', function () {
    $user = userWithRole(Role::Organizer);
    Player::factory()->create(['club_id' => $user->club_id, 'first_name' => 'Ramón', 'last_name' => 'Almonte']);
    Player::factory()->create(['club_id' => $user->club_id, 'first_name' => 'Pedro', 'last_name' => 'Brito']);
    Player::factory()->create(['first_name' => 'Ramón', 'last_name' => 'Ajeno']); // otro club

    $this->actingAs($user);
    Livewire\Livewire::test(PlayersTable::class)
        ->set('search', 'Ramón')
        ->assertSee('Almonte')->assertDontSee('Brito')->assertDontSee('Ajeno');
});

it('procesa logos grandes sin agotar la memoria (límite de PHP de 128 MB)', function () {
    $previous = ini_get('memory_limit');
    ini_set('memory_limit', '128M');
    try {
        $user = userWithRole(Role::Organizer);
        $this->actingAs($user)->post('/admin/teams', [
            'name' => 'Logo Grande', 'is_active' => 1, 'logo' => UploadedFile::fake()->image('logo.png', 4800, 4800),
        ])->assertSessionHasNoErrors()->assertRedirect();
        expect(Team::where('name', 'Logo Grande')->value('logo_path'))->toEndWith('.webp');
    } finally {
        ini_set('memory_limit', $previous);
    }
});

it('un archivo de imagen corrupto se informa como error del campo, no como error 500', function () {
    $user = userWithRole(Role::Organizer);
    $team = Team::factory()->create(['club_id' => $user->club_id]);
    $fake = UploadedFile::fake()->createWithContent('logo.png', "\x89PNG\r\n\x1a\n".str_repeat('x', 200));
    $this->actingAs($user)->put("/admin/teams/{$team->id}", ['name' => $team->name, 'is_active' => 1, 'logo' => $fake])
        ->assertSessionHasErrors('logo');
});
