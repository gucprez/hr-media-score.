<?php

use App\Enums\GameEventType;
use App\Enums\GameStatus;
use App\Enums\Role;
use App\Models\Player;
use App\Models\Team;
use Carbon\Carbon;

it('calcula la edad del jugador en una fecha de referencia', function () {
    $p = new Player(['birth_date' => '2010-06-15']);
    expect($p->ageOn(Carbon::parse('2026-06-14')))->toBe(15)
        ->and($p->ageOn(Carbon::parse('2026-06-15')))->toBe(16)
        ->and((new Player)->ageOn())->toBeNull();
});

it('elige un color de texto legible sobre el color del equipo', function () {
    expect((new Team(['primary_color' => '#FFFFFF']))->contrastColor())->toBe('#0B101A')
        ->and((new Team(['primary_color' => '#111111']))->contrastColor())->toBe('#FFFFFF');
});

it('genera abreviaturas de equipo', function () {
    expect((new Team(['name' => 'Ñandúes']))->abbreviation())->toBe('NAN')
        ->and((new Team(['name' => 'X', 'short_name' => 'NGB']))->abbreviation())->toBe('NGB');
});

it('los enums exponen etiquetas en español', function () {
    expect(Role::SuperAdmin->label())->toBe('Superadministrador')
        ->and(GameEventType::Point3->points())->toBe(3)
        ->and(GameStatus::Forfeit->isFinished())->toBeTrue()
        ->and(GameStatus::options())->toHaveKey('live');
});
