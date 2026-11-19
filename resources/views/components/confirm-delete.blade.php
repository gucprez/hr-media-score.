@props(['action', 'label' => 'Eliminar', 'message' => '¿Seguro? Esta acción no se puede deshacer.'])
<form method="POST" action="{{ $action }}" x-data @submit="if (!confirm(@js($message))) $event.preventDefault()" {{ $attributes }}>
    @csrf @method('DELETE')
    <button type="submit" class="btn-danger btn-sm" aria-label="{{ $label ?: 'Eliminar' }}" title="{{ $label ?: 'Eliminar' }}"><x-icon name="trash" class="size-4" />{{ $label }}</button>
</form>
