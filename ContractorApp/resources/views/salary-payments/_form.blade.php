{{-- Shared salary payment form partial. Expects: $payment, $employees, $statuses, $paymentMethods, $months --}}
<div class="card form-card">
    <div class="card-body">
        <x-form.section title="Payment Details" :first="true">

            {{-- Employee CS-dropdown --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                <div class="cs-wrapper" id="spEmployeeWrapper">
                    <div class="cs-trigger">
                        <span class="cs-value {{ old('employee_id', $payment->employee_id) ? '' : 'cs-placeholder' }}">
                            {{ old('employee_id', $payment->employee_id) ? ($employees[old('employee_id', $payment->employee_id)] ?? 'Select employee…') : 'Select employee…' }}
                        </span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search employee…"></div>
                        <div class="cs-list">
                            @forelse($employees as $id => $name)
                                <div class="cs-item" data-value="{{ $id }}">{{ $name }}</div>
                            @empty
                                <div class="cs-item no-data">No employees found</div>
                            @endforelse
                        </div>
                    </div>
                    <input type="hidden" name="employee_id" id="spEmployeeInput" value="{{ old('employee_id', $payment->employee_id ?? '') }}">
                </div>
                @error('employee_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            {{-- Month CS-dropdown --}}
            <div class="col-md-3">
                <label class="form-label fw-semibold">Month <span class="text-danger">*</span></label>
                @php $selectedMonth = old('period_month', $payment->period_month); @endphp
                <div class="cs-wrapper" id="spMonthWrapper">
                    <div class="cs-trigger">
                        <span class="cs-value {{ $selectedMonth ? '' : 'cs-placeholder' }}">{{ $selectedMonth ? ($months[$selectedMonth] ?? 'Select month…') : 'Select month…' }}</span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-list">
                            @foreach($months as $num => $name)
                                <div class="cs-item" data-value="{{ $num }}">{{ $name }}</div>
                            @endforeach
                        </div>
                    </div>
                    <input type="hidden" name="period_month" id="spMonthInput" value="{{ $selectedMonth ?? '' }}">
                </div>
                @error('period_month')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <x-form.input name="period_year" label="Year" type="number" min="2000" max="2100" :value="old('period_year', $payment->period_year ?? now()->year)" :required="true" col="col-md-3" />

            <x-form.input name="amount" label="Amount (₹)" type="number" step="0.01" min="0" :value="$payment->amount" :required="true" col="col-md-4" />

            <x-form.input name="payment_date" label="Payment Date" type="date" :value="$payment->payment_date?->format('Y-m-d') ?? today()->format('Y-m-d')" :required="true" col="col-md-4" />

            {{-- Payment Method CS-dropdown with Add new --}}
            <div class="col-md-4">
                <label class="form-label fw-semibold">Payment Method</label>
                <div class="cs-wrapper" id="spPmWrapper">
                    <div class="cs-trigger">
                        <span class="cs-value {{ old('payment_method', $payment->payment_method) ? '' : 'cs-placeholder' }}">{{ old('payment_method', $payment->payment_method) ?: 'Select or add…' }}</span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-list" id="spPmCsList">
                            @forelse($paymentMethods as $pm)
                                <div class="cs-item" data-value="{{ $pm }}">{{ $pm }}</div>
                            @empty
                                <div class="cs-item no-data" id="spPmNoData">No payment methods yet</div>
                            @endforelse
                        </div>
                        <div class="cs-footer">
                            <button type="button" class="cs-add-btn" id="spPmAddBtn">
                                <i class="bi bi-plus-circle"></i> Add new method
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="payment_method" id="spPmInput" value="{{ old('payment_method', $payment->payment_method ?? '') }}">
                </div>
                @error('payment_method')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            {{-- Status CS-dropdown --}}
            <div class="col-md-4">
                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                @php $selectedStatus = old('status', $payment->status?->value ?? 'paid'); @endphp
                <div class="cs-wrapper" id="spStatusWrapper">
                    <div class="cs-trigger">
                        <span class="cs-value">{{ $statuses[$selectedStatus] ?? 'Select status…' }}</span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-list">
                            @foreach($statuses as $val => $label)
                                <div class="cs-item" data-value="{{ $val }}">{{ $label }}</div>
                            @endforeach
                        </div>
                    </div>
                    <input type="hidden" name="status" id="spStatusInput" value="{{ $selectedStatus }}">
                </div>
                @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">Notes <span class="text-muted fw-normal small">(optional)</span></label>
                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" maxlength="5000">{{ old('notes', $payment->notes) }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </x-form.section>
    </div>
</div>

{{-- Add Payment Method modal --}}
<div class="modal fade" id="addPmModal" tabindex="-1" aria-labelledby="addPmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPmModalLabel"><i class="bi bi-credit-card me-2"></i>New Payment Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="pmModalName" class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="pmModalName" maxlength="100" autocomplete="off" placeholder="e.g. NEFT, Wallet…" />
                <div class="invalid-feedback" id="pmModalError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="pmModalSave">Create &amp; Select</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // ── Employee ──
    var empCs    = new CustomSelect('spEmployeeWrapper');
    var empInput = document.getElementById('spEmployeeInput');
    if (empInput.value) empCs._select(empInput.value, empInput.closest('.cs-wrapper').querySelector('.cs-value').textContent.trim());
    document.getElementById('spEmployeeWrapper').addEventListener('cs:change', function(e) { empInput.value = e.detail.value; });

    // ── Month ──
    var monthCs    = new CustomSelect('spMonthWrapper');
    var monthInput = document.getElementById('spMonthInput');
    if (monthInput.value) monthCs._select(monthInput.value, monthInput.closest('.cs-wrapper').querySelector('.cs-value').textContent.trim());
    document.getElementById('spMonthWrapper').addEventListener('cs:change', function(e) { monthInput.value = e.detail.value; });

    // ── Payment Method ──
    var pmCs    = new CustomSelect('spPmWrapper');
    var pmInput = document.getElementById('spPmInput');
    if (pmInput.value) pmCs._select(pmInput.value, pmInput.value);
    document.getElementById('spPmWrapper').addEventListener('cs:change', function(e) { pmInput.value = e.detail.value; });

    var pmModal     = new bootstrap.Modal(document.getElementById('addPmModal'));
    var pmModalName  = document.getElementById('pmModalName');
    var pmModalError = document.getElementById('pmModalError');
    var pmModalSave  = document.getElementById('pmModalSave');

    document.getElementById('spPmAddBtn').addEventListener('click', function(e) {
        e.stopPropagation(); pmCs._close(); pmModal.show();
        setTimeout(function() { pmModalName.focus(); }, 300);
    });
    document.getElementById('addPmModal').addEventListener('hidden.bs.modal', function() {
        pmModalName.value = ''; pmModalName.classList.remove('is-invalid'); pmModalError.textContent = '';
    });
    pmModalSave.addEventListener('click', async function() {
        pmModalName.classList.remove('is-invalid'); pmModalError.textContent = '';
        var name = pmModalName.value.trim();
        if (!name) { pmModalName.classList.add('is-invalid'); pmModalError.textContent = 'Name is required.'; return; }
        pmModalSave.disabled = true;
        try {
            var res  = await fetch('{{ route('salary-payment-methods.quick', [], false) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ name: name }),
            });
            var data = await res.json();
            if (!res.ok) { var msg = data.errors ? Object.values(data.errors).flat().join(' ') : 'Error saving.'; pmModalName.classList.add('is-invalid'); pmModalError.textContent = msg; return; }
            var noData = document.querySelector('#spPmCsList .no-data'); if (noData) noData.remove();
            pmCs.addOption(data.name, data.name, true);
            pmInput.value = data.name;
            pmModal.hide();
        } catch(e) { pmModalName.classList.add('is-invalid'); pmModalError.textContent = 'Something went wrong.'; }
        finally { pmModalSave.disabled = false; }
    });

    // ── Status ──
    var statusCs    = new CustomSelect('spStatusWrapper');
    var statusInput = document.getElementById('spStatusInput');
    if (statusInput.value) statusCs._select(statusInput.value, statusInput.closest('.cs-wrapper').querySelector('.cs-value').textContent.trim());
    document.getElementById('spStatusWrapper').addEventListener('cs:change', function(e) { statusInput.value = e.detail.value; });
})();
</script>
@endpush
