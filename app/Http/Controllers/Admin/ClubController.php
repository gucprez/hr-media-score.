<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClubRequest;
use App\Models\Club;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClubController extends Controller
{
    public function __construct(private ImageService $images) {}

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', Club::class);
        $user = $request->user();

        if (! $user->isSuperAdmin()) {
            return redirect()->route('admin.clubs.edit', $user->club_id);
        }

        $clubs = Club::withCount(['tournaments', 'teams', 'players', 'users'])
            ->when($request->string('q')->trim()->value(), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.clubs.index', compact('clubs'));
    }

    public function create(): View
    {
        $this->authorize('create', Club::class);

        return view('admin.clubs.form', ['club' => new Club(['country' => 'DO', 'is_active' => true])]);
    }

    public function store(ClubRequest $request): RedirectResponse
    {
        $club = Club::create($request->safe()->except(['logo', 'remove_logo']));
        $this->syncLogo($request, $club);

        return redirect()->route('admin.clubs.index')->with('success', "Organización «{$club->name}» creada.");
    }

    public function edit(Club $club): View
    {
        $this->authorize('update', $club);

        return view('admin.clubs.form', compact('club'));
    }

    public function update(ClubRequest $request, Club $club): RedirectResponse
    {
        $data = $request->safe()->except(['logo', 'remove_logo']);
        if (! $request->user()->isSuperAdmin()) {
            unset($data['is_active']); // un admin de club no puede desactivar su propia organización
        }
        $club->update($data);
        $this->syncLogo($request, $club);

        return back()->with('success', 'Organización actualizada.');
    }

    public function destroy(Club $club): RedirectResponse
    {
        $this->authorize('delete', $club);

        if ($club->tournaments()->exists() || $club->users()->exists()) {
            return back()->with('error', 'No se puede eliminar: la organización tiene torneos o usuarios. Desactívela en su lugar.');
        }
        $club->delete();

        return redirect()->route('admin.clubs.index')->with('success', 'Organización eliminada.');
    }

    private function syncLogo(ClubRequest $request, Club $club): void
    {
        if ($request->hasFile('logo')) {
            $club->forceFill(['logo_path' => $this->images->store($request->file('logo'), "clubs/{$club->id}", 600, $club->logo_path)])->save();
        } elseif ($request->boolean('remove_logo') && $club->logo_path) {
            $this->images->delete($club->logo_path);
            $club->forceFill(['logo_path' => null])->save();
        }
    }
}
