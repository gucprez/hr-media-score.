@php $editing = $club->exists; $title = $editing ? $club->name : 'Nueva organización'; @endphp
<x-layouts.admin :title="$title" :header="$title" :breadcrumbs="(auth()->user()->isSuperAdmin() ? ['Organizaciones' => route('admin.clubs.index')] : []) + [($editing ? 'Editar' : 'Nueva') => null]">
    <form method="POST" action="{{ $editing ? route('admin.clubs.update', $club) : route('admin.clubs.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3">
        @csrf @if ($editing) @method('PUT') @endif
        <div class="card space-y-5 p-5 sm:p-6 lg:col-span-2">
            <div class="grid gap-5 sm:grid-cols-3">
                <x-form.input name="name" label="Nombre" :value="$club->name" required maxlength="150" class="sm:col-span-2" />
                <x-form.input name="short_name" label="Siglas" :value="$club->short_name" maxlength="30" />
                <x-form.input type="number" name="founded_year" label="Año de fundación" :value="$club->founded_year" min="1800" :max="now()->year" />
                <x-form.input name="city" label="Ciudad" :value="$club->city" maxlength="100" />
                <x-form.input name="province" label="Provincia" :value="$club->province" maxlength="100" />
                <x-form.input name="country" label="País (ISO)" :value="$club->country ?? 'DO'" required maxlength="2" minlength="2" pattern="[A-Za-z]{2}" />
                <x-form.input type="email" name="email" label="Correo" :value="$club->email" maxlength="255" />
                <x-form.input type="tel" name="phone" label="Teléfono" :value="$club->phone" maxlength="30" />
                <x-form.input type="url" name="website" label="Sitio web" :value="$club->website" maxlength="255" placeholder="https://" />
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label class="label" for="primary_color">Color principal</label><input type="color" id="primary_color" name="primary_color" value="{{ old('primary_color', $club->primary_color ?? '#D92332') }}" class="h-11 w-full cursor-pointer rounded-xl border border-white/10 bg-ink-900 p-1"></div>
                <div><label class="label" for="secondary_color">Color secundario</label><input type="color" id="secondary_color" name="secondary_color" value="{{ old('secondary_color', $club->secondary_color ?? '#FFFFFF') }}" class="h-11 w-full cursor-pointer rounded-xl border border-white/10 bg-ink-900 p-1"></div>
            </div>
            <x-form.textarea name="description" label="Descripción" :value="$club->description" maxlength="5000" />
        </div>
        <div class="space-y-6">
            <div class="card p-5 sm:p-6"><x-form.image name="logo" label="Logo" :current="$club->logoUrl()" remove-name="remove_logo" /></div>
            @if (auth()->user()->isSuperAdmin())
                <div class="card p-5 sm:p-6"><x-form.checkbox name="is_active" label="Organización activa" :checked="$club->is_active ?? true" hint="Si se desactiva, sus usuarios no podrán iniciar sesión." /></div>
            @else
                <input type="hidden" name="is_active" value="1">
            @endif
            <button class="btn-primary w-full"><x-icon name="check" class="size-4" />Guardar</button>
            @if ($editing)@can('delete', $club)<x-confirm-delete :action="route('admin.clubs.destroy', $club)" label="Eliminar organización" class="text-center" />@endcan @endif
        </div>
    </form>
</x-layouts.admin>
