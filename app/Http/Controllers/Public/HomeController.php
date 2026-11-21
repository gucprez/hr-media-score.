<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Tournament;
use Carbon\Carbon;
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

        // Próxima jornada (o la de hoy, aunque ya tenga resultados) del torneo destacado.
        $matchday = collect();
        if ($featured) {
            $nextDate = $featured->games()->whereNotNull('scheduled_at')->where('status', '!=', 'cancelled')
                ->where('scheduled_at', '>=', now()->startOfDay())->orderBy('scheduled_at')->value('scheduled_at');
            if ($nextDate) {
                $matchday = $featured->games()->with($gameRelations)->whereDate('scheduled_at', Carbon::parse($nextDate)->toDateString())
                    ->where('status', '!=', 'cancelled')->orderBy('scheduled_at')->get();
            }
        }

        $others = Tournament::published()->with('club')
            ->when($featured, fn ($q) => $q->whereKeyNot($featured->id))->latest()->take(3)->get();

        return view('public.home', compact('featured', 'teams', 'standings', 'recent', 'upcoming', 'live', 'others', 'matchday'));
    }
}
