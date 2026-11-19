@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación" class="flex items-center justify-between gap-3 text-sm">
        <p class="text-mute">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}</p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn-secondary btn-sm opacity-40"><x-icon name="chevron-left" class="size-4" /></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" @if(isset($paginator->livewire)) @endif class="btn-secondary btn-sm" rel="prev" aria-label="Anterior"><x-icon name="chevron-left" class="size-4" /></a>
            @endif
            <span class="px-3 text-mute">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn-secondary btn-sm" rel="next" aria-label="Siguiente"><x-icon name="chevron-right" class="size-4" /></a>
            @else
                <span class="btn-secondary btn-sm opacity-40"><x-icon name="chevron-right" class="size-4" /></span>
            @endif
        </div>
    </nav>
@endif
