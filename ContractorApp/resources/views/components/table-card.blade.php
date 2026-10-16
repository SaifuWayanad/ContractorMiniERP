{{--
    Card wrapping a responsive table with a "Showing X–Y of Z" footer and pagination links.
    Pass the paginator as :paginator; put the <table> in the default slot.
--}}
@props(['paginator' => null, 'title' => null])

<div {{ $attributes->merge(['class' => 'card table-card']) }}>
    @if ($title || isset($headerActions))
        <div class="card-header">
            <h2 class="card-title">{{ $title }}</h2>
            {{ $headerActions ?? '' }}
        </div>
    @endif
    <div class="table-responsive">
        {{ $slot }}
    </div>
    @if ($paginator && $paginator->total() > 0)
        <div class="table-card-footer">
            <span class="text-muted small">Showing {{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }} of {{ number_format($paginator->total()) }} records</span>
            @if ($paginator->hasPages())
                {{ $paginator->onEachSide(1)->links() }}
            @endif
        </div>
    @endif
</div>
