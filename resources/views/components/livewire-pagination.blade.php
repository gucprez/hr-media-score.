@props(['paginator'])
@if ($paginator->hasPages())
    <nav aria-label="Paginación" class="flex items-center justify-between gap-3 text-sm">
        <p class="text-mute">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }}</p>
        <div class="flex items-center gap-1">
            <button type="button" wire:click="previousPage" @disabled($paginator->onFirstPage()) class="btn-secondary btn-sm" aria-label="Anterior"><x-icon name="chevron-left" class="size-4" /></button>
            <span class="px-3 text-mute">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
            <button type="button" wire:click="nextPage" @disabled(! $paginator->hasMorePages()) class="btn-secondary btn-sm" aria-label="Siguiente"><x-icon name="chevron-right" class="size-4" /></button>
        </div>
    </nav>
@endif
