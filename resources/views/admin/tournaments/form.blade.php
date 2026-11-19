@php
    $editing = $tournament->exists;
    $r = $tournament->rules;
    $title = $editing ? 'Editar torneo' : 'Nuevo torneo';
@endphp
<x-layouts.admin :title="$title" :header="$title" :breadcrumbs="['Torneos' => route('admin.tournaments.index')] + ($editing ? [$tournament->fullName() => route('admin.tournaments.show', $tournament)] : []) + [($editing ? 'Editar' : 'Nuevo') => null]">
    <form method="POST" action="{{ $editing ? route('admin.tournaments.update', $tournament) : route('admin.tournaments.store') }}" enctype="multipart/form-data"
          x-data="{ tab: '{{ collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'rules.')) ? 'rules' : 'general' }}', saving: false,
            check(e) { if (this.$el.checkValidity()) { this.saving = true; return; }
                e.preventDefault(); const bad = this.$el.querySelector(':invalid'); this.tab = bad.closest('[data-tab]')?.dataset.tab ?? this.tab;
                this.$nextTick(() => bad.reportValidity()); } }" @submit="check($event)" novalidate>
        @csrf @if ($editing) @method('PUT') @endif

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-brand/30 bg-brand/10 px-4 py-3 text-sm" role="alert"><b>Revise el formulario:</b> hay {{ $errors->count() }} campo(s) con errores.</div>
        @endif

        <div class="mb-5 flex gap-1 overflow-x-auto rounded-xl bg-ink-900 p-1 scrollbar-none" role="tablist">
            <button type="button" role="tab" @click="tab = 'general'" :class="tab === 'general' ? 'bg-ink-700 text-white' : 'text-mute'" class="btn btn-sm flex-1">Datos generales</button>
            <button type="button" role="tab" @click="tab = 'rules'" :class="tab === 'rules' ? 'bg-ink-700 text-white' : 'text-mute'" class="btn btn-sm flex-1">Reglamento</button>
        </div>

        <div x-show="tab === 'general'" data-tab="general" class="grid gap-6 lg:grid-cols-3">
            <div class="card space-y-5 p-5 sm:p-6 lg:col-span-2">
                @if ($clubs->isNotEmpty())
                    <x-form.select name="club_id" label="Organización" :options="$clubs->pluck('name', 'id')" :value="$tournament->club_id" placeholder="Seleccione…" required />
                @endif
                <div class="grid gap-5 sm:grid-cols-4">
                    <x-form.input name="edition" label="Edición" :value="$tournament->edition" maxlength="20" placeholder="XIV" />
                    <x-form.input name="name" label="Nombre del torneo" :value="$tournament->name" required maxlength="180" class="sm:col-span-3" />
                </div>
                <x-form.input name="motto" label="Lema" :value="$tournament->motto" maxlength="180" />
                <div class="grid gap-5 sm:grid-cols-3">
                    <x-form.input name="category" label="Categoría" :value="$tournament->category" maxlength="80" placeholder="Juvenil Infantil" />
                    <x-form.select name="gender" label="Rama" :options="App\Enums\TournamentGender::options()" :value="$tournament->gender" required />
                    <x-form.select name="status" label="Estado" :options="App\Enums\TournamentStatus::options()" :value="$tournament->status" required />
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.select name="season_id" label="Temporada" :options="$seasons->pluck('name', 'id')" :value="$tournament->season_id" placeholder="Sin temporada" />
                    <x-form.select name="venue_id" label="Sede principal" :options="$venues->pluck('name', 'id')" :value="$tournament->venue_id" placeholder="Sin sede" />
                    <x-form.input type="date" name="starts_on" label="Fecha de inicio" :value="$tournament->starts_on?->toDateString()" />
                    <x-form.input type="date" name="ends_on" label="Fecha de cierre" :value="$tournament->ends_on?->toDateString()" />
                </div>
                <x-form.textarea name="description" label="Descripción" :value="$tournament->description" maxlength="5000" />
            </div>
            <div class="space-y-6">
                <div class="card space-y-5 p-5 sm:p-6">
                    <x-form.image name="logo" label="Logo del torneo" :current="$tournament->logoUrl()" remove-name="remove_logo" />
                </div>
                <div class="card space-y-4 p-5 sm:p-6">
                    <x-form.checkbox name="is_public" label="Visible en el portal" :checked="$tournament->is_public" hint="Los torneos en borrador nunca se publican." />
                    <x-form.checkbox name="is_featured" label="Torneo destacado" :checked="$tournament->is_featured" hint="Aparece en la portada. Solo uno por organización." />
                </div>
            </div>
        </div>

        <div x-show="tab === 'rules'" data-tab="rules" x-cloak class="space-y-6">
            <div class="rounded-xl border border-sport/20 bg-sport/5 px-4 py-3 text-sm text-mute"><x-icon name="info" class="mr-1 inline size-4 text-sport" />Las reglas son propias de este torneo y se aplican automáticamente al inscribir jugadores.</div>
            <section class="card p-5 sm:p-6">
                <h2 class="heading mb-4 text-lg">Elegibilidad y plantillas</h2>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <x-form.input type="number" name="rules[min_age]" label="Edad mínima" :value="$r?->min_age" min="4" max="99" />
                    <x-form.input type="number" name="rules[max_age]" label="Edad máxima" :value="$r?->max_age" min="4" max="99" />
                    <x-form.input type="date" name="rules[age_reference_date]" label="Fecha de corte de edad" :value="$r?->age_reference_date?->toDateString()" hint="Si se omite: fecha de inicio del torneo." />
                    <x-form.input type="number" name="rules[min_minutes_per_player]" label="Minutos mínimos por jugador" :value="$r?->min_minutes_per_player" min="0" max="60" />
                    <x-form.input type="number" name="rules[max_roster_size]" label="Máx. jugadores por equipo" :value="$r?->max_roster_size" min="5" max="30" required />
                    <x-form.input type="number" name="rules[max_reinforcements]" label="Refuerzos por equipo" :value="$r?->max_reinforcements" min="0" max="30" required />
                    <x-form.input type="number" name="rules[reinforcement_max_age]" label="Edad máx. de refuerzos" :value="$r?->reinforcement_max_age" min="4" max="99" hint="Vacío = sin límite." />
                    <x-form.checkbox name="rules[reinforcements_exempt_from_age]" label="Refuerzos exentos del rango de edad" :checked="$r?->reinforcements_exempt_from_age" class="self-center" />
                </div>
            </section>
            <section class="card p-5 sm:p-6">
                <h2 class="heading mb-4 text-lg">Formato de competición</h2>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <x-form.select name="rules[regular_season_format]" label="Serie regular" :options="['round_robin' => 'Todos contra todos', 'groups' => 'Por grupos', 'none' => 'Sin serie regular']" :value="$r?->regular_season_format" required />
                    <x-form.input type="number" name="rules[round_robin_legs]" label="Vueltas" :value="$r?->round_robin_legs" min="1" max="4" required />
                    <x-form.input type="number" name="rules[games_per_team]" label="Partidos por equipo" :value="$r?->games_per_team" min="0" />
                    <x-form.input type="number" name="rules[teams_qualifying]" label="Equipos que clasifican" :value="$r?->teams_qualifying" min="0" />
                    <x-form.input type="number" name="rules[direct_final_seeds]" label="Pases directos a la final" :value="$r?->direct_final_seeds" min="0" max="2" required />
                    <x-form.select name="rules[semifinal_best_of]" label="Semifinal al mejor de" :options="[1 => '1 partido', 3 => '3 partidos', 5 => '5 partidos', 7 => '7 partidos']" :value="$r?->semifinal_best_of" placeholder="Sin semifinal" />
                    <x-form.select name="rules[final_best_of]" label="Final al mejor de" :options="[1 => '1 partido', 3 => '3 partidos', 5 => '5 partidos', 7 => '7 partidos']" :value="$r?->final_best_of" placeholder="Sin final" />
                </div>
            </section>
            <section class="card p-5 sm:p-6">
                <h2 class="heading mb-4 text-lg">Juego y puntuación</h2>
                <div class="grid gap-5 sm:grid-cols-3 lg:grid-cols-5">
                    <x-form.input type="number" name="rules[periods]" label="Períodos" :value="$r?->periods" min="1" max="4" required />
                    <x-form.input type="number" name="rules[period_minutes]" label="Minutos por período" :value="$r?->period_minutes" min="1" max="30" required />
                    <x-form.input type="number" name="rules[overtime_minutes]" label="Minutos de prórroga" :value="$r?->overtime_minutes" min="1" max="15" required />
                    <x-form.input type="number" name="rules[personal_foul_limit]" label="Faltas para eliminación" :value="$r?->personal_foul_limit" min="1" max="10" required />
                    <x-form.input type="number" name="rules[technical_foul_ejection]" label="Técnicas para expulsión" :value="$r?->technical_foul_ejection" min="1" max="5" required />
                    <x-form.input type="number" name="rules[points_per_win]" label="Puntos por victoria" :value="$r?->points_per_win" min="0" max="5" required />
                    <x-form.input type="number" name="rules[points_per_loss]" label="Puntos por derrota" :value="$r?->points_per_loss" min="0" max="5" required />
                    <x-form.input type="number" name="rules[points_per_forfeit]" label="Puntos por forfeit" :value="$r?->points_per_forfeit" min="0" max="5" required />
                    <x-form.input type="number" name="rules[min_players_to_start]" label="Mín. jugadores para iniciar" :value="$r?->min_players_to_start" min="1" max="5" required />
                    <x-form.input type="number" name="rules[tolerance_minutes]" label="Tolerancia (min)" :value="$r?->tolerance_minutes" min="0" max="120" />
                </div>
            </section>
            <section class="card grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                <h2 class="heading text-lg lg:col-span-2">Disciplina y reglamento</h2>
                <x-form.textarea name="rules[sanctions_policy]" label="Sanciones (técnicas, expulsiones, agresiones)" :value="$r?->sanctions_policy" rows="5" />
                <x-form.textarea name="rules[punctuality_policy]" label="Puntualidad y disponibilidad" :value="$r?->punctuality_policy" rows="5" />
                <x-form.textarea name="rules[additional_rules]" label="Reglas adicionales" :value="$r?->additional_rules" rows="6" class="lg:col-span-2" />
            </section>
        </div>

        <div class="sticky bottom-0 -mx-4 mt-6 flex items-center justify-end gap-2 border-t divider bg-ink-950/90 px-4 py-4 backdrop-blur sm:mx-0 sm:rounded-xl sm:border">
            <a href="{{ $editing ? route('admin.tournaments.show', $tournament) : route('admin.tournaments.index') }}" class="btn-ghost">Cancelar</a>
            <button class="btn-primary" :disabled="saving"><x-icon name="check" class="size-4" /><span x-text="saving ? 'Guardando…' : 'Guardar torneo'">Guardar torneo</span></button>
        </div>
    </form>
</x-layouts.admin>
