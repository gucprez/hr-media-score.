<a href="{{ route('public.tournaments.show', [$t->id, $t->slug]) }}" class="card card-hover group relative block overflow-hidden p-6">
    <div class="glow-red absolute inset-0 opacity-40 transition group-hover:opacity-80"></div>
    <div class="relative">
        <div class="flex items-center justify-between gap-2"><span class="{{ $t->status->badgeClass() }}">{{ $t->status->label() }}</span>@if ($t->logoUrl())<img src="{{ $t->logoUrl() }}" alt="" class="size-10 object-contain">@endif</div>
        @if ($t->edition)<p class="mt-4 font-display text-5xl font-extrabold text-transparent [-webkit-text-stroke:1.5px_var(--color-brand)]">{{ $t->edition }}</p>@endif
        <h3 class="mt-1 font-display text-2xl font-bold uppercase leading-tight">{{ $t->name }}</h3>
        @if ($t->motto)<p class="mt-1 text-sm italic text-mute">«{{ $t->motto }}»</p>@endif
        <p class="mt-4 text-xs text-mute">{{ collect([$t->club->name, $t->category, isset($t->registrations_count) ? $t->registrations_count.' equipos' : null])->filter()->implode(' · ') }}</p>
    </div>
</a>
