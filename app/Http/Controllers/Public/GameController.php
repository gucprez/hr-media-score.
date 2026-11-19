<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(): View
    {
        $rel = ['homeTeam.team', 'awayTeam.team', 'tournament', 'venue'];
        $upcoming = Game::inPublishedTournament()->upcoming()->with($rel)->take(30)->get();
        $results = Game::inPublishedTournament()->completed()->with($rel)->take(30)->get();

        return view('public.games.index', compact('upcoming', 'results'));
    }

    public function show(Game $game): View
    {
        abort_unless($game->tournament()->published()->exists(), 404);
        $game->load(['homeTeam.team', 'awayTeam.team', 'tournament', 'venue', 'periods',
            'playerStats' => fn ($q) => $q->with('player')->orderByDesc('points')]);

        return view('public.games.show', compact('game'));
    }
}
