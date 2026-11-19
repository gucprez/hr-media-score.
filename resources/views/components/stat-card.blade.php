@props(['label', 'value', 'icon' => 'chart', 'accent' => 'brand', 'href' => null])
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'card relative overflow-hidden p-5'.($href ? ' card-hover' : '')]) }}>
    <div class="flex items-start justify-between">
        <p class="eyebrow">{{ $label }}</p>
        <span @class(['grid size-9 place-items-center rounded-xl', 'bg-brand/15 text-brand-400' => $accent === 'brand', 'bg-sport/15 text-sport' => $accent === 'sport', 'bg-white/5 text-mute' => $accent === 'mute'])><x-icon :name="$icon" class="size-4.5" /></span>
    </div>
    <p class="font-score mt-3 text-4xl">{{ $value }}</p>
    @if ($slot->isNotEmpty())<p class="mt-1 text-xs text-mute">{{ $slot }}</p>@endif
</{{ $tag }}>
