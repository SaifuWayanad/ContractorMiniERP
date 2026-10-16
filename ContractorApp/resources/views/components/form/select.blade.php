{{-- :options is value => label. Selected value comes from old input, then :value. --}}
{{-- :quickCreate is a modal target id (e.g. "#quickCreateCustomer") that adds a "+" button. --}}
@props(['name', 'label', 'options' => [], 'value' => null, 'placeholder' => null, 'required' => false, 'help' => null, 'col' => 'col-md-6 col-xl-4', 'quickCreate' => null])

@php
    $id = $attributes->get('id', $name);
    $selected = (string) old($name, $value instanceof \BackedEnum ? $value->value : $value);
@endphp
<div class="{{ $col }}">
    <label for="{{ $id }}" class="form-label">{{ $label }} @if ($required)<span class="req">*</span>@endif</label>
    @if ($quickCreate)
        <div class="input-group">
            <select id="{{ $id }}" name="{{ $name }}" @required($required) {{ $attributes->except('id')->class(['form-select', 'is-invalid' => $errors->has($name)]) }}>
                @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="{{ $quickCreate }}" title="Quick add">
                <i class="bi bi-plus-lg"></i>
            </button>
            @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @else
        <select id="{{ $id }}" name="{{ $name }}" @required($required) {{ $attributes->except('id')->class(['form-select', 'is-invalid' => $errors->has($name)]) }}>
            @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
        @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
    @endif
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
