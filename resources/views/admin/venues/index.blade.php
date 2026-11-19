<x-layouts.admin title="Sedes" header="Sedes" :breadcrumbs="['Sedes' => null]">
    <x-slot:actions>@can('create', App\Models\Venue::class)<a href="{{ route('admin.venues.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" />Nueva sede</a>@endcan</x-slot:actions>
    <div class="card overflow-x-auto">
        <table class="table-base">
            <thead><tr><th>Sede</th><th>Dirección</th><th class="text-center">Capacidad</th><th class="text-center">Torneos</th><th></th></tr></thead>
            <tbody>
                @forelse ($venues as $v)
                    <tr>
                        <td class="font-semibold">{{ $v->name }}@if(auth()->user()->isSuperAdmin())<span class="block text-xs font-normal text-mute">{{ $v->club->name }}</span>@endif</td>
                        <td class="text-mute">{{ collect([$v->address, $v->city])->filter()->implode(', ') ?: '—' }}</td>
                        <td class="text-center">{{ $v->capacity ?? '—' }}</td>
                        <td class="text-center font-score text-lg">{{ $v->tournaments_count }}</td>
                        <td><div class="flex justify-end gap-2"><a href="{{ route('admin.venues.edit', $v) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" />Editar</a><x-confirm-delete :action="route('admin.venues.destroy', $v)" label="" /></div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 text-center text-mute">No hay sedes registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $venues->links() }}</div>
</x-layouts.admin>
