@props(['title', 'eyebrow' => null, 'href' => null, 'linkText' => 'Ver todo'])
<div {{ $attributes->merge(['class' => 'mb-5 flex items-end justify-between gap-4']) }}>
    <div>
        @if ($eyebrow)<p class="eyebrow mb-1">{{ $eyebrow }}</p>@endif
        <h2 class="heading flex items-center gap-3 text-2xl sm:text-3xl"><span class="h-6 w-1.5 rounded-full bg-brand"></span>{{ $title }}</h2>
    </div>
    @if ($href)
        <a href="{{ $href }}" class="group inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-mute hover:text-white">
            {{ $linkText }} <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-0.5" />
        </a>
    @endif
</div>
