<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PlayerRequest;
use App\Models\Club;
use App\Models\Player;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlayerController extends Controller
{
    public function __construct(private ImageService $images) {}

    public function index(): View
    {
        $this->authorize('viewAny', Player::class);

        return view('admin.players.index'); // listado con búsqueda en vivo (Livewire)
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Player::class);

        return view('admin.players.form', [
            'player' => new Player(['is_active' => true]),
            'clubs' => $request->user()->isSuperAdmin() ? Club::orderBy('name')->get() : collect(),
        ]);
    }

    public function store(PlayerRequest $request): RedirectResponse
    {
        $player = Player::create($request->validatedForClub());
        $this->syncPhoto($request, $player);

        return redirect()->route('admin.players.show', $player)->with('success', "Jugador «{$player->full_name}» registrado.");
    }

    public function show(Player $player): View
    {
        $this->authorize('view', $player);
        $player->load(['club', 'rosterEntries' => fn ($q) => $q->with(['tournament', 'tournamentTeam.team'])->latest()]);

        return view('admin.players.show', compact('player'));
    }

    public function edit(Player $player): View
    {
        $this->authorize('update', $player);

        return view('admin.players.form', ['player' => $player, 'clubs' => collect()]);
    }

    public function update(PlayerRequest $request, Player $player): RedirectResponse
    {
        $player->update($request->validatedForClub());
        $this->syncPhoto($request, $player);

        return redirect()->route('admin.players.show', $player)->with('success', 'Jugador actualizado.');
    }

    public function destroy(Player $player): RedirectResponse
    {
        $this->authorize('delete', $player);
        if ($player->rosterEntries()->exists() || $player->gameStats()->exists()) {
            return back()->with('error', 'No se puede eliminar: el jugador tiene historial deportivo. Márquelo como inactivo.');
        }
        $this->images->delete($player->photo_path);
        $player->forceDelete();

        return redirect()->route('admin.players.index')->with('success', 'Jugador eliminado.');
    }

    private function syncPhoto(PlayerRequest $request, Player $player): void
    {
        if ($request->hasFile('photo')) {
            $player->forceFill(['photo_path' => $this->images->store($request->file('photo'), "clubs/{$player->club_id}/players", 600, $player->photo_path)])->save();
        } elseif ($request->boolean('remove_photo') && $player->photo_path) {
            $this->images->delete($player->photo_path);
            $player->forceFill(['photo_path' => null])->save();
        }
    }
}
