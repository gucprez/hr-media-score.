<x-layouts.public>
    {{-- Héroe --}}
    <section class="relative overflow-hidden border-b divider">
        <div class="glow-red absolute inset-0"></div>
        <div class="court-lines absolute inset-0"></div>
        <div class="relative mx-auto grid max-w-7xl items-center gap-10 px-4 py-12 sm:px-6 md:py-16 lg:grid-cols-[1.25fr_1fr] lg:py-20">
            <div class="animate-fade-up">
                @if ($featured)
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="eyebrow text-sport">Torneo destacado</span>
                        <span class="{{ $featured->status->badgeClass() }}">@if($featured->status->value === 'in_progress')<span class="size-1.5 rounded-full bg-brand-400 animate-pulse-dot"></span>@endif{{ $featured->status->label() }}</span>
                    </div>
                    <h1 class="mt-4 font-display font-extrabold uppercase leading-[.88] tracking-tight">
                        @if ($featured->edition)<span class="block text-7xl text-transparent [-webkit-text-stroke:2px_var(--color-brand)] sm:text-8xl lg:text-9xl">{{ $featured->edition }}</span>@endif
                        <span class="block text-4xl sm:text-5xl lg:text-6xl">{{ $featured->name }}</span>
                    </h1>
                    @if ($featured->motto)<p class="mt-4 font-display text-xl font-medium italic tracking-wide text-mute sm:text-2xl">«{{ $featured->motto }}»</p>@endif
                    <div class="mt-6 flex flex-wrap gap-2 text-sm">
                        <span class="badge-muted normal-case tracking-normal"><x-icon name="building" class="size-3.5" />{{ $featured->club->name }}</span>
                        @if ($featured->venue)<span class="badge-muted normal-case tracking-normal"><x-icon name="map-pin" class="size-3.5" />{{ $featured->venue->name }}</span>@endif
                        @if ($featured->category)<span class="badge-muted normal-case tracking-normal"><x-icon name="users" class="size-3.5" />{{ $featured->category }}@if($featured->rules?->min_age) · {{ $featured->rules->min_age }}–{{ $featured->rules->max_age }} años @endif</span>@endif
                        @if ($featured->starts_on)<span class="badge-muted normal-case tracking-normal"><x-icon name="calendar" class="size-3.5" />Desde {{ $featured->starts_on->translatedFormat('j \d\e F') }}</span>@endif
                    </div>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('public.tournaments.show', [$featured->id, $featured->slug]) }}" class="btn-primary px-6 py-3">Ver torneo <x-icon name="arrow-right" class="size-4" /></a>
                        <a href="{{ route('public.tournaments.show', [$featured->id, $featured->slug]) }}#reglamento" class="btn-secondary px-6 py-3">Reglamento</a>
                    </div>
                @else
                    <p class="eyebrow text-sport">Plataforma deportiva</p>
                    <h1 class="mt-4 font-display text-5xl font-extrabold uppercase leading-[.9] sm:text-7xl">Torneos, resultados<br>y estadísticas</h1>
                    <p class="mt-4 max-w-lg text-mute">Pronto encontrará aquí los torneos publicados por las organizaciones de HR Media Score.</p>
                @endif
            </div>
            <div class="relative order-first mx-auto w-full max-w-sm lg:order-none lg:max-w-none">
                <div class="absolute inset-10 rounded-full bg-brand/25 blur-3xl"></div>
                <img src="{{ asset('assets/branding/hr-logo-principal-640.webp') }}" srcset="{{ asset('assets/branding/hr-logo-principal-1024.webp') }} 1.6x"
                     alt="HR Media Score" width="640" height="404" class="relative mx-auto w-full max-w-[520px] drop-shadow-[0_20px_50px_rgba(0,0,0,.5)]" fetchpriority="high">
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl space-y-16 px-4 py-12 sm:px-6">
        @if ($matchday->isNotEmpty())
            @php $mdDate = $matchday->first()->scheduled_at; @endphp
            <section class="arena-bg relative overflow-hidden rounded-3xl border border-white/10 px-4 py-8 sm:px-10 sm:py-10">
                <div class="arena-lights absolute inset-0 opacity-60"></div>
                <div class="relative flex flex-col items-center gap-3 text-center">
                    <p class="eyebrow text-gold">{{ $mdDate->isToday() ? 'Hoy se juega' : 'Próxima jornada' }}@if ($matchday->first()->round_number) · Jornada {{ $matchday->first()->round_number }}@endif</p>
                    <p class="ribbon-red poster-type w-full max-w-2xl py-2 text-2xl text-white sm:text-4xl">{{ $mdDate->translatedFormat('l j \d\e F') }}</p>
                    @if ($matchday->count() > 1)<p class="ribbon-gold poster-type px-10 py-1 text-lg not-italic tracking-wider sm:text-2xl">{{ $matchday->every(fn ($g) => $g->status->isFinished()) ? 'Resultados' : ($matchday->count() === 2 ? 'Doble cartelera' : ($matchday->count() === 3 ? 'Triple cartelera' : $matchday->count().' partidos')) }}</p>@endif
                </div>
                <div class="relative mx-auto mt-8 grid max-w-5xl gap-10 {{ $matchday->count() > 1 ? 'lg:grid-cols-2' : '' }}">
                    @foreach ($matchday as $g)<x-matchup :game="$g" size="lg" />@endforeach
                </div>
                <div class="relative mt-8 flex flex-wrap items-center justify-center gap-3">
                    @if ($matchday->first()->venue)<span class="badge-muted normal-case tracking-normal"><x-icon name="map-pin" class="size-3.5 text-brand-400" />{{ $matchday->first()->venue->name }}</span>@endif
                    <a href="{{ route('public.tournaments.poster', [$featured->id, $mdDate->toDateString()]) }}" class="btn-secondary btn-sm"><x-icon name="image" class="size-4 text-gold" />Cartelera para compartir</a>
                </div>
            </section>
        @endif

        @if ($live->isNotEmpty())
            <section>
                <x-section-title title="En vivo" eyebrow="Ahora mismo" />
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@foreach ($live as $g)<x-game-card :game="$g" />@endforeach</div>
            </section>
        @endif

        <div class="grid gap-10 lg:grid-cols-2">
            <section>
                <x-section-title title="Próximos partidos" :href="route('public.games.index')" />
                @if ($upcoming->isEmpty())
                    <x-empty-state icon="calendar" title="Calendario por publicar">El calendario oficial se publicará aquí en cuanto la organización lo confirme.</x-empty-state>
                @else
                    <div class="grid gap-3 sm:grid-cols-2">@foreach ($upcoming->take(4) as $g)<x-game-card :game="$g" compact />@endforeach</div>
                @endif
            </section>
            <section>
                <x-section-title title="Resultados" :href="route('public.games.index')" />
                @if ($recent->isEmpty())
                    <x-empty-state icon="scoreboard" title="Aún no hay resultados">Los marcadores finales aparecerán aquí tras cada partido.</x-empty-state>
                @else
                    <div class="grid gap-3 sm:grid-cols-2">@foreach ($recent->take(4) as $g)<x-game-card :game="$g" compact />@endforeach</div>
                @endif
            </section>
        </div>

        @if ($featured)
            <section>
                <x-section-title title="Equipos participantes" :eyebrow="$featured->fullName()" :href="route('public.teams.index')" />
                @if ($teams->isEmpty())
                    <x-empty-state icon="shield" title="Inscripciones abiertas">Los equipos aparecerán cuando se confirme su inscripción.</x-empty-state>
                @else
                    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                        @foreach ($teams as $reg)
                            @php $t = $reg->team; $sec = collect($t->secondary_colors ?? [])->pluck('hex')->filter(); @endphp
                            <a href="{{ route('public.teams.show', [$t->id, $t->slug]) }}" class="card card-hover group relative overflow-hidden p-5 text-center sm:p-6">
                                <div class="absolute inset-x-0 top-0 flex h-1.5"><span class="flex-[3]" style="background: {{ $t->primary_color ?? '#222D41' }}"></span>@foreach ($sec as $c)<span class="flex-1" style="background: {{ $c }}"></span>@endforeach</div>
                                <div class="absolute -right-10 -top-10 size-32 rounded-full opacity-20 blur-2xl transition group-hover:opacity-40" style="background: {{ $t->primary_color ?? '#D92332' }}"></div>
                                <x-team-badge :team="$t" size="xl" class="relative mx-auto transition duration-300 group-hover:scale-105" />
                                <h3 class="relative mt-4 font-display text-xl font-bold uppercase tracking-wide sm:text-2xl">{{ $t->name }}</h3>
                                <p class="relative mt-1 text-xs text-mute">{{ $t->primary_color_name ?? '' }}{{ $sec->isNotEmpty() ? ' · '.collect($t->secondary_colors)->pluck('name')->filter()->implode(', ') : '' }}</p>
                                <p class="relative mt-3 text-xs font-semibold uppercase tracking-wider text-mute-dim">{{ $reg->active_roster_count ? $reg->active_roster_count.' jugadores' : 'Plantilla por publicar' }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section>
                <x-section-title title="Tabla de posiciones" :eyebrow="$featured->fullName()" :href="route('public.tournaments.show', [$featured->id, $featured->slug]).'#posiciones'" link-text="Ver tabla" />
                @include('public.partials.standings', ['standings' => $standings, 'registrations' => $teams, 'rules' => $featured->rules])
            </section>
        @endif

        @if ($others->isNotEmpty())
            <section>
                <x-section-title title="Otros torneos" :href="route('public.tournaments.index')" />
                <div class="grid gap-4 md:grid-cols-3">@foreach ($others as $t)@include('public.partials.tournament-card', ['t' => $t])@endforeach</div>
            </section>
        @endif
    </div>
</x-layouts.public>
