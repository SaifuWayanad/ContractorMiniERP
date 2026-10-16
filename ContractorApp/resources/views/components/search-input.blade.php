@props(['value' => null, 'placeholder' => 'Search…', 'name' => 'search'])

<div class="toolbar-search">
    <i class="bi bi-search" aria-hidden="true"></i>
    <input type="search" name="{{ $name }}" value="{{ $value }}" class="form-control" placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}" />
</div>
