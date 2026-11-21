@php
    $club = $tournament->club;
    $W = 1080; $H = $story ? 1920 : 1350;
    $count = $games->count();
    $msize = $count <= 2 ? 'poster' : 'poster-sm';
    $nameWords = explode(' ', $tournament->name, 2);
    $line2 = $nameWords[1] ?? '';
    $line2Size = mb_strlen($line2) <= 16 ? 96 : (mb_strlen($line2) <= 26 ? 78 : (mb_strlen($line2) <= 34 ? 62 : 52));
    $venue = $games->first()->venue ?? $tournament->venue;
    $file = 'cartelera-'.$tournament->slug.'-'.$day->toDateString().($story ? '-historia' : '').'.png';
@endphp
<x-layouts.public :title="'Cartelera · '.$day->translatedFormat('j \d\e F')">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <a href="{{ route('public.tournaments.show', [$tournament->id, $tournament->slug]) }}#calendario" class="inline-flex items-center gap-1 text-sm text-mute hover:text-white"><x-icon name="chevron-left" class="size-4" />{{ $tournament->fullName() }}</a>
                <h1 class="heading mt-1 text-3xl sm:text-4xl">Cartelera</h1>
                <p class="text-sm text-mute">Lista para compartir en redes sociales.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($dates->count() > 1)
                    <select class="input w-auto" aria-label="Fecha" onchange="location.href = this.value">
                        @foreach ($dates as $d)
                            <option value="{{ route('public.tournaments.poster', [$tournament->id, $d]) }}{{ $story ? '?formato=historia' : '' }}" @selected($d === $day->toDateString())>{{ \Carbon\Carbon::parse($d)->translatedFormat('D j M Y') }}</option>
                        @endforeach
                    </select>
                @endif
                <div class="flex rounded-xl bg-ink-900 p-1">
                    <a href="{{ route('public.tournaments.poster', [$tournament->id, $day->toDateString()]) }}" @class(['btn btn-sm', 'bg-ink-700 text-white' => ! $story, 'text-mute' => $story])>Publicación 4:5</a>
                    <a href="{{ route('public.tournaments.poster', [$tournament->id, $day->toDateString()]) }}?formato=historia" @class(['btn btn-sm', 'bg-ink-700 text-white' => $story, 'text-mute' => ! $story])>Historia 9:16</a>
                </div>
                <button type="button" class="btn-primary" x-data="{ busy: false }" :disabled="busy"
                        @click="busy = true; downloadPoster(document.getElementById('poster'), @js($file)).finally(() => busy = false)">
                    <x-icon name="image" class="size-4" /><span x-text="busy ? 'Generando…' : 'Descargar imagen'">Descargar imagen</span>
                </button>
            </div>
        </div>

        {{-- Lienzo a tamaño real ({{ $W }}×{{ $H }}), escalado para la pantalla. --}}
        <div x-data="{ s: 1, fit() { this.s = Math.min(1, this.$el.clientWidth / {{ $W }}) } }" x-init="fit()" @resize.window="fit()" class="w-full">
            <div class="mx-auto overflow-hidden rounded-2xl shadow-2xl shadow-black/50" :style="`width:${ {{ $W }} * s }px; height:${ {{ $H }} * s }px`">
                <div :style="`transform: scale(${s}); transform-origin: top left; width: {{ $W }}px; height: {{ $H }}px`">
                    <div id="poster" class="arena-bg relative flex flex-col items-center overflow-hidden text-white" style="width: {{ $W }}px; height: {{ $H }}px">
                        <div class="arena-lights absolute inset-0"></div>
                        <div class="court-floor absolute inset-x-0 bottom-0 h-[24%] opacity-80" style="mask-image: linear-gradient(180deg, transparent, #000 40%)"></div>
                        <div class="absolute inset-x-0 bottom-0 h-[24%]" style="background: radial-gradient(50% 60% at 50% 0%, rgba(255,140,40,.35), transparent 70%)"></div>

                        <div class="relative flex h-full w-full flex-col items-center {{ $story ? 'justify-between pb-24 pt-24' : 'justify-between pb-14 pt-10' }}">
                            {{-- Club --}}
                            <div class="flex flex-col items-center">
                                @if ($club->logoUrl())
                                    <img src="{{ $club->logoUrl() }}" alt="{{ $club->name }}" class="{{ $story ? 'h-56' : 'h-36' }} w-auto object-contain poster-shadow">
                                @else
                                    <div class="relative rounded-[999px] border-4 border-white/90 px-14 pb-3 pt-2 text-center poster-shadow" style="background: radial-gradient(120% 120% at 50% 0%, #F0313F, #9E111C)">
                                        <p class="poster-type text-[34px] leading-none text-white">{{ \Illuminate\Support\Str::of($club->name)->replace(['Club ', 'club '], '')->before(' ')->upper() }}</p>
                                        <p class="poster-type text-[78px] leading-[.9] text-chrome">{{ \Illuminate\Support\Str::of($club->name)->replace(['Club ', 'club '], '')->after(' ')->upper() }}</p>
                                        @if ($club->founded_year)<p class="mt-1 font-display text-[18px] font-bold uppercase tracking-[.2em] text-gold">Fundado en el {{ $club->founded_year }}</p>@endif
                                    </div>
                                @endif
                            </div>

                            {{-- Título --}}
                            <div class="flex flex-col items-center px-10 text-center">
                                <p class="poster-type {{ $story ? 'text-[110px]' : 'text-[92px]' }} leading-[.95] poster-shadow">
                                    @if ($tournament->edition)<span class="text-gold-grad">{{ $tournament->edition }}</span>&nbsp;@endif<span class="text-chrome">{{ $nameWords[0] }}</span>
                                </p>
                                @if ($line2)<p class="poster-type leading-[.95] text-chrome poster-shadow" style="font-size: {{ $line2Size }}px">{{ $line2 }}</p>@endif
                                @if ($tournament->motto)
                                    <p class="ribbon-gold poster-type {{ $story ? 'mt-5 text-[40px]' : 'mt-3 text-[34px]' }} -rotate-1 px-14 py-2">{{ $tournament->motto }}</p>
                                @endif
                                @if ($tournament->sponsor_logo_path || $tournament->sponsor_name)
                                    <div class="{{ $story ? 'mt-6' : 'mt-3' }} flex flex-col items-center">
                                        @if ($tournament->sponsorLogoUrl())
                                            <img src="{{ $tournament->sponsorLogoUrl() }}" alt="{{ $tournament->sponsor_name }}" class="h-32 w-auto object-contain">
                                        @else
                                            <svg viewBox="0 0 64 56" class="{{ $story ? 'h-16' : 'h-11' }} w-auto poster-shadow" aria-hidden="true"><defs><linearGradient id="g" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#FFE27A"/><stop offset=".5" stop-color="#FFC72C"/><stop offset="1" stop-color="#E79A00"/></linearGradient></defs><path fill="url(#g)" d="M16 2h32v12a16 16 0 0 1-12 15.5V38h8v6H20v-6h8v-8.5A16 16 0 0 1 16 14zM6 6h8v6a8 8 0 0 1-8-6zm44 0h8a8 8 0 0 1-8 6zM14 48h36v6H14z"/></svg>
                                            <p class="mt-1 rounded-xl border-2 border-gold/80 bg-black/70 px-8 py-1.5 font-display {{ $story ? 'text-[30px]' : 'text-[24px]' }} font-extrabold uppercase leading-none tracking-wide">{{ $tournament->sponsor_name }}</p>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            {{-- Fecha y etiqueta --}}
                            <div class="flex w-full flex-col items-center gap-3 px-12">
                                <p class="ribbon-red poster-type w-full {{ $story ? 'py-4 text-[54px]' : 'py-2.5 text-[46px]' }} text-center text-white">{{ $day->translatedFormat('l j \d\e F Y') }}</p>
                                <p class="ribbon-gold poster-type px-20 py-1.5 {{ $story ? 'text-[40px]' : 'text-[34px]' }} not-italic tracking-wider">{{ $label }}</p>
                            </div>

                            {{-- Partidos --}}
                            <div class="flex w-full flex-col {{ $story ? 'gap-16' : ($count <= 2 ? 'gap-8' : 'gap-4') }} px-16">
                                @foreach ($games as $g)
                                    <x-matchup :game="$g" :size="$msize" :link="false" />
                                @endforeach
                            </div>

                            {{-- Sede --}}
                            @if ($venue)
                                <div class="flex w-[92%] items-center gap-4 rounded-2xl border-2 border-gold/80 bg-[#0B1A3A]/90 px-6 py-3 shadow-[0_0_30px_rgba(31,91,216,.45)]">
                                    <svg viewBox="0 0 24 24" class="size-12 shrink-0" aria-hidden="true"><path fill="#E8303D" d="M12 22s7-6.1 7-12a7 7 0 0 0-14 0c0 5.9 7 12 7 12z"/><circle cx="12" cy="10" r="3" fill="#0B101A"/></svg>
                                    <p class="poster-type flex-1 text-center text-[34px] text-white">{{ $venue->name }}@if ($venue->city) <span class="text-gold">•</span> {{ $venue->city }}@endif</p>
                                </div>
                            @endif
                        </div>
                        <img src="{{ asset('assets/branding/hr-logo-horizontal-400.webp') }}" alt="HR Media Score" class="absolute bottom-3 left-1/2 h-8 w-auto -translate-x-1/2 opacity-90">
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.public>
