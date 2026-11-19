@php $editing = $season->exists; $title = $editing ? 'Editar temporada' : 'Nueva temporada'; @endphp
<x-layouts.admin :title="$title" :header="$title" :breadcrumbs="['Temporadas' => route('admin.seasons.index'), $title => null]">
    <form method="POST" action="{{ $editing ? route('admin.seasons.update', $season) : route('admin.seasons.store') }}" class="card max-w-2xl space-y-5 p-5 sm:p-6">
        @csrf @if ($editing) @method('PUT') @endif
        @if (! $editing && auth()->user()->isSuperAdmin())<x-form.select name="club_id" label="Organización" :options="App\Models\Club::orderBy('name')->pluck('name', 'id')" placeholder="Seleccione…" required />@endif
        <x-form.input name="name" label="Nombre" :value="$season->name" required maxlength="100" placeholder="Temporada 2026" />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input type="date" name="starts_on" label="Inicio" :value="$season->starts_on?->toDateString()" />
            <x-form.input type="date" name="ends_on" label="Fin" :value="$season->ends_on?->toDateString()" />
        </div>
        <x-form.checkbox name="is_current" label="Temporada actual" :checked="$season->is_current" />
        <div class="flex justify-end gap-2"><a href="{{ route('admin.seasons.index') }}" class="btn-ghost">Cancelar</a><button class="btn-primary">Guardar</button></div>
    </form>
</x-layouts.admin>
