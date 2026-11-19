<x-layouts.admin title="Organizaciones" header="Organizaciones" :breadcrumbs="['Organizaciones' => null]">
    <x-slot:actions>@can('create', App\Models\Club::class)<a href="{{ route('admin.clubs.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" />Nueva organización</a>@endcan</x-slot:actions>
    <div class="card overflow-x-auto">
        <table class="table-base">
            <thead><tr><th>Organización</th><th>Ciudad</th><th class="text-center">Torneos</th><th class="text-center">Equipos</th><th class="text-center">Jugadores</th><th class="text-center">Usuarios</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                @forelse ($clubs as $club)
                    <tr>
                        <td class="font-semibold"><div class="flex items-center gap-3">@if($club->logoUrl())<img src="{{ $club->logoUrl() }}" alt="" class="size-8 object-contain">@endif{{ $club->name }}</div></td>
                        <td class="text-mute">{{ $club->city ?? '—' }}</td>
                        <td class="text-center font-score text-lg">{{ $club->tournaments_count }}</td>
                        <td class="text-center font-score text-lg">{{ $club->teams_count }}</td>
                        <td class="text-center font-score text-lg">{{ $club->players_count }}</td>
                        <td class="text-center font-score text-lg">{{ $club->users_count }}</td>
                        <td><span class="{{ $club->is_active ? 'badge-success' : 'badge-muted' }}">{{ $club->is_active ? 'Activa' : 'Inactiva' }}</span></td>
                        <td class="text-right"><a href="{{ route('admin.clubs.edit', $club) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" />Editar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-10 text-center text-mute">No hay organizaciones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $clubs->links() }}</div>
</x-layouts.admin>
