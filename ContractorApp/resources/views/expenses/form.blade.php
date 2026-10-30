{{-- Shared expense form. Expects $expense, $projects, $categories, $paymentMethods. --}}
@if(isset($from))
    <input type="hidden" name="from" value="{{ $from }}" />
@endif

@php
    $catWrapperId = 'expenseCategoryWrapper';
    $pmWrapperId  = 'expensePaymentWrapper';
@endphp

<div class="card form-card">
    <div class="card-body">
        <x-form.section title="Expense Details" :first="true">

            <x-form.select name="project_id" label="Project" :options="$projects" :value="$expense->project_id" placeholder="Select project" :required="true" />

            <x-form.input name="expense_date" label="Date" type="date" :value="$expense->expense_date?->format('Y-m-d') ?? today()->format('Y-m-d')" :required="true" col="col-md-4" />

            <x-form.input name="heading" label="Expense Name" :value="$expense->heading" :required="true" maxlength="200" autofocus col="col-md-8" />

            {{-- Category CS-dropdown --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">Category</label>
                <div class="cs-wrapper" id="{{ $catWrapperId }}">
                    <div class="cs-trigger @error('category') is-invalid @enderror">
                        <span class="cs-value cs-placeholder">Select or add a category...</span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                        <div class="cs-list">
                            @if($categories->isEmpty())
                                <div class="cs-item no-data">No categories yet</div>
                            @else
                                @foreach($categories as $cat)
                                    <div class="cs-item" data-value="{{ $cat }}">{{ $cat }}</div>
                                @endforeach
                            @endif
                        </div>
                        <div class="cs-footer">
                            <button type="button" class="cs-add-btn" id="catAddBtn">
                                <i class="bi bi-plus-circle"></i> Add new category
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="category" id="catHiddenInput" value="{{ old('category', $expense->category) }}">
                </div>
                @error('category')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <x-form.input name="amount" label="Amount (₹)" type="number" step="0.01" min="0" :value="$expense->amount" :required="true" col="col-md-3" />

            {{-- Payment Method CS-dropdown --}}
            <div class="col-md-3">
                <label class="form-label fw-semibold">Payment Method</label>
                <div class="cs-wrapper" id="{{ $pmWrapperId }}">
                    <div class="cs-trigger @error('payment_method') is-invalid @enderror">
                        <span class="cs-value cs-placeholder">Select method...</span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                        <div class="cs-list">
                            @foreach($paymentMethods as $pm)
                                <div class="cs-item" data-value="{{ $pm }}">{{ $pm }}</div>
                            @endforeach
                        </div>
                        <div class="cs-footer">
                            <button type="button" class="cs-add-btn" id="expPmAddBtn">
                                <i class="bi bi-plus-circle"></i> Add new method
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="payment_method" id="expPmHiddenInput" value="{{ old('payment_method', $expense->payment_method) }}">
                </div>
                @error('payment_method')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <x-form.input name="reference_number" label="Reference Number" :value="$expense->reference_number" maxlength="100" col="col-md-4" />

            <x-form.textarea name="notes" label="Notes" :value="$expense->notes" rows="2" maxlength="5000" />

        </x-form.section>
    </div>
</div>

{{-- Add new category modal --}}
<div class="modal fade" id="expAddCategoryModal" tabindex="-1" aria-labelledby="expAddCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="expAddCategoryModalLabel"><i class="bi bi-tag me-2"></i>New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="expCatModalInput" class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="expCatModalInput" maxlength="200" autocomplete="off" placeholder="e.g. Materials, Labour…" />
                <div class="invalid-feedback" id="expCatModalError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="expCatModalSave">Add &amp; Select</button>
            </div>
        </div>
    </div>
</div>

{{-- Add new payment method modal --}}
<div class="modal fade" id="expAddPmModal" tabindex="-1" aria-labelledby="expAddPmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="expAddPmModalLabel"><i class="bi bi-credit-card me-2"></i>New Payment Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="expPmModalInput" class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="expPmModalInput" maxlength="100" autocomplete="off" placeholder="e.g. Bank Transfer, UPI…" />
                <div class="invalid-feedback" id="expPmModalError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="expPmModalSave">Add &amp; Select</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    // ── Category ──
    var catCs    = new CustomSelect('{{ $catWrapperId }}');
    var catInput = document.getElementById('catHiddenInput');
    var origCatSel = catCs._select.bind(catCs);
    catCs._select = function(v, l) { origCatSel(v, l); if (catInput) catInput.value = v; };

    var catModal      = bootstrap.Modal.getOrCreateInstance(document.getElementById('expAddCategoryModal'));
    var catModalInput = document.getElementById('expCatModalInput');
    var catModalError = document.getElementById('expCatModalError');
    var catModalSave  = document.getElementById('expCatModalSave');

    document.getElementById('catAddBtn').addEventListener('click', function (e) {
        e.stopPropagation();
        catCs._close();
        catModal.show();
        setTimeout(function () { catModalInput.focus(); }, 300);
    });

    document.getElementById('expAddCategoryModal').addEventListener('hidden.bs.modal', function () {
        catModalInput.value = '';
        catModalInput.classList.remove('is-invalid'); catModalError.textContent = '';
    });

    catModalSave.addEventListener('click', function () {
        catModalInput.classList.remove('is-invalid'); catModalError.textContent = '';
        var name = catModalInput.value.trim();
        if (!name) { catModalInput.classList.add('is-invalid'); catModalError.textContent = 'Name is required.'; return; }
        catCs.addOption(name, name, true);
        catModal.hide();
    });

    catModalInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); catModalSave.click(); }
        if (e.key === 'Escape') { catModal.hide(); }
    });

    // ── Payment Method ──
    var pmCs    = new CustomSelect('{{ $pmWrapperId }}');
    var pmInput = document.getElementById('expPmHiddenInput');
    var origPmSel = pmCs._select.bind(pmCs);
    pmCs._select = function(v, l) { origPmSel(v, l); if (pmInput) pmInput.value = v; };

    var pmModal      = bootstrap.Modal.getOrCreateInstance(document.getElementById('expAddPmModal'));
    var pmModalInput = document.getElementById('expPmModalInput');
    var pmModalError = document.getElementById('expPmModalError');
    var pmModalSave  = document.getElementById('expPmModalSave');

    document.getElementById('expPmAddBtn').addEventListener('click', function (e) {
        e.stopPropagation();
        pmCs._close();
        pmModal.show();
        setTimeout(function () { pmModalInput.focus(); }, 300);
    });

    document.getElementById('expAddPmModal').addEventListener('hidden.bs.modal', function () {
        pmModalInput.value = '';
        pmModalInput.classList.remove('is-invalid'); pmModalError.textContent = '';
    });

    pmModalSave.addEventListener('click', function () {
        pmModalInput.classList.remove('is-invalid'); pmModalError.textContent = '';
        var name = pmModalInput.value.trim();
        if (!name) { pmModalInput.classList.add('is-invalid'); pmModalError.textContent = 'Name is required.'; return; }
        pmCs.addOption(name, name, true);
        pmModal.hide();
    });

    pmModalInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); pmModalSave.click(); }
        if (e.key === 'Escape') { pmModal.hide(); }
    });
})();
</script>
@endpush
