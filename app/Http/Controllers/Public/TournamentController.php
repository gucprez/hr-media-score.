<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TournamentController extends Controller
{
    public function index(): View
    {
        $tournaments = Tournament::published()->with(['club', 'season', 'venue'])->withCount('registrations')
            ->orderByRaw("FIELD(status, 'in_progress', 'registration', 'finished', 'cancelled')")
            ->latest('starts_on')->latest()->paginate(12);

        return view('public.tournaments.index', compact('tournaments'));
    }

    public function show(int $id, ?string $slug = null): View|RedirectResponse
    {
        $tournament = Tournament::published()->with(['club', 'season', 'venue', 'rules'])->findOrFail($id);
        if ($slug !== $tournament->slug) {
            return redirect()->route('public.tournaments.show', [$tournament->id, $tournament->slug], 301);
        }

        $registrations = $tournament->registrations()->with('team')
            ->whereNotIn('status', ['withdrawn', 'disqualified'])
            ->with(['activeRoster' => fn ($q) => $q->with('player')])
            ->get()->sortBy(fn ($r) => $r->team->name);

        $games = $tournament->games()->with(['homeTeam.team', 'awayTeam.team', 'venue', 'periods'])
            ->orderByRaw('scheduled_at IS NULL, scheduled_at')->get();

        $standings = $tournament->standings()->with('tournamentTeam.team')->get();
        $series = $tournament->series()->with(['highSeed.team', 'lowSeed.team', 'winner.team'])->get();

        return view('public.tournaments.show', compact('tournament', 'registrations', 'games', 'standings', 'series'));
    }
}
