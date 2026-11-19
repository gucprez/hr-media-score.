<x-layouts.admin title="Configuración" header="Configuración" :breadcrumbs="['Configuración' => null]">
    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('admin.settings.profile') }}" class="card space-y-5 p-5 sm:p-6">
            @csrf @method('PUT')
            <h2 class="heading text-lg">Mi perfil</h2>
            <x-form.input name="name" label="Nombre" :value="$user->name" required maxlength="120" />
            <x-form.input type="email" name="email" label="Correo" :value="$user->email" required maxlength="255" />
            <p class="text-sm text-mute">Rol: <b class="text-white">{{ $user->roleLabel() }}</b>{{ $user->club ? ' · '.$user->club->name : '' }}</p>
            <div class="flex justify-end"><button class="btn-primary">Guardar perfil</button></div>
        </form>
        <form method="POST" action="{{ route('admin.settings.password') }}" class="card space-y-5 p-5 sm:p-6">
            @csrf @method('PUT')
            <h2 class="heading text-lg">Cambiar contraseña</h2>
            <x-form.input type="password" name="current_password" label="Contraseña actual" required autocomplete="current-password" />
            <x-form.input type="password" name="password" label="Nueva contraseña" required minlength="8" autocomplete="new-password" />
            <x-form.input type="password" name="password_confirmation" label="Confirmar nueva contraseña" required autocomplete="new-password" />
            <div class="flex justify-end"><button class="btn-primary">Actualizar contraseña</button></div>
        </form>
    </div>
</x-layouts.admin>
