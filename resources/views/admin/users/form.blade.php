@php $editing = $user->exists; $title = $editing ? 'Editar usuario' : 'Nuevo usuario'; @endphp
<x-layouts.admin :title="$title" :header="$title" :breadcrumbs="['Usuarios' => route('admin.users.index'), $title => null]">
    <form method="POST" action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}" class="grid gap-6 lg:grid-cols-3" x-data="{ role: @js(old('role', $user->roles->first()?->name ?? 'organizer')) }">
        @csrf @if ($editing) @method('PUT') @endif
        <div class="card space-y-5 p-5 sm:p-6 lg:col-span-2">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="name" label="Nombre completo" :value="$user->name" required maxlength="120" />
                <x-form.input type="email" name="email" label="Correo electrónico" :value="$user->email" required maxlength="255" autocomplete="off" />
                <x-form.input type="password" name="password" :label="$editing ? 'Nueva contraseña' : 'Contraseña'" :required="! $editing" minlength="8" autocomplete="new-password" :hint="$editing ? 'Déjela vacía para mantener la actual.' : 'Mínimo 8 caracteres, con letras y números.'" />
                <x-form.input type="password" name="password_confirmation" label="Confirmar contraseña" :required="! $editing" autocomplete="new-password" />
            </div>
            @if ($clubs->isNotEmpty())
                <div x-show="role !== 'super-admin'"><x-form.select name="club_id" label="Organización" :options="$clubs->pluck('name', 'id')" :value="$user->club_id" placeholder="Seleccione…" /></div>
            @endif
        </div>
        <div class="space-y-6">
            <fieldset class="card space-y-2 p-5 sm:p-6">
                <legend class="sr-only">Rol</legend>
                <p class="label">Rol</p>
                @php $desc = ['super-admin' => 'Acceso completo a la plataforma.', 'club-admin' => 'Administra la organización, usuarios y recursos.', 'organizer' => 'Gestiona torneos, equipos, jugadores y plantillas.', 'scorekeeper' => 'Registra los partidos asignados.']; @endphp
                @foreach ($roles as $role)
                    <label class="flex cursor-pointer gap-3 rounded-xl border p-3 transition" :class="role === '{{ $role->value }}' ? 'border-brand/50 bg-brand/10' : 'border-white/10 hover:border-white/20'">
                        <input type="radio" name="role" value="{{ $role->value }}" x-model="role" class="mt-1 text-brand focus:ring-sport">
                        <span><span class="block text-sm font-semibold">{{ $role->label() }}</span><span class="block text-xs text-mute">{{ $desc[$role->value] }}</span></span>
                    </label>
                @endforeach
                @error('role')<p class="error">{{ $message }}</p>@enderror
            </fieldset>
            <div class="card p-5 sm:p-6"><x-form.checkbox name="is_active" label="Cuenta activa" :checked="$user->is_active ?? true" /></div>
            <button class="btn-primary w-full"><x-icon name="check" class="size-4" />Guardar</button>
        </div>
    </form>
</x-layouts.admin>
