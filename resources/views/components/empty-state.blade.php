@props(['icon' => 'info', 'title', 'action' => null])
<div {{ $attributes->merge(['class' => 'card flex flex-col items-center justify-center gap-3 px-6 py-12 text-center']) }}>
    <span class="grid size-14 place-items-center rounded-2xl bg-white/5 text-mute"><x-icon :name="$icon" class="size-7" /></span>
    <h3 class="font-display text-lg font-semibold uppercase tracking-wide">{{ $title }}</h3>
    @if ($slot->isNotEmpty())<p class="max-w-md text-sm text-mute">{{ $slot }}</p>@endif
    {{ $action }}
</div>
