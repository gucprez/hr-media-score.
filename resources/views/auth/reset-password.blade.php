<x-layouts.auth title="Nueva contraseña">
    <h1 class="heading text-2xl">Nueva contraseña</h1>
    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.input name="email" type="email" label="Correo electrónico" :value="$email" required autocomplete="email" />
        <x-form.input name="password" type="password" label="Nueva contraseña" required minlength="8" autocomplete="new-password" hint="Mínimo 8 caracteres, con letras y números." />
        <x-form.input name="password_confirmation" type="password" label="Confirmar contraseña" required autocomplete="new-password" />
        <button class="btn-primary w-full py-3">Guardar contraseña</button>
    </form>
</x-layouts.auth>
