<?php

use App\Actions\RecordGameResult;
use App\Models\Game;
use App\Models\PlayoffSeries;
use App\Models\Standing;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentTeam;
use App\Services\PlayoffManager;
use App\Services\RoundRobinScheduler;
use Illuminate\Validation\ValidationException;

/** Torneo con N equipos inscritos (nombres ordenables) y reglamento del XIV torneo. */
function competition(int $teams = 4, array $rules = []): array
{
    $t = Tournament::factory()->create(['status' => 'in_progress']);
    $t->rules()->update(array_merge(['round_robin_legs' => 1, 'teams_qualifying' => 3, 'direct_final_seeds' => 1,
        'semifinal_best_of' => 3, 'final_best_of' => 5, 'points_per_win' => 2, 'points_per_loss' => 1, 'points_per_forfeit' => 0,
        'tiebreakers' => ['head_to_head', 'head_to_head_diff', 'point_diff', 'points_for']], $rules));
    $regs = collect(range(1, $teams))->map(fn ($i) => TournamentTeam::factory()->create([
        'tournament_id' => $t->id,
        'team_id' => Team::factory()->create(['club_id' => $t->club_id, 'name' => "Equipo {$i}"])->id,
    ]));

    return [$t->fresh('rules'), $regs];
}

function play(Game $g, int $home, int $away): Game
{
    return app(RecordGameResult::class)->handle($g, ['status' => 'final', 'home_score' => $home, 'away_score' => $away]);
}

function gameBetween(Tournament $t, TournamentTeam $a, TournamentTeam $b): Game
{
    return $t->games()->where('stage', 'regular')->where(fn ($q) => $q
        ->where(fn ($w) => $w->where('home_team_id', $a->id)->where('away_team_id', $b->id))
        ->orWhere(fn ($w) => $w->where('home_team_id', $b->id)->where('away_team_id', $a->id)))->firstOrFail();
}

it('genera todos contra todos: cada par una vez y nadie juega dos veces por jornada', function (int $n) {
    [$t] = competition($n);
    $created = app(RoundRobinScheduler::class)->generate($t);
    $games = $t->games()->get();

    expect($created)->toBe($n * ($n - 1) / 2)
        ->and($games->map(fn ($g) => collect([$g->home_team_id, $g->away_team_id])->sort()->implode('-'))->unique())->toHaveCount($created);
    foreach ($games->groupBy('round_number') as $round) {
        $ids = $round->flatMap(fn ($g) => [$g->home_team_id, $g->away_team_id]);
        expect($ids->unique()->count())->toBe($ids->count());
    }
    expect($games->max('round_number'))->toBe($n % 2 ? $n : $n - 1);
})->with([4, 5, 6]);

it('a doble vuelta invierte la localía', function () {
    [$t] = competition(4, ['round_robin_legs' => 2]);
    expect(app(RoundRobinScheduler::class)->generate($t))->toBe(12);
    $pairs = $t->games()->get()->map(fn ($g) => "{$g->home_team_id}-{$g->away_team_id}");
    expect($pairs->unique())->toHaveCount(12);
});

it('respeta una jornada cargada a mano y completa solo lo que falta', function () {
    [$t, $r] = competition(4);
    foreach ([[$r[3], $r[1]], [$r[0], $r[2]]] as [$h, $a]) {
        Game::create(['club_id' => $t->club_id, 'tournament_id' => $t->id, 'home_team_id' => $h->id, 'away_team_id' => $a->id,
            'stage' => 'regular', 'round_number' => 1, 'status' => 'scheduled', 'scheduled_at' => '2026-11-21 18:00']);
    }
    expect(app(RoundRobinScheduler::class)->generate($t, ['start_at' => '2026-11-28 18:00', 'days_between' => 7]))->toBe(4);
    expect($t->games()->count())->toBe(6)
        ->and($t->games()->where('round_number', 1)->count())->toBe(2)
        ->and($t->games()->whereIn('round_number', [2, 3])->count())->toBe(4)
        ->and($t->games()->where('round_number', 3)->first()->scheduled_at->toDateString())->toBe('2026-12-05');
    expect(app(RoundRobinScheduler::class)->generate($t))->toBe(0); // idempotente
});

it('no admite empates en un resultado final y exige los períodos reglamentarios', function () {
    [$t] = competition(2);
    app(RoundRobinScheduler::class)->generate($t);
    $g = $t->games()->first();
    expect(fn () => play($g, 60, 60))->toThrow(ValidationException::class);
    expect(fn () => app(RecordGameResult::class)->handle($g, ['status' => 'final', 'periods' => [['home' => 10, 'away' => 8]]]))
        ->toThrow(ValidationException::class);
});

it('suma los períodos (con prórroga) y define el ganador', function () {
    [$t, $r] = competition(2);
    app(RoundRobinScheduler::class)->generate($t);
    $g = app(RecordGameResult::class)->handle($t->games()->first(), ['status' => 'final', 'periods' => [
        ['home' => 15, 'away' => 12], ['home' => 10, 'away' => 14], ['home' => 12, 'away' => 11], ['home' => 13, 'away' => 13], ['home' => 9, 'away' => 6],
    ]]);
    expect($g->home_score)->toBe(59)->and($g->away_score)->toBe(56)
        ->and($g->winner_team_id)->toBe($g->home_team_id)
        ->and($g->periods)->toHaveCount(5)
        ->and($g->periods->last()->is_overtime)->toBeTrue();
});

it('calcula la tabla: victoria 2, derrota 1, forfeit 0', function () {
    [$t, $r] = competition(3);
    app(RoundRobinScheduler::class)->generate($t);
    play(gameBetween($t, $r[0], $r[1])->fresh(), ...(gameBetween($t, $r[0], $r[1])->home_team_id === $r[0]->id ? [70, 60] : [60, 70]));
    $g = gameBetween($t, $r[1], $r[2]);
    app(RecordGameResult::class)->handle($g, ['status' => 'forfeit', 'forfeit_loser' => $g->home_team_id === $r[2]->id ? 'home' : 'away']);

    $s = Standing::where('tournament_id', $t->id)->get()->keyBy('tournament_team_id');
    expect($s[$r[0]->id]->standing_points)->toBe(2)
        ->and($s[$r[1]->id]->standing_points)->toBe(3)   // perdió (1) + ganó por forfeit (2)
        ->and($s[$r[2]->id]->standing_points)->toBe(0)
        ->and($s[$r[2]->id]->forfeits)->toBe(1)
        ->and($s[$r[1]->id]->position)->toBe(1);
});

it('desempata por enfrentamiento directo antes que por diferencia general', function () {
    [$t, $r] = competition(3);
    app(RoundRobinScheduler::class)->generate($t);
    $res = function ($winner, $loser, $w, $l) use ($t) {
        $g = gameBetween($t, $winner, $loser);
        play($g, ...($g->home_team_id === $winner->id ? [$w, $l] : [$l, $w]));
    };
    // Triple empate imposible con 3 equipos y 2 partidos c/u salvo ciclo: A>B, B>C, C>A
    $res($r[0], $r[1], 61, 60);   // A le gana a B por 1
    $res($r[1], $r[2], 90, 50);   // B le gana a C por 40
    $res($r[2], $r[0], 70, 60);   // C le gana a A por 10
    // Todos 3 pts; h2h entre los 3 empatado (3 c/u); h2h diff: A -9, B +39, C -30 → B, A, C
    $order = Standing::where('tournament_id', $t->id)->orderBy('position')->pluck('tournament_team_id')->all();
    expect($order)->toBe([$r[1]->id, $r[0]->id, $r[2]->id]);
});

it('genera playoffs: 2.º vs 3.º al mejor de 3 y el 1.º espera en la final', function () {
    [$t, $r] = competition(4);
    app(RoundRobinScheduler::class)->generate($t);
    // Orden final: Equipo 1 > 2 > 3 > 4 (el de menor número siempre gana)
    foreach ($t->games()->with('homeTeam.team', 'awayTeam.team')->get() as $g) {
        $homeBetter = $g->homeTeam->team->name < $g->awayTeam->team->name;
        play($g, $homeBetter ? 80 : 70, $homeBetter ? 70 : 80);
    }
    $series = app(PlayoffManager::class)->generate($t)->keyBy('slot');

    expect($series['SF']->high_seed_team_id)->toBe($r[1]->id)->and($series['SF']->low_seed_team_id)->toBe($r[2]->id)
        ->and($series['SF']->best_of)->toBe(3)
        ->and($series['SF']->games()->count())->toBe(2)               // mínimo para decidir
        ->and($series['F']->high_seed_team_id)->toBe($r[0]->id)
        ->and($series['F']->low_seed_team_id)->toBeNull()
        ->and($series['F']->games()->count())->toBe(0);
    expect(fn () => app(PlayoffManager::class)->generate($t))->toThrow(ValidationException::class);
});

it('una serie 1–1 agrega el tercer juego; el ganador avanza y la final se crea al mejor de 5', function () {
    [$t, $r] = competition(4);
    app(RoundRobinScheduler::class)->generate($t);
    foreach ($t->games()->with('homeTeam.team', 'awayTeam.team')->get() as $g) {
        $homeBetter = $g->homeTeam->team->name < $g->awayTeam->team->name;
        play($g, $homeBetter ? 80 : 70, $homeBetter ? 70 : 80);
    }
    app(PlayoffManager::class)->generate($t);
    $sf = PlayoffSeries::where('slot', 'SF')->first();
    [$g1, $g2] = $sf->games()->get();
    expect($g1->home_team_id)->toBe($r[1]->id)->and($g2->home_team_id)->toBe($r[2]->id);

    play($g1, 75, 70);                       // gana el 2.º
    play($g2, 75, 70);                       // gana el 3.º (local en el juego 2)
    $sf->refresh();
    expect($sf->high_wins)->toBe(1)->and($sf->low_wins)->toBe(1)->and($sf->games()->count())->toBe(3);

    play($sf->games()->where('series_game_number', 3)->first(), 60, 66);   // gana el 3.º de visitante
    $sf->refresh();
    $final = PlayoffSeries::where('slot', 'F')->first();
    expect($sf->winner_team_id)->toBe($r[2]->id)->and($sf->status)->toBe('finished')
        ->and($final->low_seed_team_id)->toBe($r[2]->id)
        ->and($final->games()->count())->toBe(3);

    foreach ($final->games()->get() as $g) {
        play($g, $g->home_team_id === $r[0]->id ? 90 : 70, $g->home_team_id === $r[0]->id ? 70 : 90);
    }
    expect($final->fresh()->winner_team_id)->toBe($r[0]->id)
        ->and($final->games()->count())->toBe(3)
        ->and($t->fresh()->status->value)->toBe('finished');
});

it('una serie decidida elimina los juegos que ya no hacen falta', function () {
    [$t, $r] = competition(4, ['teams_qualifying' => 2, 'direct_final_seeds' => 0, 'final_best_of' => 3]);
    app(RoundRobinScheduler::class)->generate($t);
    foreach ($t->games()->with('homeTeam.team', 'awayTeam.team')->get() as $g) {
        $homeBetter = $g->homeTeam->team->name < $g->awayTeam->team->name;
        play($g, $homeBetter ? 80 : 70, $homeBetter ? 70 : 80);
    }
    $final = app(PlayoffManager::class)->generate($t)->first();
    [$g1, $g2] = $final->games()->get();
    play($g1, 80, 70);
    play($g2, 70, 80);   // el 1.º gana de visitante: 2–0
    expect($final->fresh()->status)->toBe('finished')->and($final->games()->count())->toBe(2);
});

it('no genera playoffs con la serie regular incompleta o formatos no soportados', function () {
    [$t] = competition(4);
    app(RoundRobinScheduler::class)->generate($t);
    play($t->games()->first(), 70, 60);
    expect(fn () => app(PlayoffManager::class)->generate($t))->toThrow(ValidationException::class, 'quedan 5');

    [$t2] = competition(4, ['teams_qualifying' => 4, 'direct_final_seeds' => 1]);
    app(RoundRobinScheduler::class)->generate($t2);
    foreach ($t2->games()->get() as $g) {
        play($g, 70, 60);
    }
    expect(fn () => app(PlayoffManager::class)->generate($t2))->toThrow(ValidationException::class, 'no soportado');
});

it('cambiar un resultado de la serie regular reordena la tabla', function () {
    [$t, $r] = competition(2);
    app(RoundRobinScheduler::class)->generate($t);
    $g = $t->games()->first();
    play($g, 70, 60);
    expect(Standing::where('position', 1)->value('tournament_team_id'))->toBe($g->home_team_id);
    play($g, 60, 70);
    expect(Standing::where('position', 1)->value('tournament_team_id'))->toBe($g->away_team_id);
    app(RecordGameResult::class)->handle($g, ['status' => 'postponed']);
    expect(Standing::sum('played'))->toBe(0);
});
