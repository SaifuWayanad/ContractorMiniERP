@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'page-header']) }}>
    <div class="min-w-0">
        @isset($breadcrumbs)
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-1">{{ $breadcrumbs }}</ol>
            </nav>
        @endisset
        <h1 class="page-title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="page-actions">{{ $actions }}</div>
    @endisset
</div>
