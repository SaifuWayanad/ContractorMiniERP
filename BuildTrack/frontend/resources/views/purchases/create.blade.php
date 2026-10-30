@extends('layouts.app')

@section('title', 'Add Purchase')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
                <span><i class="bi bi-cart3 me-2"></i>Record Purchase</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('purchases.store') }}">
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
                                            <div class="cs-item" data-value="{{ $pr['id'] ?? $pr['Id'] ?? '' }}">{{ $pr['projectName'] ?? $pr['ProjectName'] ?? '' }}</div>
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
                            <label class="form-label fw-semibold">Product <span class="text-danger">*</span></label>
                            <div class="cs-wrapper" id="productSelectWrapper">
                                <div class="cs-trigger @error('productId') is-invalid @enderror" tabindex="0">
                                    <span class="cs-value cs-placeholder">Select a product...</span>
                                    <i class="bi bi-chevron-down cs-arrow"></i>
                                </div>
                                <div class="cs-panel">
                                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                                    <div class="cs-list">
                                        @php $productItems = $products['items'] ?? $products['data'] ?? []; @endphp
                                        @forelse($productItems as $prod)
                                            @php $p = is_array($prod) ? $prod : []; @endphp
                                            <div class="cs-item" data-value="{{ $p['id'] ?? $p['Id'] ?? '' }}">{{ $p['productName'] ?? $p['ProductName'] ?? '' }}{{ !empty($p['unitOfMeasure'] ?? $p['UnitOfMeasure'] ?? '') ? ' (' . ($p['unitOfMeasure'] ?? $p['UnitOfMeasure']) . ')' : '' }}</div>
                                        @empty
                                            <div class="cs-item no-data">No products yet</div>
                                        @endforelse
                                    </div>
                                    <div class="cs-footer">
                                        <button type="button" class="cs-add-btn" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                            <i class="bi bi-plus"></i> Add new product
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="productId" id="productSelectInput" value="{{ old('productId') }}">
                            </div>
                            @error('productId')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Purchase Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', date('Y-m-d')) }}" required>
                            @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="quantity" id="qtyInput" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}" required>
                            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Unit Rate <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" name="unitRate" id="rateInput" class="form-control @error('unitRate') is-invalid @enderror" value="{{ old('unitRate') }}" required>
                                @error('unitRate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Line Total</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" id="lineTotalDisplay" class="form-control bg-light fw-bold" readonly placeholder="0.00">
                            </div>
                            <small class="text-muted">Calculated automatically</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Reference / Invoice #</label>
                            <input type="text" name="reference" class="form-control @error('reference') is-invalid @enderror" value="{{ old('reference') }}">
                            @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supplier</label>
                            <input type="text" name="supplier" class="form-control @error('supplier') is-invalid @enderror" value="{{ old('supplier') }}">
                            @error('supplier')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Save Purchase</button>
                        <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="productModalError" class="alert alert-danger d-none"></div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                    <input type="text" id="newProductName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Unit</label>
                    <input type="text" id="newProductUnit" class="form-control" placeholder="pcs, kg, m...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveProductBtn">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

new CustomSelect('projectSelectWrapper');
new CustomSelect('productSelectWrapper');

document.querySelector('form').addEventListener('submit', function(e) {
    const projectId = document.getElementById('projectSelectInput').value;
    const productId = document.getElementById('productSelectInput').value;
    let errors = [];
    if (!projectId) errors.push('Please select a project.');
    if (!productId) errors.push('Please select a product.');
    if (errors.length) {
        e.preventDefault();
        let container = document.getElementById('clientValidationErrors');
        if (!container) {
            container = document.createElement('div');
            container.id = 'clientValidationErrors';
            container.className = 'alert alert-danger mb-3';
            this.querySelector('.row.g-3').before(container);
        }
        container.innerHTML = errors.map(m => `<div>${m}</div>`).join('');
    }
});

// Auto-populate customer from project
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
    } catch(e) { display.value = ''; hidden.value = ''; }
});

// Auto-calculate line total
function calcTotal() {
    const qty = parseFloat(document.getElementById('qtyInput').value) || 0;
    const rate = parseFloat(document.getElementById('rateInput').value) || 0;
    document.getElementById('lineTotalDisplay').value = (qty * rate).toFixed(2);
}
document.getElementById('qtyInput').addEventListener('input', calcTotal);
document.getElementById('rateInput').addEventListener('input', calcTotal);

// Trigger customer populate if project pre-selected
if (document.getElementById('projectSelectInput').value) {
    document.getElementById('projectSelectInput').dispatchEvent(new Event('change'));
}

// Add Product
document.getElementById('saveProductBtn').addEventListener('click', async function() {
    const name = document.getElementById('newProductName').value.trim();
    const errDiv = document.getElementById('productModalError');
    if (!name) { errDiv.textContent = 'Name is required.'; errDiv.classList.remove('d-none'); return; }
    errDiv.classList.add('d-none');
    this.disabled = true;
    try {
        const resp = await fetch('/api-proxy/products', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ productName: name, unitOfMeasure: document.getElementById('newProductUnit').value })
        });
        const data = await resp.json();
        if (!resp.ok) {
            const msg = data.error || (data.errors ? Object.values(data.errors).flat().join(' ') : null) || 'Failed.';
            errDiv.textContent = msg; errDiv.classList.remove('d-none');
        } else {
            const label = (data.productName || data.ProductName) + ((data.unitOfMeasure || data.UnitOfMeasure) ? ' (' + (data.unitOfMeasure || data.UnitOfMeasure) + ')' : '');
            csRegistry['productSelectWrapper'].addOption(data.id || data.Id, label);
            bootstrap.Modal.getInstance(document.getElementById('addProductModal')).hide();
            document.getElementById('newProductName').value = '';
            document.getElementById('newProductUnit').value = '';
        }
    } catch(e) { errDiv.textContent = 'Connection error.'; errDiv.classList.remove('d-none'); }
    this.disabled = false;
});
</script>
@endsection
