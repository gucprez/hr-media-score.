<x-layouts.admin title="Jugadores" header="Jugadores" :breadcrumbs="['Jugadores' => null]">
    <x-slot:actions>@can('create', App\Models\Player::class)<a href="{{ route('admin.players.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" />Nuevo jugador</a>@endcan</x-slot:actions>
    <livewire:admin.players-table />
</x-layouts.admin>
