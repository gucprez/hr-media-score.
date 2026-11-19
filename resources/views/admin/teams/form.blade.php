@php $editing = $team->exists; $title = $editing ? 'Editar equipo' : 'Nuevo equipo'; $sec = old('secondary_colors', $team->secondary_colors ?? []); @endphp
<x-layouts.admin :title="$title" :header="$title" :breadcrumbs="['Equipos' => route('admin.teams.index')] + ($editing ? [$team->name => route('admin.teams.show', $team)] : []) + [($editing ? 'Editar' : 'Nuevo') => null]">
    <form method="POST" action="{{ $editing ? route('admin.teams.update', $team) : route('admin.teams.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3" x-data="{ saving: false }" @submit="saving = true">
        @csrf @if ($editing) @method('PUT') @endif
        <div class="card space-y-5 p-5 sm:p-6 lg:col-span-2">
            @if ($clubs->isNotEmpty())<x-form.select name="club_id" label="Organización" :options="$clubs->pluck('name', 'id')" :value="$team->club_id" placeholder="Seleccione…" required />@endif
            <div class="grid gap-5 sm:grid-cols-3">
                <x-form.input name="name" label="Nombre" :value="$team->name" required maxlength="120" class="sm:col-span-2" />
                <x-form.input name="short_name" label="Abreviatura" :value="$team->short_name" maxlength="12" pattern="[A-Za-z0-9]+" placeholder="BAL" hint="Para marcadores." />
            </div>
            <fieldset class="rounded-xl border divider p-4" x-data="{ colors: @js(array_values($sec)) }">
                <legend class="px-2 text-sm font-medium">Colores</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="primary_color">Color principal</label>
                        <div class="flex gap-2">
                            <input type="color" id="primary_color" name="primary_color" value="{{ old('primary_color', $team->primary_color ?? '#D92332') }}" class="h-11 w-14 cursor-pointer rounded-lg border border-white/10 bg-ink-900 p-1">
                            <input type="text" name="primary_color_name" value="{{ old('primary_color_name', $team->primary_color_name) }}" maxlength="40" placeholder="Nombre (ej. Azul)" class="input">
                        </div>
                        @error('primary_color')<p class="error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <p class="label mt-4">Colores secundarios</p>
                <template x-for="(c, i) in colors" :key="i">
                    <div class="mb-2 flex gap-2">
                        <input type="color" :name="`secondary_colors[${i}][hex]`" x-model="c.hex" class="h-11 w-14 cursor-pointer rounded-lg border border-white/10 bg-ink-900 p-1">
                        <input type="text" :name="`secondary_colors[${i}][name]`" x-model="c.name" maxlength="40" placeholder="Nombre del color" class="input">
                        <button type="button" class="btn-ghost" @click="colors.splice(i, 1)" aria-label="Quitar color"><x-icon name="x" class="size-4" /></button>
                    </div>
                </template>
                <button type="button" class="btn-secondary btn-sm" x-show="colors.length < 4" @click="colors.push({ hex: '#FFFFFF', name: '' })"><x-icon name="plus" class="size-4" />Agregar color</button>
                @error('secondary_colors.*')<p class="error">{{ $message }}</p>@enderror
            </fieldset>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="coach_name" label="Entrenador" :value="$team->coach_name" maxlength="120" />
                <x-form.input name="assistant_coach_name" label="Asistente" :value="$team->assistant_coach_name" maxlength="120" />
                <x-form.input name="delegate_name" label="Delegado" :value="$team->delegate_name" maxlength="120" />
                <x-form.input type="tel" name="contact_phone" label="Teléfono de contacto" :value="$team->contact_phone" maxlength="30" pattern="[0-9+()\-\s]+" />
            </div>
        </div>
        <div class="space-y-6">
            <div class="card p-5 sm:p-6"><x-form.image name="logo" label="Logo oficial del equipo" :current="$team->logoUrl()" remove-name="remove_logo" hint="Suba el logo oficial (PNG con transparencia recomendado). Sin logo se muestran las iniciales sobre los colores del equipo." /></div>
            <div class="card p-5 sm:p-6"><x-form.checkbox name="is_active" label="Equipo activo" :checked="$team->is_active ?? true" /></div>
            <div class="flex gap-2">
                <a href="{{ $editing ? route('admin.teams.show', $team) : route('admin.teams.index') }}" class="btn-ghost flex-1">Cancelar</a>
                <button class="btn-primary flex-1" :disabled="saving"><x-icon name="check" class="size-4" />Guardar</button>
            </div>
        </div>
    </form>
</x-layouts.admin>
