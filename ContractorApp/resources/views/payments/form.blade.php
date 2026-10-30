{{-- Shared payment form. Expects $payment, $projects, $paymentMethods. --}}
@if(isset($from))
    <input type="hidden" name="from" value="{{ $from }}" />
@endif

@php $pmtWrapperId = 'paymentMethodWrapper'; @endphp

<div class="card form-card">
    <div class="card-body">
        <x-form.section title="Payment Details" :first="true">
            <x-form.select name="project_id" label="Project" :options="$projects" :value="$payment->project_id" placeholder="Select project" :required="true" />
            <x-form.input name="payment_date" label="Date" type="date" :value="$payment->payment_date?->format('Y-m-d') ?? today()->format('Y-m-d')" :required="true" col="col-md-4" />
            <x-form.input name="amount_received" label="Amount Received (₹)" type="number" step="0.01" min="0" :value="$payment->amount_received" :required="true" col="col-md-4" />

            {{-- Payment Method CS-dropdown --}}
            <div class="col-md-4">
                <label class="form-label fw-semibold">Payment Method</label>
                <div class="cs-wrapper" id="{{ $pmtWrapperId }}">
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
                            <button type="button" class="cs-add-btn" id="pmtAddBtn">
                                <i class="bi bi-plus-circle"></i> Add new method
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="payment_method" id="pmtHiddenInput" value="{{ old('payment_method', $payment->payment_method) }}">
                </div>
                @error('payment_method')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <x-form.input name="receipt_reference" label="Receipt / Reference No." :value="$payment->receipt_reference" maxlength="100" col="col-md-6" />
            <x-form.textarea name="notes" label="Notes" :value="$payment->notes" rows="2" maxlength="5000" />
        </x-form.section>
    </div>
</div>

{{-- Add new payment method modal --}}
<div class="modal fade" id="pmtAddMethodModal" tabindex="-1" aria-labelledby="pmtAddMethodModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pmtAddMethodModalLabel"><i class="bi bi-credit-card me-2"></i>New Payment Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="pmtModalInput" class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="pmtModalInput" maxlength="100" autocomplete="off" placeholder="e.g. Bank Transfer, UPI…" />
                <div class="invalid-feedback" id="pmtModalError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="pmtModalSave">Add &amp; Select</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var pmtCs    = new CustomSelect('{{ $pmtWrapperId }}');
    var pmtInput = document.getElementById('pmtHiddenInput');
    var origPmtSel = pmtCs._select.bind(pmtCs);
    pmtCs._select = function(v, l) { origPmtSel(v, l); if (pmtInput) pmtInput.value = v; };

    var pmtModal      = bootstrap.Modal.getOrCreateInstance(document.getElementById('pmtAddMethodModal'));
    var pmtModalInput = document.getElementById('pmtModalInput');
    var pmtModalError = document.getElementById('pmtModalError');
    var pmtModalSave  = document.getElementById('pmtModalSave');

    document.getElementById('pmtAddBtn').addEventListener('click', function (e) {
        e.stopPropagation();
        pmtCs._close();
        pmtModal.show();
        setTimeout(function () { pmtModalInput.focus(); }, 300);
    });

    document.getElementById('pmtAddMethodModal').addEventListener('hidden.bs.modal', function () {
        pmtModalInput.value = '';
        pmtModalInput.classList.remove('is-invalid'); pmtModalError.textContent = '';
    });

    pmtModalSave.addEventListener('click', function () {
        pmtModalInput.classList.remove('is-invalid'); pmtModalError.textContent = '';
        var name = pmtModalInput.value.trim();
        if (!name) { pmtModalInput.classList.add('is-invalid'); pmtModalError.textContent = 'Name is required.'; return; }
        pmtCs.addOption(name, name, true);
        pmtModal.hide();
    });

    pmtModalInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); pmtModalSave.click(); }
        if (e.key === 'Escape') { pmtModal.hide(); }
    });
})();
</script>
@endpush
