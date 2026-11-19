@props(['name', 'label' => null, 'hint' => null, 'required' => false])
@php $errKey = str_replace(['[', ']'], ['.', ''], $name); @endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $attributes->get('id', $errKey) }}" class="label">{{ $label }}@if ($required)<span class="text-brand-400" aria-hidden="true"> *</span>@endif</label>
    @endif
    {{ $slot }}
    @if ($hint)<p class="hint">{{ $hint }}</p>@endif
    @error($errKey)<p class="error" role="alert">{{ $message }}</p>@enderror
</div>
