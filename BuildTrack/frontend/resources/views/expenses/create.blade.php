@extends('layouts.app')

@section('title', 'Add Expense')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <a href="{{ route('expenses.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
                <span><i class="bi bi-receipt me-2"></i>Record Expense</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('expenses.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Project <span class="text-danger">*</span></label>
                            <div class="cs-wrapper" id="projectSelectWrapper">
                                <div class="cs-trigger @error('projectId') is-invalid @enderror" tabindex="0">
                                    <span class="cs-value cs-placeholder">Select a project...</span>
                                    <i class="bi bi-chevron-down cs-arrow"></i>
                                </div>
                                <div class="cs-panel">
                                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                                    <div class="cs-list">
                                        @php $projectItems = $projects['items'] ?? $projects['data'] ?? []; @endphp
                                        @forelse($projectItems as $proj)
                                            @php $pr = is_array($proj) ? $proj : []; @endphp
                                            <div class="cs-item" data-value="{{ $pr['id'] ?? '' }}">{{ $pr['projectName'] ?? '' }}</div>
                                        @empty
                                            <div class="cs-item no-data">No projects available — create a project first</div>
                                        @endforelse
                                    </div>
                                </div>
                                <input type="hidden" name="projectId" id="projectSelectInput" value="{{ old('projectId', $preselectedProject['id'] ?? '') }}">
                            </div>
                            @error('projectId')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Customer</label>
                            <input type="text" id="customerDisplay" class="form-control bg-light" readonly
                                value="{{ $preselectedProject['customerName'] ?? ($preselectedProject['customer']['name'] ?? '') }}"
                                placeholder="Auto-populated from project">
                            <input type="hidden" name="customerId" id="customerIdInput"
                                value="{{ $preselectedProject['customerId'] ?? ($preselectedProject['customer']['id'] ?? '') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Expense Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', date('Y-m-d')) }}" required>
                            @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                                @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Heading / Description <span class="text-danger">*</span></label>
                            <input type="text" name="heading" class="form-control @error('heading') is-invalid @enderror" value="{{ old('heading') }}" required>
                            @error('heading')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category</label>
                            <input type="text" name="category" class="form-control @error('category') is-invalid @enderror" value="{{ old('category') }}">
                            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Reference</label>
                            <input type="text" name="reference" class="form-control @error('reference') is-invalid @enderror" value="{{ old('reference') }}">
                            @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Save Expense</button>
                        <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
new CustomSelect('projectSelectWrapper');

document.getElementById('projectSelectInput').addEventListener('change', async function() {
    const id = this.value;
    const display = document.getElementById('customerDisplay');
    const hidden = document.getElementById('customerIdInput');
    if (!id) { display.value = ''; hidden.value = ''; return; }
    try {
        const resp = await fetch('/api-proxy/projects/' + id);
        const data = await resp.json();
        display.value = data.customerName || '';
        hidden.value = data.customerId || '';
    } catch(e) {}
});

if (document.getElementById('projectSelectInput').value) {
    document.getElementById('projectSelectInput').dispatchEvent(new Event('change'));
}
</script>
@endsection
