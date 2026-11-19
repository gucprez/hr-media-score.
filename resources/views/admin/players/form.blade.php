@php $editing = $player->exists; $title = $editing ? 'Editar jugador' : 'Nuevo jugador'; @endphp
<x-layouts.admin :title="$title" :header="$title" :breadcrumbs="['Jugadores' => route('admin.players.index')] + ($editing ? [$player->full_name => route('admin.players.show', $player)] : []) + [($editing ? 'Editar' : 'Nuevo') => null]">
    <form method="POST" action="{{ $editing ? route('admin.players.update', $player) : route('admin.players.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3" x-data="{ saving: false }" @submit="saving = true">
        @csrf @if ($editing) @method('PUT') @endif
        <div class="space-y-6 lg:col-span-2">
            <section class="card space-y-5 p-5 sm:p-6">
                <h2 class="heading text-lg">Datos personales</h2>
                @if ($clubs->isNotEmpty())<x-form.select name="club_id" label="Organización" :options="$clubs->pluck('name', 'id')" :value="$player->club_id" placeholder="Seleccione…" required />@endif
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="first_name" label="Nombres" :value="$player->first_name" required maxlength="80" autocomplete="off" />
                    <x-form.input name="last_name" label="Apellidos" :value="$player->last_name" required maxlength="80" autocomplete="off" />
                    <x-form.input name="nickname" label="Apodo" :value="$player->nickname" maxlength="60" />
                    <x-form.input type="date" name="birth_date" label="Fecha de nacimiento" :value="$player->birth_date?->toDateString()" :max="now()->subDay()->toDateString()" hint="Necesaria para validar la edad del torneo." />
                    <x-form.input name="document_number" label="Cédula / documento" :value="$player->document_number" maxlength="30" pattern="[A-Za-z0-9\-]+" hint="Dato privado: no se publica." />
                    <x-form.select name="gender" label="Sexo" :options="['male' => 'Masculino', 'female' => 'Femenino']" :value="$player->gender" placeholder="—" />
                </div>
            </section>
            <section class="card space-y-5 p-5 sm:p-6">
                <h2 class="heading text-lg">Perfil deportivo</h2>
                <div class="grid gap-5 sm:grid-cols-4">
                    <x-form.select name="position" label="Posición" :options="App\Enums\PlayerPosition::options()" :value="$player->position" placeholder="—" />
                    <x-form.input type="number" name="height_cm" label="Estatura (cm)" :value="$player->height_cm" min="100" max="250" />
                    <x-form.input type="number" name="weight_kg" label="Peso (kg)" :value="$player->weight_kg" min="25" max="200" />
                    <x-form.select name="dominant_hand" label="Mano hábil" :options="['right' => 'Derecha', 'left' => 'Izquierda']" :value="$player->dominant_hand" placeholder="—" />
                </div>
            </section>
            <section class="card space-y-5 p-5 sm:p-6">
                <h2 class="heading text-lg">Tutor y notas <span class="ml-2 align-middle text-xs font-normal normal-case text-mute">(privado)</span></h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.input name="guardian_name" label="Padre, madre o tutor" :value="$player->guardian_name" maxlength="120" />
                    <x-form.input type="tel" name="guardian_phone" label="Teléfono del tutor" :value="$player->guardian_phone" maxlength="30" pattern="[0-9+()\-\s]+" />
                </div>
                <x-form.textarea name="notes" label="Notas internas" :value="$player->notes" rows="3" maxlength="2000" />
            </section>
        </div>
        <div class="space-y-6">
            <div class="card p-5 sm:p-6"><x-form.image name="photo" label="Fotografía" :current="$player->photoUrl()" remove-name="remove_photo" hint="Foto tipo carnet. JPG, PNG o WebP · máx. 4 MB." /></div>
            <div class="card p-5 sm:p-6"><x-form.checkbox name="is_active" label="Jugador activo" :checked="$player->is_active ?? true" hint="Los inactivos no aparecen en el portal." /></div>
            <div class="flex gap-2">
                <a href="{{ $editing ? route('admin.players.show', $player) : route('admin.players.index') }}" class="btn-ghost flex-1">Cancelar</a>
                <button class="btn-primary flex-1" :disabled="saving"><x-icon name="check" class="size-4" />Guardar</button>
            </div>
        </div>
    </form>
</x-layouts.admin>
