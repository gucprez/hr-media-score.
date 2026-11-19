<?php

namespace App\Livewire\Admin;

use App\Actions\AssignPlayerToRoster;
use App\Actions\RemovePlayerFromRoster;
use App\Enums\PlayerPosition;
use App\Enums\RosterStatus;
use App\Models\Player;
use App\Models\TeamRoster;
use App\Models\TournamentTeam;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Gestión de la plantilla de un equipo inscrito en un torneo. */
class RosterManager extends Component
{
    #[Locked]
    public int $registrationId;

    public string $search = '';

    public ?int $playerId = null;

    public ?int $jerseyNumber = null;

    public string $position = '';

    public bool $isReinforcement = false;

    public bool $isCaptain = false;

    public ?int $editingId = null;

    public ?int $editJersey = null;

    public string $editStatus = 'active';

    public function mount(TournamentTeam $registration): void
    {
        $this->authorize('manageRosters', $registration->tournament);
        $this->registrationId = $registration->id;
    }

    protected function registration(): TournamentTeam
    {
        $reg = TournamentTeam::with(['tournament.rules', 'team'])->findOrFail($this->registrationId);
        $this->authorize('manageRosters', $reg->tournament);

        return $reg;
    }

    public function selectPlayer(int $id): void
    {
        $reg = $this->registration();
        $player = Player::where('club_id', $reg->tournament->club_id)->findOrFail($id);
        $this->playerId = $player->id;
        $this->position = $player->position?->value ?? '';
        $this->search = '';
    }

    public function clearPlayer(): void
    {
        $this->reset('playerId', 'position');
    }

    public function add(AssignPlayerToRoster $action): void
    {
        $reg = $this->registration();
        $this->validate([
            'playerId' => ['required', 'integer', Rule::exists('players', 'id')->where('club_id', $reg->tournament->club_id)->whereNull('deleted_at')],
            'jerseyNumber' => ['required', 'integer', 'min:0', 'max:99'],
            'position' => ['nullable', Rule::enum(PlayerPosition::class)],
        ], [
            'playerId.required' => 'Seleccione un jugador.',
            'jerseyNumber.required' => 'Indique el dorsal.',
        ]);

        try {
            $entry = $action->handle($reg, Player::findOrFail($this->playerId), [
                'jersey_number' => $this->jerseyNumber,
                'position' => $this->position ?: null,
                'is_reinforcement' => $this->isReinforcement,
                'is_captain' => $this->isCaptain,
            ]);
        } catch (ValidationException $e) {
            // La Action usa nombres de dominio; se traducen a las propiedades del componente.
            $map = ['player_id' => 'playerId', 'jersey_number' => 'jerseyNumber', 'is_reinforcement' => 'isReinforcement'];
            foreach ($e->errors() as $key => $messages) {
                $this->addError($map[$key] ?? $key, $messages[0]);
            }

            return;
        }

        $this->reset('playerId', 'jerseyNumber', 'position', 'isReinforcement', 'isCaptain', 'search');
        $this->dispatch('notify', message: "{$entry->player->full_name} agregado a la plantilla.");
    }

    public function edit(int $entryId): void
    {
        $entry = $this->entry($entryId);
        $this->editingId = $entry->id;
        $this->editJersey = $entry->jersey_number;
        $this->editStatus = $entry->status->value;
    }

    public function saveEdit(): void
    {
        $entry = $this->entry($this->editingId ?? 0);
        $this->validate([
            'editJersey' => ['required', 'integer', 'min:0', 'max:99',
                Rule::unique('team_rosters', 'jersey_number')->where('tournament_team_id', $entry->tournament_team_id)->ignore($entry->id)],
            'editStatus' => ['required', Rule::enum(RosterStatus::class)],
        ], ['editJersey.unique' => 'Ese dorsal ya está asignado en este equipo.']);

        $entry->update(['jersey_number' => $this->editJersey, 'status' => $this->editStatus,
            'left_on' => $this->editStatus === RosterStatus::Released->value ? now()->toDateString() : null]);
        $this->reset('editingId', 'editJersey', 'editStatus');
    }

    public function toggleCaptain(int $entryId): void
    {
        $entry = $this->entry($entryId);
        TeamRoster::where('tournament_team_id', $entry->tournament_team_id)->whereKeyNot($entry->id)->update(['is_captain' => false]);
        $entry->update(['is_captain' => ! $entry->is_captain]);
    }

    public function remove(int $entryId, RemovePlayerFromRoster $action): void
    {
        $result = $action->handle($this->entry($entryId));
        $this->dispatch('notify', message: $result === 'released' ? 'Jugador dado de baja (se conserva su historial).' : 'Jugador retirado de la plantilla.');
    }

    protected function entry(int $id): TeamRoster
    {
        return TeamRoster::where('tournament_team_id', $this->registration()->id)->findOrFail($id);
    }

    public function render()
    {
        $reg = $this->registration();
        $tournament = $reg->tournament;
        $roster = $reg->roster()->with('player')->get();

        $results = collect();
        if (! $this->playerId && mb_strlen(trim($this->search)) >= 2) {
            $taken = TeamRoster::where('tournament_id', $tournament->id)->pluck('player_id');
            $s = trim($this->search);
            $results = Player::where('club_id', $tournament->club_id)->where('is_active', true)
                ->whereNotIn('id', $taken)
                ->where(fn ($w) => $w->whereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$s}%"])
                    ->orWhere('document_number', 'like', "%{$s}%"))
                ->orderBy('last_name')->take(8)->get();
        }

        return view('livewire.admin.roster-manager', [
            'registration' => $reg,
            'tournament' => $tournament,
            'rules' => $tournament->rules,
            'roster' => $roster,
            'activeCount' => $roster->where('status', '!=', RosterStatus::Released)->count(),
            'reinforcements' => $roster->where('status', '!=', RosterStatus::Released)->where('is_reinforcement', true)->count(),
            'results' => $results,
            'selected' => $this->playerId ? Player::find($this->playerId) : null,
            'usedNumbers' => $roster->pluck('jersey_number')->all(),
        ]);
    }
}
