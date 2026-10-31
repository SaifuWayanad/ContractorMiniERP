@props(['label', 'value', 'icon' => null, 'accent' => 'primary', 'note' => null, 'href' => null])

@php($tag = $href ? 'a' : 'div')
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'card stat-tile h-100'.($href ? ' stat-tile-link' : '')]) }}>
    <div class="d-flex justify-content-between align-items-start gap-2">
        <div class="min-w-0" style="overflow:hidden;">
            <div class="stat-label">{{ $label }}</div>
            <div class="stat-value">{{ $value }}</div>
        </div>
        @if ($icon)
            <span class="kpi-icon kpi-icon-{{ $accent }}"><i class="bi {{ $icon }}"></i></span>
        @endif
    </div>
    @if ($note || $slot->isNotEmpty())
        <div class="stat-note">{{ $note }}{{ $slot }}</div>
    @endif
</{{ $tag }}>
