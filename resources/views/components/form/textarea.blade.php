@props(['name', 'label' => null, 'hint' => null, 'value' => null, 'rows' => 4, 'required' => false])
@php $errKey = str_replace(['[', ']'], ['.', ''], $name); @endphp
<x-form.field :name="$name" :label="$label" :hint="$hint" :required="$required" :class="$attributes->get('class')">
    <textarea name="{{ $name }}" id="{{ $errKey }}" rows="{{ $rows }}" @required($required) {{ $attributes->except('class')->merge(['class' => 'input'.($errors->has($errKey) ? ' input-error' : '')]) }}>{{ old($errKey, $value) }}</textarea>
</x-form.field>
