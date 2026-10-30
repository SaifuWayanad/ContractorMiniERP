@extends('layouts.app')

@section('title', 'Edit Purchase')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <a href="{{ route('projects.show', $purchase->project_id) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
                <i class="bi bi-cart3"></i>
                <span class="fw-semibold">Edit Purchase</span>
            </div>
            <div class="card-body">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif

                <form method="post" action="{{ route('purchases.update', $purchase) }}" id="purchaseForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="from" value="project">

                    <div class="row g-3">

                        {{-- Project --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Project <span class="text-danger">*</span></label>
                            <div class="cs-wrapper" id="projectSelectWrapper">
                                <div class="cs-trigger @error('project_id') is-invalid @enderror">
                                    <span class="cs-value cs-placeholder">Select a project...</span>
                                    <i class="bi bi-chevron-down cs-arrow"></i>
                                </div>
                                <div class="cs-panel">
                                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                                    <div class="cs-list">
                                        @forelse($projects as $id => $name)
                                            <div class="cs-item" data-value="{{ $id }}">{{ $name }}</div>
                                        @empty
                                            <div class="cs-item no-data">No open projects</div>
                                        @endforelse
                                    </div>
                                </div>
                                <input type="hidden" name="project_id" id="projectSelectInput" value="{{ old('project_id', $purchase->project_id) }}">
                            </div>
                            @error('project_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        {{-- Customer (auto-populated) --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Customer</label>
                            <input type="text" id="customerDisplay" class="form-control" readonly placeholder="Auto-populated from project" style="background:#f8f9fa;"
                                value="{{ $purchase->project?->client?->name ?? '' }}">
                        </div>

                        {{-- Product --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Product</label>
                            <div class="cs-wrapper" id="productSelectWrapper">
                                <div class="cs-trigger @error('product_id') is-invalid @enderror">
                                    <span class="cs-value cs-placeholder">Select a product...</span>
                                    <i class="bi bi-chevron-down cs-arrow"></i>
                                </div>
                                <div class="cs-panel">
                                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                                    <div class="cs-list" id="productCsList">
                                        @forelse($products as $id => $name)
                                            <div class="cs-item" data-value="{{ $id }}">{{ $name }}</div>
                                        @empty
                                            <div class="cs-item no-data" id="productNoData">No products yet</div>
                                        @endforelse
                                    </div>
                                    <div class="cs-footer">
                                        <button type="button" class="cs-add-btn" id="openAddProductModal">
                                            <i class="bi bi-plus-circle"></i> Add new product
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="product_id" id="productSelectInput" value="{{ old('product_id', $purchase->product_id) }}">
                            </div>
                            @error('product_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        {{-- Purchase Date --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Purchase Date <span class="text-danger">*</span></label>
                            <input type="date" name="purchase_date" class="form-control @error('purchase_date') is-invalid @enderror"
                                value="{{ old('purchase_date', $purchase->purchase_date?->format('Y-m-d')) }}" required>
                            @error('purchase_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Quantity --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Quantity</label>
                            <input type="number" step="0.01" name="quantity" id="qtyInput"
                                class="form-control @error('quantity') is-invalid @enderror"
                                value="{{ old('quantity', $purchase->quantity) }}" placeholder="0">
                            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Unit Rate --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Unit Rate</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" name="unit_amount" id="rateInput"
                                    class="form-control @error('unit_amount') is-invalid @enderror"
                                    value="{{ old('unit_amount', $purchase->unit_amount) }}" placeholder="0.00">
                            </div>
                            @error('unit_amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        {{-- Line Total --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Line Total</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" name="line_total" id="lineTotalInput"
                                    class="form-control fw-bold"
                                    value="{{ old('line_total', $purchase->line_total) }}" placeholder="0.00">
                            </div>
                            <div class="text-muted small mt-1">Calculated automatically</div>
                        </div>

                        {{-- Reference / Invoice --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Reference / Invoice #</label>
                            <input type="text" name="invoice_reference" class="form-control @error('invoice_reference') is-invalid @enderror"
                                value="{{ old('invoice_reference', $purchase->invoice_reference) }}">
                            @error('invoice_reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Supplier --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supplier</label>
                            <input type="text" name="supplier" class="form-control @error('supplier') is-invalid @enderror"
                                value="{{ old('supplier', $purchase->supplier) }}" maxlength="200">
                            @error('supplier')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Notes --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" maxlength="5000">{{ old('notes', $purchase->notes) }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Save Changes</button>
                        <a href="{{ route('projects.show', $purchase->project_id) }}" class="btn btn-outline-secondary">Cancel</a>
                        @can('delete', $purchase)
                            <form method="post" action="{{ route('purchases.destroy', $purchase) }}" class="ms-auto">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this purchase?')"><i class="bi bi-trash me-1"></i>Delete</button>
                            </form>
                        @endcan
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

{{-- Add New Product Modal --}}
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="addProductModalLabel">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="addProductError" class="alert alert-danger d-none"></div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                    <input type="text" id="newProductName" class="form-control" placeholder="Product name">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Unit</label>
                    <input type="text" id="newProductUnit" class="form-control" placeholder="pcs, kg, m...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveNewProduct">
                    <span id="saveProductSpinner" class="spinner-border spinner-border-sm me-1 d-none"></span>
                    Save
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const projectsData = @json($projectsJson);
    const productsData = @json($productsJson);

    const projectCs = new CustomSelect('projectSelectWrapper');
    const productCs = new CustomSelect('productSelectWrapper');

    const projectInput    = document.getElementById('projectSelectInput');
    const customerDisplay = document.getElementById('customerDisplay');
    const qtyInput        = document.getElementById('qtyInput');
    const rateInput       = document.getElementById('rateInput');
    const totalInput      = document.getElementById('lineTotalInput');

    projectInput.addEventListener('change', function () {
        const data = projectsData[this.value];
        customerDisplay.value = data ? data.client : '';
    });

    function calcTotal() {
        const qty  = parseFloat(qtyInput.value)  || 0;
        const rate = parseFloat(rateInput.value) || 0;
        if (qty > 0 || rate > 0) {
            totalInput.value = (qty * rate).toFixed(2);
        }
    }
    qtyInput.addEventListener('input', calcTotal);
    rateInput.addEventListener('input', calcTotal);

    // Trigger customer populate on load
    if (projectInput.value) {
        projectInput.dispatchEvent(new Event('change'));
    }

    // Add New Product modal
    const addProductModal = new bootstrap.Modal(document.getElementById('addProductModal'));

    document.getElementById('openAddProductModal').addEventListener('click', function (e) {
        e.stopPropagation();
        addProductModal.show();
        document.getElementById('newProductName').value = '';
        document.getElementById('newProductUnit').value = '';
        document.getElementById('addProductError').classList.add('d-none');
    });

    document.getElementById('saveNewProduct').addEventListener('click', function () {
        const name = document.getElementById('newProductName').value.trim();
        if (!name) {
            document.getElementById('addProductError').textContent = 'Product name is required.';
            document.getElementById('addProductError').classList.remove('d-none');
            return;
        }
        const spinner = document.getElementById('saveProductSpinner');
        spinner.classList.remove('d-none');
        this.disabled = true;

        fetch('{{ route('products.quick-store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ name, unit: document.getElementById('newProductUnit').value.trim() }),
        })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            spinner.classList.add('d-none');
            document.getElementById('saveNewProduct').disabled = false;
            if (!ok) {
                const msg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Error saving product.');
                document.getElementById('addProductError').textContent = msg;
                document.getElementById('addProductError').classList.remove('d-none');
                return;
            }
            productsData[data.id] = { unit: data.unit, rate: data.rate };
            productCs.addOption(data.id, data.name, true);
            addProductModal.hide();
        })
        .catch(() => {
            spinner.classList.add('d-none');
            document.getElementById('saveNewProduct').disabled = false;
            document.getElementById('addProductError').textContent = 'Network error. Please try again.';
            document.getElementById('addProductError').classList.remove('d-none');
        });
    });

    document.getElementById('newProductName').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') document.getElementById('saveNewProduct').click();
    });
})();
</script>
@endpush
