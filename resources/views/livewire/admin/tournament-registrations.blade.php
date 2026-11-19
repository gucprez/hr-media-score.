<div class="grid gap-6 lg:grid-cols-3">
    <section class="space-y-3 lg:col-span-2">
        @forelse ($registrations as $reg)
            <div wire:key="reg-{{ $reg->id }}" class="card flex flex-col gap-4 p-4 sm:flex-row sm:items-center">
                <x-team-badge :team="$reg->team" size="lg" />
                <div class="min-w-0 flex-1">
                    <p class="truncate font-display text-xl font-bold uppercase">{{ $reg->displayName() }}</p>
                    <p class="text-sm text-mute">{{ $reg->active_roster_count }}/{{ $tournament->rules?->max_roster_size ?? '—' }} jugadores · {{ $reg->reinforcements_count }}/{{ $tournament->rules?->max_reinforcements ?? 0 }} refuerzos</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <select wire:change="setStatus({{ $reg->id }}, $event.target.value)" class="input w-auto py-1.5 text-xs" aria-label="Estado de inscripción">
                        @foreach (App\Enums\RegistrationStatus::options() as $v => $l)<option value="{{ $v }}" @selected($reg->status->value === $v)>{{ $l }}</option>@endforeach
                    </select>
                    <a href="{{ route('admin.tournaments.roster', [$tournament, $reg]) }}" class="btn-primary btn-sm"><x-icon name="users" class="size-4" />Plantilla</a>
                    <button type="button" wire:click="unregister({{ $reg->id }})" wire:confirm="¿Eliminar la inscripción de {{ $reg->team->name }}?" class="btn-ghost btn-sm" aria-label="Eliminar inscripción"><x-icon name="trash" class="size-4" /></button>
                </div>
            </div>
        @empty
            <x-empty-state icon="shield" title="Sin equipos inscritos">Use el formulario para inscribir los equipos participantes.</x-empty-state>
        @endforelse
    </section>
    <aside class="card h-fit p-5 sm:p-6">
        <h2 class="heading mb-4 text-lg">Inscribir equipo</h2>
        <form wire:submit="register" class="space-y-4">
            <div>
                <label class="label" for="teamId">Equipo</label>
                <select id="teamId" wire:model="teamId" class="input @error('teamId') input-error @enderror" required>
                    <option value="">Seleccione…</option>
                    @foreach ($available as $team)<option value="{{ $team->id }}">{{ $team->name }}</option>@endforeach
                </select>
                @error('teamId')<p class="error" role="alert">{{ $message }}</p>@enderror
                @if ($available->isEmpty())<p class="hint">Todos los equipos activos ya están inscritos. <a class="text-sport underline" href="{{ route('admin.teams.create') }}">Crear equipo</a></p>@endif
            </div>
            <button class="btn-primary w-full" wire:loading.attr="disabled" @disabled($available->isEmpty())>
                <span wire:loading.remove wire:target="register">Inscribir</span><span wire:loading wire:target="register">Inscribiendo…</span>
            </button>
        </form>
    </aside>
</div>
