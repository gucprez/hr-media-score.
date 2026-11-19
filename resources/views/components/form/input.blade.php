@props(['name', 'label' => null, 'hint' => null, 'value' => null, 'type' => 'text', 'required' => false])
@php $errKey = str_replace(['[', ']'], ['.', ''], $name); $id = $attributes->get('id', $errKey); @endphp
<x-form.field :name="$name" :label="$label" :hint="$hint" :required="$required" :class="$attributes->get('class')">
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}" @if($type !== 'password' && $type !== 'file') value="{{ old($errKey, $value) }}" @endif
        @required($required) {{ $attributes->except(['class', 'id'])->merge(['class' => 'input'.($errors->has($errKey) ? ' input-error' : '')]) }}
        @error($errKey) aria-invalid="true" @enderror>
</x-form.field>
