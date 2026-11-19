<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TeamRequest;
use App\Models\Club;
use App\Models\Team;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __construct(private ImageService $images) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Team::class);
        $teams = Team::visibleTo($request->user())->with('club')->withCount('registrations')
            ->when($request->string('q')->trim()->value(), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')->paginate(24)->withQueryString();

        return view('admin.teams.index', compact('teams'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Team::class);

        return view('admin.teams.form', ['team' => new Team(['is_active' => true]), 'clubs' => $this->clubs($request)]);
    }

    public function store(TeamRequest $request): RedirectResponse
    {
        $team = Team::create($request->validatedForClub());
        $this->syncLogo($request, $team);

        return redirect()->route('admin.teams.show', $team)->with('success', "Equipo «{$team->name}» creado.");
    }

    public function show(Team $team): View
    {
        $this->authorize('view', $team);
        $team->load(['club', 'registrations' => fn ($q) => $q->with(['tournament', 'activeRoster.player'])->latest()]);

        return view('admin.teams.show', compact('team'));
    }

    public function edit(Request $request, Team $team): View
    {
        $this->authorize('update', $team);

        return view('admin.teams.form', ['team' => $team, 'clubs' => collect()]);
    }

    public function update(TeamRequest $request, Team $team): RedirectResponse
    {
        $team->update($request->validatedForClub());
        $this->syncLogo($request, $team);

        return redirect()->route('admin.teams.show', $team)->with('success', 'Equipo actualizado.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->authorize('delete', $team);
        if ($team->registrations()->exists()) {
            return back()->with('error', 'No se puede eliminar: el equipo tiene inscripciones en torneos. Desactívelo para conservar su historial.');
        }
        $this->images->delete($team->logo_path);
        $team->forceDelete();

        return redirect()->route('admin.teams.index')->with('success', 'Equipo eliminado.');
    }

    private function clubs(Request $request)
    {
        return $request->user()->isSuperAdmin() ? Club::orderBy('name')->get() : collect();
    }

    private function syncLogo(TeamRequest $request, Team $team): void
    {
        if ($request->hasFile('logo')) {
            $team->forceFill(['logo_path' => $this->images->store($request->file('logo'), "clubs/{$team->club_id}/teams", 600, $team->logo_path)])->save();
        } elseif ($request->boolean('remove_logo') && $team->logo_path) {
            $this->images->delete($team->logo_path);
            $team->forceFill(['logo_path' => null])->save();
        }
    }
}
