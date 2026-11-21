<?php

namespace App\Http\Controllers\Admin;

use App\Actions\RecordGameResult;
use App\Enums\GameStage;
use App\Enums\GameStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GameRequest;
use App\Http\Requests\Admin\GameResultRequest;
use App\Models\Game;
use App\Models\Tournament;
use App\Models\User;
use App\Models\Venue;
use App\Services\PlayoffManager;
use App\Services\RoundRobinScheduler;
use App\Services\StandingsCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GameController extends Controller
{
    /** Listado general de partidos (el anotador ve solo los asignados). */
    public function all(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->can('games.manage') || $user->can('games.score'), 403);

        $games = Game::visibleTo($user)
            ->when(! $user->can('games.manage'), fn ($q) => $q->where('scorekeeper_id', $user->id))
            ->with(['homeTeam.team', 'awayTeam.team', 'tournament', 'venue', 'scorekeeper'])
            ->when($request->string('status')->value(), fn ($q, $s) => $q->where('status', $s))
            ->orderByRaw("FIELD(status, 'live', 'scheduled', 'postponed', 'final', 'forfeit', 'cancelled')")
            ->orderByRaw('scheduled_at IS NULL, scheduled_at')
            ->paginate(30)->withQueryString();

        return view('admin.games.all', compact('games'));
    }

    /** Calendario, resultados, posiciones y playoffs de un torneo. */
    public function index(Tournament $tournament, StandingsCalculator $standings): View
    {
        $this->authorize('view', $tournament);
        $tournament->load(['rules', 'venue']);
        $games = $tournament->games()->with(['homeTeam.team', 'awayTeam.team', 'venue', 'scorekeeper', 'periods'])
            ->orderByRaw("FIELD(stage, 'regular', 'quarterfinal', 'semifinal', 'third_place', 'final', 'friendly')")
            ->orderBy('round_number')->orderBy('series_game_number')->orderByRaw('scheduled_at IS NULL, scheduled_at')->get();

        return view('admin.games.index', [
            'tournament' => $tournament,
            'regular' => $games->where('stage', GameStage::Regular)->groupBy(fn ($g) => $g->round_number ?? 0),
            'playoffGames' => $games->where('stage', '!=', GameStage::Regular),
            'standings' => $tournament->standings()->with('tournamentTeam.team')->get(),
            'series' => $tournament->series()->with(['highSeed.team', 'lowSeed.team', 'winner.team', 'games'])->get(),
            'venues' => Venue::where('club_id', $tournament->club_id)->orderBy('name')->get(),
            'canManage' => auth()->user()->can('update', $tournament) && auth()->user()->can('games.manage'),
        ]);
    }

    public function create(Tournament $tournament): View
    {
        $this->authorize('create', Game::class);
        abort_unless(auth()->user()->belongsToClub($tournament->club_id), 403);
        $game = new Game(['stage' => 'regular', 'venue_id' => $tournament->venue_id,
            'round_number' => ((int) $tournament->games()->where('stage', 'regular')->max('round_number')) ?: 1]);

        return view('admin.games.form', $this->formData($tournament, $game));
    }

    public function store(GameRequest $request, Tournament $tournament): RedirectResponse
    {
        Game::create($request->gameData() + ['club_id' => $tournament->club_id, 'tournament_id' => $tournament->id, 'status' => GameStatus::Scheduled]);

        return redirect()->route('admin.tournaments.games.index', $tournament)->with('success', 'Partido programado.');
    }

    public function edit(Tournament $tournament, Game $game): View
    {
        $this->authorize('update', $game);
        $this->ensureBelongs($tournament, $game);

        return view('admin.games.form', $this->formData($tournament, $game));
    }

    public function update(GameRequest $request, Tournament $tournament, Game $game): RedirectResponse
    {
        $this->ensureBelongs($tournament, $game);
        $data = $request->gameData();
        if ($game->series_id) {
            // En playoffs los equipos y la fase los define la serie.
            unset($data['home_team_id'], $data['away_team_id'], $data['stage'], $data['round_number']);
        }
        $game->update($data);

        return redirect()->route('admin.tournaments.games.index', $tournament)->with('success', 'Partido actualizado.');
    }

    public function destroy(Tournament $tournament, Game $game, StandingsCalculator $standings): RedirectResponse
    {
        $this->authorize('delete', $game);
        $this->ensureBelongs($tournament, $game);
        if ($game->series_id) {
            return back()->with('error', 'Los partidos de playoffs se gestionan desde su serie.');
        }
        $wasCounted = $game->status->isFinished();
        $game->delete();
        if ($wasCounted) {
            $standings->recalculate($tournament);
        }

        return back()->with('success', 'Partido eliminado.');
    }

    public function generate(Request $request, Tournament $tournament, RoundRobinScheduler $scheduler): RedirectResponse
    {
        $this->authorize('update', $tournament);
        abort_unless($request->user()->can('games.manage'), 403);
        $opts = $request->validate([
            'start_at' => ['nullable', 'date'],
            'days_between' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);
        $count = $scheduler->generate($tournament, $opts);

        return back()->with($count ? 'success' : 'error', $count
            ? "Se programaron {$count} partido(s) de la serie regular."
            : 'El calendario de la serie regular ya está completo.');
    }

    public function editResult(Tournament $tournament, Game $game): View
    {
        $this->authorize('recordResult', $game);
        $this->ensureBelongs($tournament, $game);
        $game->load(['homeTeam.team', 'awayTeam.team', 'periods', 'venue', 'series']);

        return view('admin.games.result', ['tournament' => $tournament->load('rules'), 'game' => $game]);
    }

    public function updateResult(GameResultRequest $request, Tournament $tournament, Game $game, RecordGameResult $action): RedirectResponse
    {
        $this->ensureBelongs($tournament, $game);
        $action->handle($game, $request->resultData());

        return redirect()->route($request->user()->can('games.manage') ? 'admin.tournaments.games.index' : 'admin.games.all', $tournament)
            ->with('success', 'Resultado guardado. La tabla de posiciones se actualizó.');
    }

    public function recalculate(Tournament $tournament, StandingsCalculator $standings): RedirectResponse
    {
        $this->authorize('update', $tournament);
        $standings->recalculate($tournament);

        return back()->with('success', 'Tabla de posiciones recalculada.');
    }

    public function generatePlayoffs(Tournament $tournament, PlayoffManager $playoffs): RedirectResponse
    {
        $this->authorize('update', $tournament);
        abort_unless(auth()->user()->can('games.manage'), 403);
        try {
            $playoffs->generate($tournament);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', 'Playoffs generados según la tabla de posiciones.');
    }

    private function ensureBelongs(Tournament $tournament, Game $game): void
    {
        abort_unless($game->tournament_id === $tournament->id, 404);
    }

    private function formData(Tournament $tournament, Game $game): array
    {
        return [
            'tournament' => $tournament,
            'game' => $game,
            'teams' => $tournament->registrations()->with('team')->get()->sortBy(fn ($r) => $r->team->name)
                ->mapWithKeys(fn ($r) => [$r->id => $r->displayName()]),
            'venues' => Venue::where('club_id', $tournament->club_id)->orderBy('name')->pluck('name', 'id'),
            'scorekeepers' => User::where('club_id', $tournament->club_id)->where('is_active', true)
                ->role([Role::Scorekeeper->value, Role::Organizer->value, Role::ClubAdmin->value])->orderBy('name')->pluck('name', 'id'),
        ];
    }
}
