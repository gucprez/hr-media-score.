@php $max = $rules?->max_roster_size; $maxR = $rules?->max_reinforcements ?? 0; $full = $max && $activeCount >= $max; @endphp
<div>
    <div class="card relative mb-6 overflow-hidden">
        <div class="absolute inset-0 opacity-20" style="background: linear-gradient(120deg, {{ $registration->team->primary_color ?? '#222D41' }}, transparent 55%)"></div>
        <div class="relative flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:p-6">
            <x-team-badge :team="$registration->team" size="xl" />
            <div class="min-w-0 flex-1">
                <p class="eyebrow">{{ $tournament->fullName() }}</p>
                <h1 class="heading mt-1 text-3xl sm:text-4xl">{{ $registration->displayName() }}</h1>
                <p class="mt-1 text-sm text-mute">{{ $registration->coach() ? 'Entrenador: '.$registration->coach() : 'Sin entrenador registrado' }}</p>
            </div>
            <dl class="grid grid-cols-2 gap-3 sm:w-72">
                <div class="rounded-xl bg-ink-900/70 p-3 text-center ring-1 ring-white/5"><dt class="eyebrow text-[10px]">Jugadores</dt><dd @class(['font-score text-3xl', 'text-success' => $full])>{{ $activeCount }}<span class="text-lg text-mute">/{{ $max ?? '∞' }}</span></dd></div>
                <div class="rounded-xl bg-ink-900/70 p-3 text-center ring-1 ring-white/5"><dt class="eyebrow text-[10px]">Refuerzos</dt><dd class="font-score text-3xl">{{ $reinforcements }}<span class="text-lg text-mute">/{{ $maxR }}</span></dd></div>
            </dl>
        </div>
        @if ($max)<div class="h-1 bg-ink-900"><div class="h-full bg-brand transition-all" style="width: {{ min(100, $activeCount / $max * 100) }}%"></div></div>@endif
    </div>

    <div class="grid gap-6 lg:grid-cols-5">
        <section class="lg:col-span-3">
            <x-section-title title="Plantilla" />
            <div class="card overflow-x-auto">
                <table class="table-base">
                    <thead><tr><th class="w-16 text-center">#</th><th>Jugador</th><th class="hidden sm:table-cell">Edad</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($roster as $e)
                            <tr wire:key="e-{{ $e->id }}" @class(['opacity-50' => $e->status === App\Enums\RosterStatus::Released])>
                                @if ($editingId === $e->id)
                                    <td><input type="number" wire:model="editJersey" min="0" max="99" class="input w-16 px-2 py-1.5 text-center" aria-label="Dorsal">@error('editJersey')<p class="error">{{ $message }}</p>@enderror</td>
                                    <td class="font-semibold">{{ $e->player->full_name }}</td>
                                    <td class="hidden sm:table-cell"></td>
                                    <td><select wire:model="editStatus" class="input py-1.5 text-xs" aria-label="Estado">@foreach (App\Enums\RosterStatus::options() as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></td>
                                    <td><div class="flex justify-end gap-1"><button wire:click="saveEdit" class="btn-primary btn-sm">Guardar</button><button wire:click="$set('editingId', null)" class="btn-ghost btn-sm">✕</button></div></td>
                                @else
                                    <td class="text-center font-score text-2xl">{{ $e->jersey_number }}</td>
                                    <td>
                                        <a href="{{ route('admin.players.show', $e->player) }}" class="flex items-center gap-3 hover:text-sport">
                                            <x-player-avatar :player="$e->player" size="sm" />
                                            <span class="min-w-0"><span class="block truncate font-semibold">{{ $e->player->full_name }}</span>
                                                <span class="flex flex-wrap gap-1 text-xs text-mute">{{ $e->position?->label() ?? $e->player->position?->label() }}
                                                    @if ($e->is_reinforcement)<span class="badge-scheduled px-1.5 py-0 text-[9px]">Refuerzo</span>@endif
                                                    @if ($e->is_captain)<span class="badge-live px-1.5 py-0 text-[9px]">C</span>@endif</span></span>
                                        </a>
                                    </td>
                                    <td class="hidden sm:table-cell">{{ $e->player->ageOn($rules?->age_reference_date ?? $tournament->starts_on) ?? '—' }}</td>
                                    <td><span class="{{ $e->status === App\Enums\RosterStatus::Active ? 'badge-success' : 'badge-muted' }}">{{ $e->status->label() }}</span></td>
                                    <td>
                                        <div class="flex justify-end gap-1" x-data="{ open: false }">
                                            <button wire:click="toggleCaptain({{ $e->id }})" class="btn-ghost btn-sm" title="{{ $e->is_captain ? 'Quitar capitanía' : 'Nombrar capitán' }}" aria-label="Capitán"><x-icon name="star" class="size-4 {{ $e->is_captain ? 'fill-current text-sport' : '' }}" /></button>
                                            <button wire:click="edit({{ $e->id }})" class="btn-ghost btn-sm" aria-label="Editar"><x-icon name="pencil" class="size-4" /></button>
                                            <button wire:click="remove({{ $e->id }})" wire:confirm="¿Retirar a {{ $e->player->full_name }} de la plantilla?" class="btn-ghost btn-sm hover:text-brand-400" aria-label="Retirar"><x-icon name="trash" class="size-4" /></button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-12 text-center text-mute">La plantilla está vacía. Agregue jugadores desde el panel.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="lg:col-span-2">
            <x-section-title title="Agregar jugador" />
            <form wire:submit="add" class="card space-y-4 p-5">
                @if ($full)
                    <p class="rounded-xl bg-success/10 px-4 py-3 text-sm text-success">Plantilla completa ({{ $max }} jugadores).</p>
                @endif
                <div>
                    <span class="label">Jugador</span>
                    @if ($selected)
                        <div class="flex items-center gap-3 rounded-xl border border-sport/40 bg-sport/5 p-3">
                            <x-player-avatar :player="$selected" size="sm" />
                            <div class="min-w-0 flex-1"><p class="truncate font-semibold">{{ $selected->full_name }}</p><p class="text-xs text-mute">{{ $selected->ageOn($rules?->age_reference_date ?? $tournament->starts_on) !== null ? $selected->ageOn($rules?->age_reference_date ?? $tournament->starts_on).' años' : 'Sin fecha de nacimiento' }}</p></div>
                            <button type="button" wire:click="clearPlayer" class="btn-ghost btn-sm" aria-label="Cambiar jugador"><x-icon name="x" class="size-4" /></button>
                        </div>
                    @else
                        <div class="relative">
                            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-mute" />
                            <input type="search" wire:model.live.debounce.250ms="search" placeholder="Escriba al menos 2 letras…" class="input pl-9 @error('playerId') input-error @enderror" autocomplete="off" aria-label="Buscar jugador">
                        </div>
                        @if ($results->isNotEmpty())
                            <ul class="mt-2 max-h-64 divide-y divide-white/5 overflow-y-auto rounded-xl border divider bg-ink-900" role="listbox">
                                @foreach ($results as $p)
                                    <li><button type="button" wire:click="selectPlayer({{ $p->id }})" class="flex w-full items-center gap-3 px-3 py-2.5 text-left hover:bg-white/5" role="option">
                                        <x-player-avatar :player="$p" size="sm" /><span class="flex-1"><span class="block text-sm font-semibold">{{ $p->full_name }}</span><span class="text-xs text-mute">{{ $p->position?->label() ?? 'Sin posición' }} · {{ $p->ageOn() !== null ? $p->ageOn().' años' : 'Edad no registrada' }}</span></span>
                                    </button></li>
                                @endforeach
                            </ul>
                        @elseif (mb_strlen(trim($search)) >= 2)
                            <p class="hint">Sin jugadores disponibles con ese nombre. <a href="{{ route('admin.players.create') }}" class="text-sport underline">Registrar jugador</a></p>
                        @endif
                    @endif
                    @error('playerId')<p class="error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label" for="jersey">Dorsal</label>
                        <input id="jersey" type="number" wire:model="jerseyNumber" min="0" max="99" required class="input @error('jerseyNumber') input-error @enderror">
                        @error('jerseyNumber')<p class="error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label" for="pos">Posición</label>
                        <select id="pos" wire:model="position" class="input"><option value="">—</option>@foreach (App\Enums\PlayerPosition::options() as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
                    </div>
                </div>
                @if ($usedNumbers)<p class="hint">Dorsales ocupados: {{ collect($usedNumbers)->sort()->implode(', ') }}</p>@endif
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="isReinforcement" class="rounded border-white/20 bg-ink-900 text-brand focus:ring-sport" @disabled($maxR === 0)> Refuerzo <span class="text-xs text-mute">({{ $reinforcements }}/{{ $maxR }})</span></label>
                    @error('isReinforcement')<p class="error" role="alert">{{ $message }}</p>@enderror
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="isCaptain" class="rounded border-white/20 bg-ink-900 text-brand focus:ring-sport"> Capitán</label>
                </div>
                <button class="btn-primary w-full" wire:loading.attr="disabled" @disabled($full)>
                    <x-icon name="plus" class="size-4" /><span wire:loading.remove wire:target="add">Agregar a la plantilla</span><span wire:loading wire:target="add">Validando reglamento…</span>
                </button>
                @if ($rules)
                    <p class="text-xs text-mute">Reglamento: edad {{ $rules->min_age ?? '—' }}–{{ $rules->max_age ?? '—' }} años{{ $rules->reinforcements_exempt_from_age ? ' (refuerzos exentos)' : '' }} · máx. {{ $rules->max_roster_size }} jugadores · {{ $rules->max_reinforcements }} refuerzos.</p>
                @endif
            </form>
        </aside>
    </div>
</div>
