@props(['cancel', 'submit' => 'Save', 'loading' => 'Saving…'])

<div class="form-actions">
    <a href="{{ $cancel }}" class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary" data-loading-text="{{ $loading }}"><i class="bi bi-check-lg me-1"></i>{{ $submit }}</button>
</div>
