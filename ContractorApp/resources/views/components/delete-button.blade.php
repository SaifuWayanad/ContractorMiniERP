{{-- A form button (DELETE by default) that asks for confirmation first. Leave :label empty for an icon-only button; :fields adds hidden inputs. --}}
@props([
    'action',
    'label' => 'Delete',
    'confirm' => 'This action cannot be undone.',
    'title' => 'Delete this record?',
    'method' => 'DELETE',
    'variant' => 'danger',
    'button' => null,
    'class' => 'btn btn-outline-danger btn-sm',
    'icon' => 'bi-trash',
    'loadingText' => 'Deleting…',
    'fields' => [],
])

<form action="{{ $action }}" method="post" class="d-inline" data-confirm="{{ $confirm }}" data-confirm-title="{{ $title }}" data-confirm-button="{{ $button ?? ($label ?: 'Delete') }}" data-confirm-variant="{{ $variant }}">
    @csrf
    @method($method)
    @foreach ($fields as $fieldName => $fieldValue)
        <input type="hidden" name="{{ $fieldName }}" value="{{ $fieldValue }}" />
    @endforeach
    <button type="submit" class="{{ $class }}" data-loading-text="{{ $label ? $loadingText : '' }}" aria-label="{{ $button ?? ($label ?: 'Delete') }}">
        <i class="bi {{ $icon }} {{ $label ? 'me-1' : '' }}" aria-hidden="true"></i>{{ $label }}
    </button>
</form>
