@props(['name', 'label' => null, 'hint' => null, 'value' => null, 'options' => [], 'placeholder' => null, 'required' => false])
@php
    $errKey = str_replace(['[', ']'], ['.', ''], $name);
    $current = (string) old($errKey, $value instanceof \BackedEnum ? $value->value : $value);
@endphp
<x-form.field :name="$name" :label="$label" :hint="$hint" :required="$required" :class="$attributes->get('class')">
    <select name="{{ $name }}" id="{{ $errKey }}" @required($required) {{ $attributes->except('class')->merge(['class' => 'input'.($errors->has($errKey) ? ' input-error' : '')]) }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $optValue => $optLabel)
            <option value="{{ $optValue }}" @selected($current === (string) $optValue)>{{ $optLabel }}</option>
        @endforeach
    </select>
</x-form.field>
