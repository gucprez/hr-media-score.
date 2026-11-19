@props(['name', 'label', 'checked' => false, 'hint' => null])
@php $errKey = str_replace(['[', ']'], ['.', ''], $name); @endphp
<div {{ $attributes->only('class') }}>
    <label class="flex cursor-pointer items-start gap-3">
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox" name="{{ $name }}" value="1" @checked(old($errKey, $checked)) class="peer sr-only">
        <span class="relative mt-0.5 h-5 w-9 shrink-0 rounded-full bg-ink-600 transition peer-checked:bg-brand peer-focus-visible:ring-2 peer-focus-visible:ring-sport after:absolute after:left-0.5 after:top-0.5 after:size-4 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-4"></span>
        <span>
            <span class="text-sm font-medium">{{ $label }}</span>
            @if ($hint)<span class="block text-xs text-mute-dim">{{ $hint }}</span>@endif
        </span>
    </label>
    @error($errKey)<p class="error">{{ $message }}</p>@enderror
</div>
