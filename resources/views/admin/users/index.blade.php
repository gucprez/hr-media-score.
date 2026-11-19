<x-layouts.admin title="Usuarios" header="Usuarios y permisos" :breadcrumbs="['Usuarios' => null]">
    <x-slot:actions><a href="{{ route('admin.users.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" />Nuevo usuario</a></x-slot:actions>
    <form method="GET" class="mb-5" role="search"><input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o correo…" class="input sm:max-w-sm" aria-label="Buscar usuarios"></form>
    <div class="card overflow-x-auto">
        <table class="table-base">
            <thead><tr><th>Usuario</th><th>Rol</th><th>Organización</th><th>Último acceso</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td><div class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-full bg-ink-600 font-display text-sm font-bold">{{ $u->initials() }}</span><div><p class="font-semibold">{{ $u->name }}</p><p class="text-xs text-mute">{{ $u->email }}</p></div></div></td>
                        <td><span class="badge-scheduled">{{ $u->roleLabel() }}</span></td>
                        <td class="text-mute">{{ $u->club?->name ?? 'Plataforma' }}</td>
                        <td class="text-mute">{{ $u->last_login_at?->diffForHumans() ?? 'Nunca' }}</td>
                        <td><span class="{{ $u->is_active ? 'badge-success' : 'badge-muted' }}">{{ $u->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                        <td><div class="flex justify-end gap-2">
                            @can('update', $u)<a href="{{ route('admin.users.edit', $u) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" />Editar</a>@endcan
                            @if (! auth()->user()->is($u)) @can('delete', $u)<x-confirm-delete :action="route('admin.users.destroy', $u)" label="" message="¿Eliminar este usuario?" />@endcan @endif
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-mute">No hay usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $users->links() }}</div>
</x-layouts.admin>
