<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VenueRequest;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VenueController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Venue::class);
        $venues = Venue::visibleTo($request->user())->with('club')->withCount(['tournaments', 'games'])->orderBy('name')->paginate(20);

        return view('admin.venues.index', compact('venues'));
    }

    public function create(): View
    {
        $this->authorize('create', Venue::class);

        return view('admin.venues.form', ['venue' => new Venue]);
    }

    public function store(VenueRequest $request): RedirectResponse
    {
        Venue::create($request->validatedForClub());

        return redirect()->route('admin.venues.index')->with('success', 'Sede creada.');
    }

    public function edit(Venue $venue): View
    {
        $this->authorize('update', $venue);

        return view('admin.venues.form', compact('venue'));
    }

    public function update(VenueRequest $request, Venue $venue): RedirectResponse
    {
        $venue->update($request->validatedForClub());

        return redirect()->route('admin.venues.index')->with('success', 'Sede actualizada.');
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        $this->authorize('delete', $venue);
        if ($venue->games()->exists()) {
            return back()->with('error', 'No se puede eliminar: la sede tiene partidos programados.');
        }
        $venue->delete();

        return back()->with('success', 'Sede eliminada.');
    }
}
