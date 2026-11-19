<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeamController extends Controller
{
    /** Solo equipos activos inscritos en algún torneo publicado. */
    private function publicTeams(): Builder
    {
        return Team::where('is_active', true)
            ->whereHas('registrations', fn ($q) => $q->whereHas('tournament', fn ($t) => $t->published()));
    }

    public function index(): View
    {
        $teams = $this->publicTeams()->with('club')->orderBy('name')->get();

        return view('public.teams.index', compact('teams'));
    }

    public function show(int $id, ?string $slug = null): View|RedirectResponse
    {
        $team = $this->publicTeams()->with('club')->findOrFail($id);
        if ($slug !== $team->slug) {
            return redirect()->route('public.teams.show', [$team->id, $team->slug], 301);
        }

        $registrations = $team->registrations()
            ->whereHas('tournament', fn ($t) => $t->published())
            ->with(['tournament', 'standing', 'activeRoster' => fn ($q) => $q->with('player')])
            ->latest()->get();

        $current = $registrations->first();
        $regIds = $registrations->pluck('id');
        $games = Game::where(fn ($q) => $q->whereIn('home_team_id', $regIds)->orWhereIn('away_team_id', $regIds))
            ->with(['homeTeam.team', 'awayTeam.team', 'tournament', 'venue'])
            ->orderByRaw('scheduled_at IS NULL, scheduled_at')->get();

        return view('public.teams.show', compact('team', 'registrations', 'current', 'games'));
    }
}
