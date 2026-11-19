<?php

namespace App\Livewire\Admin;

use App\Enums\PlayerPosition;
use App\Models\Player;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PlayersTable extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $position = '';

    #[Url(except: 'active')]
    public string $status = 'active';

    public function mount(): void
    {
        $this->authorize('viewAny', Player::class);
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'position', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $user = auth()->user();
        $players = Player::visibleTo($user)
            ->with(['club', 'rosterEntries' => fn ($q) => $q->latest()->with('tournamentTeam.team')])
            ->when(trim($this->search), function ($q, $s) {
                $q->where(fn ($w) => $w->whereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$s}%"])
                    ->orWhere('nickname', 'like', "%{$s}%")->orWhere('document_number', 'like', "%{$s}%"));
            })
            ->when($this->position && PlayerPosition::tryFrom($this->position), fn ($q) => $q->where('position', $this->position))
            ->when($this->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('last_name')->orderBy('first_name')
            ->paginate(20);

        return view('livewire.admin.players-table', compact('players'));
    }
}
