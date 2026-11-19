<x-layouts.auth title="Recuperar contraseña">
    <h1 class="heading text-2xl">Recuperar contraseña</h1>
    <p class="mt-1 text-sm text-mute">Le enviaremos un enlace para crear una nueva contraseña.</p>
    @if (session('status'))<p class="mt-4 rounded-xl bg-success/10 px-4 py-3 text-sm text-success" role="status">{{ session('status') }}</p>@endif
    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf
        <x-form.input name="email" type="email" label="Correo electrónico" required autofocus maxlength="255" autocomplete="email" />
        <button class="btn-primary w-full py-3">Enviar enlace</button>
    </form>
    <a href="{{ route('login') }}" class="mt-4 block text-center text-sm text-mute hover:text-white">Volver al acceso</a>
</x-layouts.auth>
