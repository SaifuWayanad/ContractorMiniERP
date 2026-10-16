@props(['name', 'label', 'value' => null, 'required' => false, 'rows' => 3, 'col' => 'col-12'])

<div class="{{ $col }}">
    <label for="{{ $name }}" class="form-label">{{ $label }} @if ($required)<span class="req">*</span>@endif</label>
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" @required($required) {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>{{ old($name, $value) }}</textarea>
    @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
