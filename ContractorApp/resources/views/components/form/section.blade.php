@props(['title', 'first' => false])

@unless ($first)
    <hr class="form-divider" />
@endunless
<h2 class="form-section-title">{{ $title }}</h2>
<div {{ $attributes->merge(['class' => 'row g-3 g-lg-4']) }}>
    {{ $slot }}
</div>
