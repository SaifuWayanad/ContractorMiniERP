@extends('layouts.app')

@section('title', 'Edit Payment')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <a href="{{ route('payments.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
                <span><i class="bi bi-pencil me-2"></i>Edit Payment</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('payments.update', $payment['id'] ?? 0) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Project <span class="text-danger">*</span></label>
                            @php $selProject = old('projectId') ? '' : ($payment['projectName'] ?? ''); @endphp
                            <div class="cs-wrapper" id="projectSelectWrapper">
                                <div class="cs-trigger @error('projectId') is-invalid @enderror" tabindex="0">
                                    <span class="cs-value {{ $selProject ? '' : 'cs-placeholder' }}">{{ $selProject ?: 'Select a project...' }}</span>
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
                                            <div class="cs-item no-data">No projects available</div>
                                        @endforelse
                                    </div>
                                </div>
                                <input type="hidden" name="projectId" id="projectSelectInput" value="{{ old('projectId', $payment['projectId'] ?? ($payment['project']['id'] ?? '')) }}">
                            </div>
                            @error('projectId')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Customer</label>
                            <input type="text" id="customerDisplay" class="form-control bg-light" readonly
                                value="{{ $payment['customerName'] ?? ($payment['customer']['name'] ?? '') }}">
                            <input type="hidden" name="customerId" id="customerIdInput"
                                value="{{ $payment['customerId'] ?? ($payment['customer']['id'] ?? '') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                                value="{{ old('date', $payment['date'] ?? $payment['paymentDate'] ?? '') }}" required>
                            @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror"
                                    value="{{ old('amount', $payment['amountReceived'] ?? '') }}" required>
                                @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Method</label>
                            @php $currentMethod = old('method', $payment['method'] ?? $payment['paymentMethod'] ?? ''); @endphp
                            <div class="cs-wrapper" id="methodSelectWrapper">
                                <div class="cs-trigger" tabindex="0">
                                    <span class="cs-value cs-placeholder">Select payment method (optional)...</span>
                                    <i class="bi bi-chevron-down cs-arrow"></i>
                                </div>
                                <div class="cs-panel">
                                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                                    <div class="cs-list">
                                        <div class="cs-item" data-value="">— None —</div>
                                        <div class="cs-item" data-value="cash">Cash</div>
                                        <div class="cs-item" data-value="bank_transfer">Bank Transfer</div>
                                        <div class="cs-item" data-value="cheque">Cheque</div>
                                        <div class="cs-item" data-value="card">Card</div>
                                        <div class="cs-item" data-value="other">Other</div>
                                    </div>
                                </div>
                                <input type="hidden" name="method" id="methodSelectInput" value="{{ $currentMethod }}">
                            </div>
                            @error('method')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Reference</label>
                            <input type="text" name="reference" class="form-control @error('reference') is-invalid @enderror"
                                value="{{ old('reference', $payment['receiptReference'] ?? '') }}">
                            @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $payment['notes'] ?? '') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Update Payment</button>
                        <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Cancel</a>
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
new CustomSelect('methodSelectWrapper');

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
</script>
@endsection
