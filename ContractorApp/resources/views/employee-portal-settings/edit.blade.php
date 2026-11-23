@extends('layouts.app')

@section('title', 'Employee Portal Settings')

@section('content')
<x-page-header title="Employee Portal Settings" subtitle="Choose which sections are visible to employees when they log in." />

<form method="post" action="{{ route('employee-portal-settings.update') }}">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <div class="col-lg-8">
            <x-card>
                <x-slot:title>Visible Sections</x-slot:title>

                <p class="text-muted small mb-4">Toggle the sections you want employees to see in their portal. Dashboard is always enabled.</p>

                <div class="row g-3">
                    @foreach($allSections as $slug => $section)
                    @php $enabled = in_array($slug, $enabledSections); $isFixed = $slug === 'dashboard'; @endphp
                    <div class="col-sm-6">
                        <label class="d-flex align-items-start gap-3 p-3 rounded-3 border {{ $enabled ? 'border-primary bg-primary bg-opacity-10' : 'bg-light' }}" style="cursor:{{ $isFixed ? 'default' : 'pointer' }};transition:all .15s" for="section_{{ $slug }}">
                            <div class="pt-1">
                                @if($isFixed)
                                    <input type="checkbox" class="form-check-input" checked disabled style="pointer-events:none">
                                    <input type="hidden" name="sections[{{ $slug }}]" value="1">
                                @else
                                    <input type="checkbox" class="form-check-input" id="section_{{ $slug }}" name="sections[{{ $slug }}]" value="1" {{ $enabled ? 'checked' : '' }}>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="bi {{ $section['icon'] }} {{ $enabled ? 'text-primary' : 'text-muted' }}"></i>
                                    <span class="fw-semibold {{ $enabled ? '' : 'text-muted' }}">{{ $section['label'] }}</span>
                                    @if($isFixed)
                                        <span class="badge bg-secondary ms-1" style="font-size:.65rem">Always on</span>
                                    @endif
                                </div>
                                <div class="small text-muted">{{ $section['description'] }}</div>
                            </div>
                        </label>
                    </div>
                    @endforeach
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Settings</button>
                </div>
            </x-card>
        </div>

        <div class="col-lg-4">
            <x-card>
                <x-slot:title>How it works</x-slot:title>
                <ul class="list-unstyled small text-muted mb-0" style="line-height:1.9">
                    <li><i class="bi bi-check-circle text-success me-2"></i>Enabled sections appear in the employee's sidebar</li>
                    <li><i class="bi bi-x-circle text-danger me-2"></i>Disabled sections are completely hidden</li>
                    <li><i class="bi bi-lock text-secondary me-2"></i>Employees can only see their own records</li>
                    <li><i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard is always visible</li>
                </ul>
            </x-card>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.querySelectorAll('.form-check-input:not([disabled])').forEach(function (cb) {
    cb.addEventListener('change', function () {
        var card = this.closest('label');
        if (this.checked) {
            card.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
            card.classList.remove('bg-light');
            card.querySelector('i.bi').classList.add('text-primary');
            card.querySelector('i.bi').classList.remove('text-muted');
            card.querySelector('.fw-semibold').classList.remove('text-muted');
        } else {
            card.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
            card.classList.add('bg-light');
            card.querySelector('i.bi').classList.remove('text-primary');
            card.querySelector('i.bi').classList.add('text-muted');
            card.querySelector('.fw-semibold').classList.add('text-muted');
        }
    });
});
</script>
@endpush
@endsection
