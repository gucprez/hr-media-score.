<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SeasonRequest;
use App\Models\Season;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SeasonController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Season::class);
        $seasons = Season::visibleTo($request->user())->with('club')->withCount('tournaments')
            ->orderByDesc('is_current')->orderByDesc('starts_on')->orderByDesc('id')->paginate(20);

        return view('admin.seasons.index', compact('seasons'));
    }

    public function create(): View
    {
        $this->authorize('create', Season::class);

        return view('admin.seasons.form', ['season' => new Season]);
    }

    public function store(SeasonRequest $request): RedirectResponse
    {
        $this->save(new Season, $request->validatedForClub());

        return redirect()->route('admin.seasons.index')->with('success', 'Temporada creada.');
    }

    public function edit(Season $season): View
    {
        $this->authorize('update', $season);

        return view('admin.seasons.form', compact('season'));
    }

    public function update(SeasonRequest $request, Season $season): RedirectResponse
    {
        $this->save($season, $request->validatedForClub());

        return redirect()->route('admin.seasons.index')->with('success', 'Temporada actualizada.');
    }

    public function destroy(Season $season): RedirectResponse
    {
        $this->authorize('delete', $season);
        if ($season->tournaments()->withTrashed()->exists()) {
            return back()->with('error', 'No se puede eliminar: la temporada tiene torneos asociados.');
        }
        $season->delete();

        return back()->with('success', 'Temporada eliminada.');
    }

    private function save(Season $season, array $data): void
    {
        DB::transaction(function () use ($season, $data) {
            $season->fill($data)->save();
            if ($season->is_current) {
                Season::where('club_id', $season->club_id)->whereKeyNot($season->id)->update(['is_current' => false]);
            }
        });
    }
}
