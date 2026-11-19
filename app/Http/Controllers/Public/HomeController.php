<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Tournament;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featured = Tournament::published()->with(['club', 'venue', 'rules'])
            ->withCount('registrations')
            ->orderByDesc('is_featured')->orderByRaw("FIELD(status, 'in_progress', 'registration', 'finished', 'cancelled')")
            ->latest()->first();

        $teams = $featured
            ? $featured->registrations()->with('team')->whereNotIn('status', ['withdrawn', 'disqualified'])
                ->withCount('activeRoster')->get()->sortBy(fn ($r) => $r->team->name)
            : collect();

        $standings = $featured ? $featured->standings()->with('tournamentTeam.team')->get() : collect();

        $gameRelations = ['homeTeam.team', 'awayTeam.team', 'tournament', 'venue'];
        $recent = Game::inPublishedTournament()->completed()->with($gameRelations)->take(6)->get();
        $upcoming = Game::inPublishedTournament()->upcoming()->with($gameRelations)->take(6)->get();
        $live = Game::inPublishedTournament()->where('status', 'live')->with($gameRelations)->get();

        $others = Tournament::published()->with('club')
            ->when($featured, fn ($q) => $q->whereKeyNot($featured->id))->latest()->take(3)->get();

        return view('public.home', compact('featured', 'teams', 'standings', 'recent', 'upcoming', 'live', 'others'));
    }
}
