{{-- GET form for list filters. Selects marked data-autosubmit apply immediately. --}}
@props(['action', 'hasFilters' => false])

<form method="get" action="{{ $action }}" {{ $attributes->merge(['class' => 'card toolbar-card mb-3', 'data-filter-form' => true]) }}>
    <div class="toolbar">
        {{ $slot }}
        <button type="submit" class="btn btn-outline-secondary btn-sm toolbar-apply"><i class="bi bi-funnel me-1"></i>Apply</button>
        @if ($hasFilters)
            <a href="{{ $action }}" class="btn btn-link btn-sm text-decoration-none">Clear</a>
        @endif
    </div>
</form>
