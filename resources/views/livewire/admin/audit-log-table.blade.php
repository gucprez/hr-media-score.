<div>
    <div class="mb-5 flex flex-col gap-2 sm:flex-row">
        <select wire:model.live="event" class="input sm:w-56" aria-label="Evento">
            <option value="">Todos los eventos</option>
            @foreach (['created' => 'Creación', 'updated' => 'Modificación', 'deleted' => 'Eliminación', 'login' => 'Inicio de sesión', 'logout' => 'Cierre de sesión', 'login_failed' => 'Intento fallido', 'password_reset' => 'Restablecer contraseña'] as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
        </select>
        <select wire:model.live="type" class="input sm:w-56" aria-label="Tipo de registro">
            <option value="">Todos los registros</option>
            @foreach (['Tournament', 'TournamentRule', 'Team', 'Player', 'TournamentTeam', 'TeamRoster', 'User', 'Club', 'Venue', 'Season'] as $t)<option value="{{ $t }}">{{ __('models.'.$t) }}</option>@endforeach
        </select>
    </div>
    <div class="card overflow-x-auto">
        <table class="table-base">
            <thead><tr><th>Fecha</th><th>Usuario</th><th>Evento</th><th>Registro</th><th class="hidden md:table-cell">IP</th><th></th></tr></thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr wire:key="log-{{ $log->id }}">
                        <td class="whitespace-nowrap text-mute">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $log->user?->name ?? 'Sistema' }}@if(auth()->user()->isSuperAdmin() && $log->club)<span class="block text-xs text-mute">{{ $log->club->name }}</span>@endif</td>
                        <td><span @class(['badge', 'bg-success/15 text-success' => $log->event === 'created', 'bg-sport/15 text-sport' => $log->event === 'updated', 'bg-brand/15 text-brand-400' => in_array($log->event, ['deleted', 'login_failed']), 'bg-white/5 text-mute' => ! in_array($log->event, ['created', 'updated', 'deleted', 'login_failed'])])>{{ $log->eventLabel() }}</span></td>
                        <td><span class="text-mute">{{ $log->subjectLabel() }}</span> {{ $log->description }}</td>
                        <td class="hidden text-xs text-mute md:table-cell">{{ $log->ip_address }}</td>
                        <td class="text-right">@if ($log->old_values || $log->new_values)<button wire:click="toggle({{ $log->id }})" class="btn-ghost btn-sm">{{ $expanded === $log->id ? 'Ocultar' : 'Detalle' }}</button>@endif</td>
                    </tr>
                    @if ($expanded === $log->id)
                        <tr wire:key="log-d-{{ $log->id }}"><td colspan="6" class="bg-ink-900">
                            <div class="grid gap-2 text-xs">
                                @foreach (collect($log->old_values ?? [])->keys()->merge(array_keys($log->new_values ?? []))->unique() as $field)
                                    <div class="grid grid-cols-3 gap-3 font-mono"><span class="text-mute">{{ $field }}</span><span class="truncate text-brand-400 line-through decoration-brand/50">{{ is_array($v = $log->old_values[$field] ?? null) ? json_encode($v) : $v }}</span><span class="truncate text-success">{{ is_array($v = $log->new_values[$field] ?? null) ? json_encode($v) : $v }}</span></div>
                                @endforeach
                            </div>
                        </td></tr>
                    @endif
                @empty
                    <tr><td colspan="6" class="py-12 text-center text-mute">Sin registros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5"><x-livewire-pagination :paginator="$logs" /></div>
</div>
