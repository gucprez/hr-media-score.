<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogTable extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $event = '';

    #[Url(except: '')]
    public string $type = '';

    public ?int $expanded = null;

    public function mount(): void
    {
        $this->authorize('viewAny', AuditLog::class);
    }

    public function updating($name): void
    {
        if (in_array($name, ['event', 'type'], true)) {
            $this->resetPage();
        }
    }

    public function toggle(int $id): void
    {
        $this->expanded = $this->expanded === $id ? null : $id;
    }

    public function render()
    {
        $this->authorize('viewAny', AuditLog::class);
        $user = auth()->user();

        $logs = AuditLog::with(['user', 'club'])
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('club_id', $user->club_id))
            ->when($this->event, fn ($q) => $q->where('event', $this->event))
            ->when($this->type, fn ($q) => $q->where('auditable_type', 'App\\Models\\'.preg_replace('/[^A-Za-z]/', '', $this->type)))
            ->latest('id')->paginate(30);

        return view('livewire.admin.audit-log-table', compact('logs'));
    }
}
