<div>
    <div class="mb-5 flex flex-col gap-2 sm:flex-row">
        <label class="relative flex-1"><span class="sr-only">Buscar jugador</span>
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-mute" />
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre, apodo o documento…" class="input pl-9">
        </label>
        <select wire:model.live="position" class="input sm:w-44" aria-label="Posición">
            <option value="">Todas las posiciones</option>
            @foreach (App\Enums\PlayerPosition::options() as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
        </select>
        <select wire:model.live="status" class="input sm:w-36" aria-label="Estado">
            <option value="active">Activos</option><option value="inactive">Inactivos</option><option value="all">Todos</option>
        </select>
    </div>
    <div class="card relative overflow-x-auto">
        <div wire:loading.delay class="absolute inset-x-0 top-0 h-0.5 overflow-hidden"><div class="h-full w-1/3 animate-pulse bg-sport"></div></div>
        <table class="table-base">
            <thead><tr><th>Jugador</th><th class="hidden sm:table-cell">Posición</th><th class="hidden md:table-cell">Edad</th><th>Equipo actual</th><th></th></tr></thead>
            <tbody>
                @forelse ($players as $p)
                    @php $last = $p->rosterEntries->first(); @endphp
                    <tr wire:key="p-{{ $p->id }}">
                        <td><a href="{{ route('admin.players.show', $p) }}" class="flex items-center gap-3 hover:text-sport"><x-player-avatar :player="$p" size="sm" /><span><span class="block font-semibold">{{ $p->full_name }}</span>@if($p->nickname)<span class="text-xs text-mute">«{{ $p->nickname }}»</span>@endif</span></a></td>
                        <td class="hidden text-mute sm:table-cell">{{ $p->position?->label() ?? '—' }}</td>
                        <td class="hidden md:table-cell">{{ $p->ageOn() ?? '—' }}</td>
                        <td>@if ($last)<span class="flex items-center gap-2"><x-team-badge :team="$last->tournamentTeam->team" size="xs" /><span class="truncate text-sm">{{ $last->tournamentTeam->team->name }} <b class="font-score">#{{ $last->jersey_number }}</b></span></span>@else<span class="text-mute">Libre</span>@endif</td>
                        <td class="text-right"><a href="{{ route('admin.players.edit', $p) }}" class="btn-ghost btn-sm" aria-label="Editar {{ $p->full_name }}"><x-icon name="pencil" class="size-4" /></a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-12 text-center text-mute">{{ $search ? 'Ningún jugador coincide con «'.$search.'».' : 'No hay jugadores registrados.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5"><x-livewire-pagination :paginator="$players" /></div>
</div>
