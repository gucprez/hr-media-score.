<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SaveTournament;
use App\Enums\TournamentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TournamentRequest;
use App\Models\Club;
use App\Models\Season;
use App\Models\Tournament;
use App\Models\TournamentRule;
use App\Models\Venue;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TournamentController extends Controller
{
    public function __construct(private ImageService $images) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Tournament::class);

        $tournaments = Tournament::visibleTo($request->user())
            ->with(['club', 'season', 'rules'])
            ->withCount(['registrations', 'rosters'])
            ->when($request->string('q')->trim()->value(), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")->orWhere('edition', 'like', "%{$s}%")->orWhere('category', 'like', "%{$s}%")))
            ->when($request->string('status')->value(), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('is_featured')->latest()->paginate(15)->withQueryString();

        return view('admin.tournaments.index', compact('tournaments'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Tournament::class);
        $tournament = new Tournament(['status' => TournamentStatus::Draft, 'gender' => 'male', 'is_public' => true]);
        $tournament->setRelation('rules', new TournamentRule(TournamentRule::defaults()));

        return view('admin.tournaments.form', $this->formData($request, $tournament));
    }

    public function store(TournamentRequest $request, SaveTournament $save): RedirectResponse
    {
        $tournament = $save->handle($request->tournamentData(), $request->rulesData());
        $this->syncLogo($request, $tournament);

        return redirect()->route('admin.tournaments.show', $tournament)->with('success', 'Torneo creado.');
    }

    public function show(Tournament $tournament): View
    {
        $this->authorize('view', $tournament);
        $tournament->load(['club', 'season', 'venue', 'rules', 'registrations' => fn ($q) => $q->with('team')->withCount('activeRoster')]);

        return view('admin.tournaments.show', compact('tournament'));
    }

    public function edit(Request $request, Tournament $tournament): View
    {
        $this->authorize('update', $tournament);
        $tournament->load('rules');

        return view('admin.tournaments.form', $this->formData($request, $tournament));
    }

    public function update(TournamentRequest $request, Tournament $tournament, SaveTournament $save): RedirectResponse
    {
        $save->handle($request->tournamentData(), $request->rulesData(), $tournament);
        $this->syncLogo($request, $tournament);

        return redirect()->route('admin.tournaments.show', $tournament)->with('success', 'Torneo actualizado.');
    }

    public function destroy(Tournament $tournament): RedirectResponse
    {
        $this->authorize('delete', $tournament);

        if ($tournament->games()->whereIn('status', ['final', 'forfeit', 'live'])->exists()) {
            return back()->with('error', 'No se puede eliminar un torneo con partidos jugados. Cámbielo a «Cancelado» o «Finalizado».');
        }
        $tournament->delete(); // borrado lógico: se conserva el historial

        return redirect()->route('admin.tournaments.index')->with('success', 'Torneo eliminado.');
    }

    private function formData(Request $request, Tournament $tournament): array
    {
        $user = $request->user();

        return [
            'tournament' => $tournament,
            'seasons' => Season::visibleTo($user)->orderByDesc('id')->get(),
            'venues' => Venue::visibleTo($user)->orderBy('name')->get(),
            'clubs' => $user->isSuperAdmin() ? Club::orderBy('name')->get() : collect(),
        ];
    }

    private function syncLogo(TournamentRequest $request, Tournament $tournament): void
    {
        if ($request->hasFile('logo')) {
            $tournament->forceFill(['logo_path' => $this->images->store($request->file('logo'), "clubs/{$tournament->club_id}/tournaments", 600, $tournament->logo_path)])->save();
        } elseif ($request->boolean('remove_logo') && $tournament->logo_path) {
            $this->images->delete($tournament->logo_path);
            $tournament->forceFill(['logo_path' => null])->save();
        }
        if ($request->hasFile('sponsor_logo')) {
            $tournament->forceFill(['sponsor_logo_path' => $this->images->store($request->file('sponsor_logo'), "clubs/{$tournament->club_id}/sponsors", 700, $tournament->sponsor_logo_path, 'sponsor_logo')])->save();
        } elseif ($request->boolean('remove_sponsor_logo') && $tournament->sponsor_logo_path) {
            $this->images->delete($tournament->sponsor_logo_path);
            $tournament->forceFill(['sponsor_logo_path' => null])->save();
        }
    }
}
