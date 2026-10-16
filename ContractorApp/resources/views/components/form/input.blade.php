@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'help' => null, 'col' => 'col-md-6 col-xl-4', 'prepend' => null])

@php($id = $attributes->get('id', str_replace(['[', ']'], ['_', ''], $name)))
<div class="{{ $col }}">
    <label for="{{ $id }}" class="form-label">{{ $label }} @if ($required)<span class="req">*</span>@endif</label>
    @if ($prepend)
        <div @class(['input-group', 'has-validation', 'is-invalid' => $errors->has($name)])>
            <span class="input-group-text">{!! $prepend !!}</span>
    @endif
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @required($required)
        {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $errors->has($name)]) }} />
    @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
    @if ($prepend)
        </div>
    @endif
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
