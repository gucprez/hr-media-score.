<?php

namespace App\Livewire\Admin;

use App\Actions\RegisterTeamInTournament;
use App\Enums\RegistrationStatus;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentTeam;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Inscripción de equipos en un torneo. */
class TournamentRegistrations extends Component
{
    #[Locked]
    public int $tournamentId;

    public ?int $teamId = null;

    public function mount(Tournament $tournament): void
    {
        $this->authorize('manageRosters', $tournament);
        $this->tournamentId = $tournament->id;
    }

    protected function tournament(): Tournament
    {
        $t = Tournament::with('rules')->findOrFail($this->tournamentId);
        $this->authorize('manageRosters', $t);

        return $t;
    }

    public function register(RegisterTeamInTournament $action): void
    {
        $tournament = $this->tournament();
        $this->validate(['teamId' => ['required', 'integer', Rule::exists('teams', 'id')->where('club_id', $tournament->club_id)->whereNull('deleted_at')]],
            ['teamId.required' => 'Seleccione un equipo.']);

        $team = Team::findOrFail($this->teamId);
        try {
            $action->handle($tournament, $team);
        } catch (ValidationException $e) {
            $this->addError('teamId', collect($e->errors())->flatten()->first());

            return;
        }
        $this->reset('teamId');
        $this->dispatch('notify', message: "«{$team->name}» inscrito en el torneo.");
    }

    public function setStatus(int $registrationId, string $status): void
    {
        $tournament = $this->tournament();
        $reg = TournamentTeam::where('tournament_id', $tournament->id)->findOrFail($registrationId);
        $status = RegistrationStatus::tryFrom($status) ?? abort(422);
        $reg->update(['status' => $status]);
    }

    public function unregister(int $registrationId): void
    {
        $tournament = $this->tournament();
        $reg = TournamentTeam::where('tournament_id', $tournament->id)->withCount('roster')->findOrFail($registrationId);

        if ($reg->roster_count > 0 || $tournament->games()->where(fn ($q) => $q->where('home_team_id', $reg->id)->orWhere('away_team_id', $reg->id))->exists()) {
            $this->addError('teamId', 'No se puede retirar la inscripción: el equipo tiene jugadores o partidos. Cambie su estado a «Retirado».');

            return;
        }
        $reg->delete();
        $this->dispatch('notify', message: 'Inscripción eliminada.');
    }

    public function render()
    {
        $tournament = $this->tournament();
        $registrations = $tournament->registrations()->with('team')->withCount('activeRoster')
            ->withCount(['activeRoster as reinforcements_count' => fn ($q) => $q->where('is_reinforcement', true)])
            ->get()->sortBy(fn ($r) => $r->team->name);

        $available = Team::where('club_id', $tournament->club_id)->where('is_active', true)
            ->whereNotIn('id', $registrations->pluck('team_id'))->orderBy('name')->get();

        return view('livewire.admin.tournament-registrations', compact('tournament', 'registrations', 'available'));
    }
}
