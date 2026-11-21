{{-- Enfrentamiento estilo cartelera: local (rojo) vs visitante (azul). size: md | lg | poster --}}
@props(['game', 'size' => 'md', 'link' => true])
@php
    $home = $game->homeTeam; $away = $game->awayTeam;
    $done = $game->status->isFinished();
    $live = $game->status === \App\Enums\GameStatus::Live;
    $showScore = $done || $live;
    $cfg = [
        'md' => ['badge' => 'lg', 'badgeMobile' => 'max-sm:size-12! max-sm:text-xs!', 'name' => 'text-xs sm:text-base', 'vs' => 'text-2xl sm:text-3xl', 'time' => 'text-[10px] sm:text-xs px-2.5 sm:px-3 py-1', 'score' => 'text-2xl sm:text-4xl', 'gap' => 'gap-2 sm:gap-4'],
        'lg' => ['badge' => 'xl', 'badgeMobile' => 'max-sm:size-16! max-sm:text-base!', 'name' => 'text-xs sm:text-xl', 'vs' => 'text-4xl sm:text-5xl', 'time' => 'text-[11px] sm:text-sm px-3 sm:px-4 py-1 sm:py-1.5', 'score' => 'text-4xl sm:text-6xl', 'gap' => 'gap-3 sm:gap-6'],
        'poster-sm' => ['badge' => 'xl', 'name' => 'text-[26px]', 'vs' => 'text-[66px] leading-none', 'time' => 'text-[26px] px-6 py-1.5', 'score' => 'text-[76px] leading-none', 'gap' => 'gap-5'],
        'poster' => ['badge' => '2xl', 'name' => 'text-[34px]', 'vs' => 'text-[96px] leading-none', 'time' => 'text-[34px] px-8 py-2', 'score' => 'text-[110px] leading-none', 'gap' => 'gap-6'],
    ][$size];
    $tag = $link ? 'a' : 'div';
@endphp
<{{ $tag }} @if($link) href="{{ route('public.games.show', $game) }}" @endif {{ $attributes->merge(['class' => "group grid grid-cols-[1fr_auto_1fr] items-center {$cfg['gap']}"]) }}>
    @foreach (['home' => $home, 'away' => $away] as $side => $reg)
        @if ($side === 'away')
            {{-- Centro --}}
            <div class="flex flex-col items-center gap-1.5 text-center">
                @if ($showScore)
                    <div class="flex items-center gap-2 sm:gap-3">
                        <span @class(['font-score', $cfg['score'], 'text-white' => ! $done || $game->home_score > $game->away_score, 'text-white/45' => $done && $game->home_score < $game->away_score])>{{ $game->home_score }}</span>
                        <span class="vs-mark {{ str_starts_with($size, 'poster') ? 'text-[56px]' : 'text-xl' }}">–</span>
                        <span @class(['font-score', $cfg['score'], 'text-white' => ! $done || $game->away_score > $game->home_score, 'text-white/45' => $done && $game->away_score < $game->home_score])>{{ $game->away_score }}</span>
                    </div>
                    <span class="ribbon-red poster-type {{ $cfg['time'] }} text-white">{{ $live ? 'EN VIVO' : ($game->status === \App\Enums\GameStatus::Forfeit ? 'FORFEIT' : 'FINAL') }}</span>
                @else
                    <span class="ribbon-red poster-type whitespace-nowrap {{ $cfg['time'] }} text-white">{{ $game->scheduled_at && $game->scheduled_at->format('H:i') !== '00:00' ? $game->scheduled_at->format('g:i A') : 'POR CONFIRMAR' }}</span>
                    <span class="vs-mark {{ $cfg['vs'] }} pr-1">VS</span>
                @endif
            </div>
        @endif
        <div class="flex min-w-0 flex-col items-center gap-2 text-center sm:gap-3">
            <span class="relative rounded-full transition duration-300 group-hover:scale-105 {{ $side === 'home' ? 'glow-home' : 'glow-away' }}">
                <x-team-badge :team="$reg->team" :size="$cfg['badge']" class="rounded-full! ring-0! {{ $cfg['badgeMobile'] ?? '' }}" />
            </span>
            <span class="plate {{ $side === 'home' ? 'plate-home' : 'plate-away' }} {{ $cfg['name'] }} w-full max-w-[16rem] {{ str_starts_with($size, 'poster') ? 'truncate' : 'line-clamp-2 break-words leading-tight max-sm:px-3' }} {{ $size === 'poster' ? 'max-w-[400px] px-10 py-3' : ($size === 'poster-sm' ? 'max-w-[340px] px-8 py-2' : '') }}">{{ $reg->displayName() }}</span>
        </div>
    @endforeach
</{{ $tag }}>
