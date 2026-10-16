@props(['icon' => 'bi-inbox', 'title', 'message' => null])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <i class="bi {{ $icon }}" aria-hidden="true"></i>
    <h2>{{ $title }}</h2>
    @if ($message)
        <p>{{ $message }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="d-flex flex-wrap gap-2 justify-content-center">{{ $slot }}</div>
    @endif
</div>
