@php $editing = $venue->exists; $title = $editing ? 'Editar sede' : 'Nueva sede'; @endphp
<x-layouts.admin :title="$title" :header="$title" :breadcrumbs="['Sedes' => route('admin.venues.index'), $title => null]">
    <form method="POST" action="{{ $editing ? route('admin.venues.update', $venue) : route('admin.venues.store') }}" class="card max-w-2xl space-y-5 p-5 sm:p-6">
        @csrf @if ($editing) @method('PUT') @endif
        @if (! $editing && auth()->user()->isSuperAdmin())<x-form.select name="club_id" label="Organización" :options="App\Models\Club::orderBy('name')->pluck('name', 'id')" placeholder="Seleccione…" required />@endif
        <x-form.input name="name" label="Nombre" :value="$venue->name" required maxlength="150" />
        <x-form.input name="address" label="Dirección" :value="$venue->address" maxlength="255" />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="city" label="Ciudad" :value="$venue->city" maxlength="100" />
            <x-form.input type="number" name="capacity" label="Capacidad" :value="$venue->capacity" min="0" max="100000" />
        </div>
        <div class="flex justify-end gap-2"><a href="{{ route('admin.venues.index') }}" class="btn-ghost">Cancelar</a><button class="btn-primary">Guardar</button></div>
    </form>
</x-layouts.admin>
