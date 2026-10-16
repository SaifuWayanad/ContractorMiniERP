@props(['title' => null, 'flush' => false])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title || isset($actions))
        <div class="card-header">
            <h2 class="card-title">{{ $title }}</h2>
            {{ $actions ?? '' }}
        </div>
    @endif
    @if ($flush)
        {{ $slot }}
    @else
        <div class="card-body">{{ $slot }}</div>
    @endif
</div>
