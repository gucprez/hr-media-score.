<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TournamentStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\TeamRoster;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $tournaments = Tournament::visibleTo($user)
            ->withCount(['registrations', 'rosters', 'games'])
            ->with('rules')
            ->whereNotIn('status', [TournamentStatus::Finished->value, TournamentStatus::Cancelled->value])
            ->orderByDesc('is_featured')->latest()->take(5)->get();

        $stats = [
            'tournaments' => Tournament::visibleTo($user)->count(),
            'active_tournaments' => Tournament::visibleTo($user)->whereIn('status', ['registration', 'in_progress'])->count(),
            'teams' => Team::visibleTo($user)->count(),
            'players' => Player::visibleTo($user)->count(),
            'rostered' => TeamRoster::whereHas('tournament', fn ($q) => $q->visibleTo($user))->count(),
            'games' => Game::visibleTo($user)->count(),
        ];

        $upcoming = Game::visibleTo($user)->upcoming()->with(['homeTeam.team', 'awayTeam.team', 'tournament'])->take(5)->get();

        $activity = $user->can('audit.view')
            ? AuditLog::with('user')
                ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('club_id', $user->club_id))
                ->latest('id')->take(8)->get()
            : collect();

        return view('admin.dashboard', compact('tournaments', 'stats', 'upcoming', 'activity'));
    }
}
