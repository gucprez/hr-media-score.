<x-layouts.auth title="Acceso">
    <h1 class="heading text-2xl">Acceso administrativo</h1>
    <p class="mt-1 text-sm text-mute">Ingrese con la cuenta asignada por su organización.</p>
    @if (session('status'))<p class="mt-4 rounded-xl bg-success/10 px-4 py-3 text-sm text-success" role="status">{{ session('status') }}</p>@endif
    <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4" x-data="{ loading: false, show: false }" @submit="loading = true">
        @csrf
        <x-form.input name="email" type="email" label="Correo electrónico" autocomplete="username" required autofocus maxlength="255" />
        <x-form.field name="password" label="Contraseña" required>
            <div class="relative">
                <input :type="show ? 'text' : 'password'" type="password" name="password" id="password" required autocomplete="current-password" maxlength="255" class="input pr-20">
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-3 text-xs font-semibold text-mute hover:text-white" x-text="show ? 'Ocultar' : 'Mostrar'">Mostrar</button>
            </div>
        </x-form.field>
        <div class="flex items-center justify-between gap-3">
            <label class="flex items-center gap-2 text-sm text-mute"><input type="checkbox" name="remember" value="1" class="rounded border-white/20 bg-ink-900 text-brand focus:ring-sport"> Recordarme</label>
            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-sport hover:underline">¿Olvidó su contraseña?</a>
        </div>
        <button type="submit" class="btn-primary w-full py-3" :disabled="loading">
            <svg x-show="loading" x-cloak class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity=".25"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3"/></svg>
            <span x-text="loading ? 'Verificando…' : 'Iniciar sesión'">Iniciar sesión</span>
        </button>
    </form>
</x-layouts.auth>
