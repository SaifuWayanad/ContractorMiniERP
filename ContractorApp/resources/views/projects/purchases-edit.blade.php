@extends('layouts.app')

@section('title', 'Edit Purchase — ' . $project->name)

@section('content')
<x-page-header title="Edit Purchase" :subtitle="$project->name . ' · ' . $project->project_number">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
        <li class="breadcrumb-item"><a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a></li>
        <li class="breadcrumb-item active">Edit Purchase #{{ $purchase->id }}</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('projects.update-purchase', [$project, $purchase]) }}" id="purchasesForm">
    @csrf
    @method('PUT')
    <input type="hidden" name="from" value="{{ request('from') }}">

    {{-- Date / project header --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Purchase Date <span class="text-danger">*</span></label>
                    <input type="date" name="purchase_date"
                        class="form-control @error('purchase_date') is-invalid @enderror"
                        value="{{ old('purchase_date', $purchase->purchase_date?->format('Y-m-d')) }}" required>
                    @error('purchase_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold text-muted">Project</label>
                    <p class="mb-0 fw-semibold">{{ $project->name }}
                        <span class="text-muted fw-normal ms-1">({{ $project->project_number }})</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Product list card --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold">Product List</span>
            <button type="button" class="btn btn-primary btn-sm" id="addProductRowBtn">
                <i class="bi bi-plus-circle me-1"></i>Add Product
            </button>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0" style="min-width:{{ $isMultiProject ? '900px' : '680px' }}">
                <thead class="table-light">
                    <tr>
                        @if($isMultiProject)
                            <th style="min-width:190px">Project <span class="text-danger">*</span></th>
                        @endif
                        <th>Select Product</th>
                        <th style="width:110px" class="text-end">Qty</th>
                        <th style="width:140px" class="text-end">Unit Price (₹)</th>
                        <th style="width:150px" class="text-end">Line Total (₹) <span class="text-danger">*</span></th>
                        <th style="width:44px"></th>
                    </tr>
                </thead>
                <tbody id="productRows"></tbody>
            </table>
        </div>

        <div id="emptyHint" class="p-4 text-center text-muted small" style="display:none">
            <i class="bi bi-cart3 me-2"></i>Click <strong>Add Product</strong> to start adding items.
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="fw-semibold fs-6">
                Total: <span id="grandTotal" class="text-success ms-1">₹0.00</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary btn-sm">Cancel</a>
                <button type="submit" class="btn btn-primary btn-sm" id="saveAllBtn" disabled>
                    <i class="bi bi-check-circle me-1"></i>Save Changes
                </button>
            </div>
        </div>
    </div>

</form>

{{-- Quick-add product modal (shared) --}}
<div class="modal fade" id="quickAddProductModal" tabindex="-1" aria-labelledby="qapModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="qapModalLabel">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="qapError" class="alert alert-danger d-none"></div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                    <input type="text" id="qapName" class="form-control" placeholder="e.g. Cement, Steel Rod…" maxlength="150">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Unit</label>
                    <input type="text" id="qapUnit" class="form-control" placeholder="e.g. bag, kg, m…" maxlength="50">
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Default Rate (₹)</label>
                    <input type="number" id="qapRate" class="form-control" placeholder="0.00" min="0" step="0.01">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="qapSaveBtn">
                    <span id="qapSpinner" class="spinner-border spinner-border-sm me-1 d-none"></span>
                    Add &amp; Select
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var PRODUCTS        = @json($products);
    var EXISTING_ITEMS  = @json($existingItems);
    var IS_MULTI        = @json($isMultiProject);
    var PROJECTS        = @json($projectsJson);
    var STORE_URL       = '{{ route('products.quick-store') }}';
    var CSRF            = document.querySelector('meta[name="csrf-token"]').content;

    var prodMap = {};
    PRODUCTS.forEach(function (p) { prodMap[p.id] = { unit: p.unit, rate: p.rate }; });

    var rowCount    = 0;
    var csInstances = [];
    var activeCs    = null;

    var tbody      = document.getElementById('productRows');
    var emptyHint  = document.getElementById('emptyHint');
    var totalEl    = document.getElementById('grandTotal');
    var saveAllBtn = document.getElementById('saveAllBtn');

    /* ── Quick-add product modal ── */
    var qapModal   = new bootstrap.Modal(document.getElementById('quickAddProductModal'));
    var qapName    = document.getElementById('qapName');
    var qapUnit    = document.getElementById('qapUnit');
    var qapRate    = document.getElementById('qapRate');
    var qapError   = document.getElementById('qapError');
    var qapSpinner = document.getElementById('qapSpinner');
    var qapSaveBtn = document.getElementById('qapSaveBtn');

    document.getElementById('quickAddProductModal').addEventListener('hidden.bs.modal', function () {
        qapName.value = ''; qapUnit.value = ''; qapRate.value = '';
        qapError.classList.add('d-none'); qapError.textContent = '';
        qapSaveBtn.disabled = false; qapSpinner.classList.add('d-none');
    });

    qapSaveBtn.addEventListener('click', function () {
        var name = qapName.value.trim();
        qapError.classList.add('d-none');
        if (!name) { qapError.textContent = 'Product name is required.'; qapError.classList.remove('d-none'); qapName.focus(); return; }
        qapSaveBtn.disabled = true;
        qapSpinner.classList.remove('d-none');

        fetch(STORE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ name: name, unit: qapUnit.value.trim() || null, default_rate: parseFloat(qapRate.value) || 0 }),
        })
        .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
        .then(function (res) {
            qapSaveBtn.disabled = false; qapSpinner.classList.add('d-none');
            if (!res.ok) {
                var msg = res.data.errors ? Object.values(res.data.errors).flat().join(' ') : (res.data.message || 'Error saving product.');
                qapError.textContent = msg; qapError.classList.remove('d-none'); return;
            }
            var d = res.data;
            var label = d.name + (d.unit ? ' (' + d.unit + ')' : '');
            PRODUCTS.push({ id: d.id, name: d.name, unit: d.unit || '', rate: parseFloat(d.rate) || 0 });
            prodMap[d.id] = { unit: d.unit || '', rate: parseFloat(d.rate) || 0 };
            csInstances.forEach(function (cs) { cs.addOption(String(d.id), label, false); });
            if (activeCs) activeCs._select(String(d.id), label);
            qapModal.hide();
        })
        .catch(function () {
            qapSaveBtn.disabled = false; qapSpinner.classList.add('d-none');
            qapError.textContent = 'Network error. Please try again.'; qapError.classList.remove('d-none');
        });
    });

    qapName.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); qapSaveBtn.click(); }
        if (e.key === 'Escape') { qapModal.hide(); }
    });

    /* ── Grand total ── */
    function fmtINR(n) { return '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function recalcTotal() {
        var sum = 0;
        tbody.querySelectorAll('.pr-total').forEach(function (el) { sum += parseFloat(el.value) || 0; });
        totalEl.textContent = fmtINR(sum);
        saveAllBtn.disabled = sum <= 0;
    }

    /* ── Project CS HTML builder (multi-project mode) ── */
    function buildProjectCsHtml(idx) {
        var items = '';
        PROJECTS.forEach(function (p) {
            var label = p.name + (p.number ? ' (' + p.number + ')' : '');
            items += '<div class="cs-item" data-value="' + p.id + '">' + label + '</div>';
        });
        if (!items) items = '<div class="cs-item no-data">No projects</div>';
        return (
            '<div class="cs-wrapper" id="projCs_' + idx + '">' +
              '<div class="cs-trigger"><span class="cs-value cs-placeholder">Select project…</span><i class="bi bi-chevron-down cs-arrow"></i></div>' +
              '<div class="cs-panel">' +
                '<div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>' +
                '<div class="cs-list">' + items + '</div>' +
              '</div>' +
              '<input type="hidden" name="items[' + idx + '][project_id]" id="projInput_' + idx + '" value="">' +
            '</div>'
        );
    }

    /* ── Product CS HTML builder ── */
    function buildCsHtml(idx) {
        var items = '';
        PRODUCTS.forEach(function (p) {
            items += '<div class="cs-item" data-value="' + p.id + '">' + p.name + (p.unit ? ' (' + p.unit + ')' : '') + '</div>';
        });
        if (!items) items = '<div class="cs-item no-data">No products yet</div>';
        return (
            '<div class="cs-wrapper" id="prodCs_' + idx + '">' +
              '<div class="cs-trigger"><span class="cs-value cs-placeholder">Select product…</span><i class="bi bi-chevron-down cs-arrow"></i></div>' +
              '<div class="cs-panel">' +
                '<div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>' +
                '<div class="cs-list">' + items + '</div>' +
                '<div class="cs-footer"><button type="button" class="cs-add-btn prod-quick-add"><i class="bi bi-plus-circle"></i> Add new product</button></div>' +
              '</div>' +
              '<input type="hidden" name="items[' + idx + '][product_id]" id="prodInput_' + idx + '" value="">' +
            '</div>'
        );
    }

    /* ── Fixed-panel override ── */
    function makeFixed(cs) {
        var panel = cs.wrapper.querySelector('.cs-panel');
        var origOpen = cs._open.bind(cs), origClose = cs._close.bind(cs);
        function reposition() {
            var rect = cs.trigger.getBoundingClientRect();
            panel.style.position = 'fixed'; panel.style.top = rect.bottom + 'px';
            panel.style.left = rect.left + 'px'; panel.style.right = 'auto';
            panel.style.width = rect.width + 'px'; panel.style.zIndex = '9999';
        }
        cs._open  = function () { origOpen(); reposition(); };
        cs._close = function () { origClose(); panel.style.position = panel.style.top = panel.style.left = panel.style.right = panel.style.width = panel.style.zIndex = ''; };
        window.addEventListener('scroll', function () { if (cs.wrapper.classList.contains('open')) reposition(); }, { passive: true });
        window.addEventListener('resize', function () { if (cs.wrapper.classList.contains('open')) reposition(); }, { passive: true });
    }

    /* ── Add row (with optional prefill) ── */
    function addRow(prefill) {
        prefill = prefill || {};
        var idx = rowCount++;
        if (emptyHint) emptyHint.style.display = 'none';

        var tr = document.createElement('tr');
        tr.innerHTML =
            (IS_MULTI ? '<td>' + buildProjectCsHtml(idx) + '</td>' : '') +
            '<td>' + buildCsHtml(idx) + '</td>' +
            '<td><input type="number" name="items[' + idx + '][quantity]" class="form-control form-control-sm text-end pr-qty" min="0" step="any"></td>' +
            '<td><input type="number" name="items[' + idx + '][unit_amount]" class="form-control form-control-sm text-end pr-rate" min="0" step="0.01"></td>' +
            '<td><input type="number" name="items[' + idx + '][line_total]" class="form-control form-control-sm text-end fw-semibold pr-total" placeholder="0.00" min="0" step="0.01"></td>' +
            '<td class="text-center"><button type="button" class="btn-action text-danger pr-remove" title="Remove"><i class="bi bi-x-lg"></i></button></td>';

        tbody.appendChild(tr);

        /* Project CS (multi-project mode only) */
        var projCs    = null;
        var projInput = null;
        if (IS_MULTI) {
            projCs    = new CustomSelect('projCs_' + idx);
            makeFixed(projCs);
            projInput = document.getElementById('projInput_' + idx);
            var origProjSel = projCs._select.bind(projCs);
            projCs._select = function (v, l) { origProjSel(v, l); if (projInput) projInput.value = v; };
        }

        var cs       = new CustomSelect('prodCs_' + idx);
        makeFixed(cs);
        var hidden   = document.getElementById('prodInput_' + idx);
        var qtyEl    = tr.querySelector('.pr-qty');
        var rateEl   = tr.querySelector('.pr-rate');
        var totalInp = tr.querySelector('.pr-total');

        var origSel = cs._select.bind(cs);
        cs._select = function (v, l) {
            origSel(v, l);
            if (hidden) hidden.value = v;
            var info = prodMap[v];
            if (info && info.rate > 0 && !rateEl.value) { rateEl.value = info.rate.toFixed(2); autoCalc(); }
        };
        csInstances.push(cs);

        tr.querySelector('.prod-quick-add').addEventListener('click', function (e) {
            e.stopPropagation(); cs._close(); activeCs = cs;
            qapModal.show(); setTimeout(function () { qapName.focus(); }, 300);
        });

        function autoCalc() {
            var qty = parseFloat(qtyEl.value) || 0, rate = parseFloat(rateEl.value) || 0;
            if (qty > 0 && rate > 0) totalInp.value = (qty * rate).toFixed(2);
            recalcTotal();
        }
        qtyEl.addEventListener('input', autoCalc);
        rateEl.addEventListener('input', autoCalc);
        totalInp.addEventListener('input', recalcTotal);

        tr.querySelector('.pr-remove').addEventListener('click', function () {
            var i = csInstances.indexOf(cs);
            if (i > -1) csInstances.splice(i, 1);
            tr.remove(); recalcTotal();
            if (!tbody.querySelector('tr') && emptyHint) emptyHint.style.display = '';
        });

        /* Pre-fill existing values */
        if (IS_MULTI && projCs && prefill.project_id) {
            var projLabel = prefill.project_label || String(prefill.project_id);
            projCs._select(String(prefill.project_id), projLabel);
        }
        if (prefill.product_id) {
            var label = prefill.product_label || String(prefill.product_id);
            cs._select(String(prefill.product_id), label);
        }
        if (prefill.quantity)    { qtyEl.value    = prefill.quantity; }
        if (prefill.unit_amount) { rateEl.value   = prefill.unit_amount; }
        if (prefill.line_total)  { totalInp.value = prefill.line_total; recalcTotal(); }
    }

    /* ── Pre-populate existing items on load ── */
    EXISTING_ITEMS.forEach(function (item) { addRow(item); });

    document.getElementById('addProductRowBtn').addEventListener('click', function () { addRow(); });
})();
</script>
@endpush
