<x-layouts.admin title="Temporadas" header="Temporadas" :breadcrumbs="['Temporadas' => null]">
    <x-slot:actions>@can('create', App\Models\Season::class)<a href="{{ route('admin.seasons.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" />Nueva temporada</a>@endcan</x-slot:actions>
    <div class="card overflow-x-auto">
        <table class="table-base">
            <thead><tr><th>Temporada</th><th>Período</th><th class="text-center">Torneos</th><th></th></tr></thead>
            <tbody>
                @forelse ($seasons as $s)
                    <tr>
                        <td class="font-semibold">{{ $s->name }} @if($s->is_current)<span class="badge-live ml-2">Actual</span>@endif @if(auth()->user()->isSuperAdmin())<span class="block text-xs font-normal text-mute">{{ $s->club->name }}</span>@endif</td>
                        <td class="text-mute">{{ $s->starts_on?->format('d/m/Y') ?? '—' }} – {{ $s->ends_on?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-center font-score text-lg">{{ $s->tournaments_count }}</td>
                        <td><div class="flex justify-end gap-2"><a href="{{ route('admin.seasons.edit', $s) }}" class="btn-ghost btn-sm"><x-icon name="pencil" class="size-4" />Editar</a><x-confirm-delete :action="route('admin.seasons.destroy', $s)" label="" /></div></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-10 text-center text-mute">No hay temporadas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $seasons->links() }}</div>
</x-layouts.admin>
