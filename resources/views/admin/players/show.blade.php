<x-layouts.admin :title="$player->full_name" :breadcrumbs="['Jugadores' => route('admin.players.index'), $player->full_name => null]">
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card h-fit p-6 text-center">
            <x-player-avatar :player="$player" size="xl" class="mx-auto" />
            <h1 class="heading mt-4 text-3xl">{{ $player->full_name }}</h1>
            @if ($player->nickname)<p class="text-mute">«{{ $player->nickname }}»</p>@endif
            <div class="mt-3 flex justify-center gap-2">
                @if ($player->position)<span class="badge-scheduled">{{ $player->position->label() }}</span>@endif
                <span class="{{ $player->is_active ? 'badge-success' : 'badge-live' }}">{{ $player->is_active ? 'Activo' : 'Inactivo' }}</span>
            </div>
            <dl class="mt-6 space-y-3 text-left text-sm">
                @foreach ([
                    'Edad' => $player->birth_date ? $player->ageOn().' años ('.$player->birth_date->format('d/m/Y').')' : '—',
                    'Documento' => $player->document_number ?? '—',
                    'Estatura' => $player->height_cm ? $player->height_cm.' cm' : '—',
                    'Peso' => $player->weight_kg ? $player->weight_kg.' kg' : '—',
                    'Mano hábil' => ['right' => 'Derecha', 'left' => 'Izquierda'][$player->dominant_hand] ?? '—',
                    'Tutor' => trim(($player->guardian_name ?? '').' '.($player->guardian_phone ?? '')) ?: '—',
                ] as $k => $v)
                    <div class="flex justify-between gap-3 border-b divider pb-3 last:border-0"><dt class="text-mute">{{ $k }}</dt><dd class="text-right font-medium">{{ $v }}</dd></div>
                @endforeach
            </dl>
            @if ($player->notes)<p class="mt-4 rounded-xl bg-white/5 p-3 text-left text-sm text-mute">{{ $player->notes }}</p>@endif
            <div class="mt-6 flex justify-center gap-2">
                @can('update', $player)<a href="{{ route('admin.players.edit', $player) }}" class="btn-secondary"><x-icon name="pencil" class="size-4" />Editar</a>@endcan
                @can('delete', $player)<x-confirm-delete :action="route('admin.players.destroy', $player)" message="¿Eliminar definitivamente este jugador?" />@endcan
            </div>
        </div>
        <section class="lg:col-span-2">
            <x-section-title title="Historial deportivo" />
            @forelse ($player->rosterEntries as $e)
                <div class="card mb-3 flex items-center gap-4 p-4">
                    <x-team-badge :team="$e->tournamentTeam->team" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">{{ $e->tournamentTeam->displayName() }}</p>
                        <a href="{{ route('admin.tournaments.show', $e->tournament) }}" class="truncate text-sm text-mute hover:text-white">{{ $e->tournament->fullName() }}</a>
                    </div>
                    <div class="text-right">
                        <p class="font-score text-3xl">#{{ $e->jersey_number }}</p>
                        <p class="text-xs text-mute">{{ $e->status->label() }}{{ $e->is_reinforcement ? ' · Refuerzo' : '' }}{{ $e->is_captain ? ' · Capitán' : '' }}</p>
                    </div>
                </div>
            @empty
                <x-empty-state icon="history" title="Sin historial">El jugador aún no figura en ninguna plantilla.</x-empty-state>
            @endforelse
        </section>
    </div>
</x-layouts.admin>
