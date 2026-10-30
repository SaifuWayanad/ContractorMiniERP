@extends('layouts.app')

@section('title', 'Edit Purchase')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
                <span><i class="bi bi-pencil me-2"></i>Edit Purchase</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('purchases.update', $purchase['id'] ?? 0) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Project <span class="text-danger">*</span></label>
                            @php $selProject = old('projectId') ? '' : ($purchase['projectName'] ?? ''); @endphp
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
                                <input type="hidden" name="projectId" id="projectSelectInput" value="{{ old('projectId', $purchase['projectId'] ?? ($purchase['project']['id'] ?? '')) }}">
                            </div>
                            @error('projectId')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Customer</label>
                            <input type="text" id="customerDisplay" class="form-control bg-light" readonly
                                value="{{ $purchase['customerName'] ?? ($purchase['customer']['name'] ?? '') }}">
                            <input type="hidden" name="customerId" id="customerIdInput"
                                value="{{ $purchase['customerId'] ?? ($purchase['customer']['id'] ?? '') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Product <span class="text-danger">*</span></label>
                            @php $selProduct = old('productId') ? '' : ($purchase['productName'] ?? ''); @endphp
                            <div class="cs-wrapper" id="productSelectWrapper">
                                <div class="cs-trigger @error('productId') is-invalid @enderror" tabindex="0">
                                    <span class="cs-value {{ $selProduct ? '' : 'cs-placeholder' }}">{{ $selProduct ?: 'Select a product...' }}</span>
                                    <i class="bi bi-chevron-down cs-arrow"></i>
                                </div>
                                <div class="cs-panel">
                                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                                    <div class="cs-list">
                                        @php $productItems = $products['items'] ?? $products['data'] ?? []; @endphp
                                        @forelse($productItems as $prod)
                                            @php $p = is_array($prod) ? $prod : []; @endphp
                                            <div class="cs-item" data-value="{{ $p['id'] ?? '' }}">{{ $p['productName'] ?? '' }}{{ !empty($p['unitOfMeasure']) ? ' (' . $p['unitOfMeasure'] . ')' : '' }}</div>
                                        @empty
                                            <div class="cs-item no-data">No products available</div>
                                        @endforelse
                                    </div>
                                </div>
                                <input type="hidden" name="productId" id="productSelectInput" value="{{ old('productId', $purchase['productId'] ?? ($purchase['product']['id'] ?? '')) }}">
                            </div>
                            @error('productId')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Purchase Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                                value="{{ old('date', isset($purchase['purchaseDate']) ? substr($purchase['purchaseDate'], 0, 10) : ($purchase['date'] ?? '')) }}" required>
                            @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Quantity</label>
                            <input type="number" step="0.01" name="quantity" id="qtyInput" class="form-control @error('quantity') is-invalid @enderror"
                                value="{{ old('quantity', $purchase['quantity'] ?? '') }}">
                            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Unit Rate</label>
                            <input type="number" step="0.01" name="unitRate" id="rateInput" class="form-control @error('unitRate') is-invalid @enderror"
                                value="{{ old('unitRate', $purchase['unitAmount'] ?? '') }}">
                            @error('unitRate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Line Total</label>
                            <input type="number" step="0.01" name="lineTotal" id="lineTotalDisplay" class="form-control fw-bold"
                                value="{{ old('lineTotal', $purchase['lineTotal'] ?? '') }}">
                            <small class="text-muted">Auto-calculated; you can override</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Reference</label>
                            <input type="text" name="reference" class="form-control @error('reference') is-invalid @enderror"
                                value="{{ old('reference', $purchase['invoiceReference'] ?? '') }}">
                            @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supplier</label>
                            <input type="text" name="supplier" class="form-control @error('supplier') is-invalid @enderror"
                                value="{{ old('supplier', $purchase['supplier'] ?? '') }}">
                            @error('supplier')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $purchase['notes'] ?? '') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <input type="hidden" name="from" value="{{ $from ?? '' }}">
                    <input type="hidden" name="fromProjectId" value="{{ $fromProjectId ?? '' }}">
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Update Purchase</button>
                        @if(!empty($from) && $from === 'project' && !empty($fromProjectId))
                            <a href="{{ route('projects.show', $fromProjectId) }}" class="btn btn-outline-secondary">Cancel</a>
                        @else
                            <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        @endif
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
new CustomSelect('productSelectWrapper');

function calcTotal() {
    const qty = parseFloat(document.getElementById('qtyInput').value) || 0;
    const rate = parseFloat(document.getElementById('rateInput').value) || 0;
    document.getElementById('lineTotalDisplay').value = (qty * rate).toFixed(2);
}
document.getElementById('qtyInput').addEventListener('input', calcTotal);
document.getElementById('rateInput').addEventListener('input', calcTotal);
calcTotal();

async function fetchCustomer(id) {
    const display = document.getElementById('customerDisplay');
    const hidden = document.getElementById('customerIdInput');
    if (!id) { display.value = ''; hidden.value = ''; return; }
    try {
        const resp = await fetch('/api-proxy/projects/' + id);
        const data = await resp.json();
        display.value = data.customerName || '';
        hidden.value = data.customerId || '';
    } catch(e) {}
}
document.getElementById('projectSelectInput').addEventListener('change', function() {
    fetchCustomer(this.value);
});
// On load: if customer display is empty but project is set, fetch it
const _initProjId = document.getElementById('projectSelectInput').value;
if (_initProjId && !document.getElementById('customerDisplay').value) {
    fetchCustomer(_initProjId);
}
</script>
@endsection
