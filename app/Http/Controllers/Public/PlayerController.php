<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\PlayerGameStat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlayerController extends Controller
{
    /** Solo jugadores activos que figuran en plantillas de torneos publicados. */
    private function publicPlayers(): Builder
    {
        return Player::where('is_active', true)
            ->whereHas('rosterEntries', fn ($q) => $q->whereHas('tournament', fn ($t) => $t->published()));
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $players = $this->publicPlayers()
            ->with(['rosterEntries' => fn ($q) => $q->whereHas('tournament', fn ($t) => $t->published())->with('tournamentTeam.team')->latest()])
            ->when(mb_strlen($search) >= 2, fn ($q) => $q->whereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"]))
            ->orderBy('last_name')->orderBy('first_name')->paginate(24)->withQueryString();

        return view('public.players.index', compact('players', 'search'));
    }

    public function show(int $id, ?string $slug = null): View|RedirectResponse
    {
        $player = $this->publicPlayers()->findOrFail($id);
        if ($slug !== $player->slug) {
            return redirect()->route('public.players.show', [$player->id, $player->slug], 301);
        }

        $history = $player->rosterEntries()
            ->whereHas('tournament', fn ($t) => $t->published())
            ->with(['tournament.season', 'tournamentTeam.team'])
            ->get()->sortByDesc(fn ($e) => $e->tournament->starts_on ?? $e->created_at);

        $current = $history->first();

        // Estadísticas agregadas por torneo (se llenan con la mesa de anotación, Fase 3).
        $stats = PlayerGameStat::where('player_id', $player->id)
            ->whereHas('game', fn ($g) => $g->inPublishedTournament()->whereIn('status', ['final', 'forfeit']))
            ->join('games', 'games.id', '=', 'player_game_stats.game_id')
            ->selectRaw('games.tournament_id, COUNT(*) gp, SUM(points) pts, SUM(offensive_rebounds + defensive_rebounds) reb,
                SUM(assists) ast, SUM(steals) stl, SUM(blocks) blk, SUM(seconds_played) secs')
            ->groupBy('games.tournament_id')->get()->keyBy('tournament_id');

        return view('public.players.show', compact('player', 'history', 'current', 'stats'));
    }
}
