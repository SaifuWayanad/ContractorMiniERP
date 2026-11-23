@extends('layouts.app')

@section('title', 'Process Payroll — '.$months[$month].' '.$year)

@section('content')
<x-page-header title="Process Payroll" :subtitle="$months[$month].' '.$year" :back="route('salary-payments.index')">
    <x-slot:actions>
        <a href="{{ route('salary-payments.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-clock-history me-1"></i>History</a>
    </x-slot:actions>
</x-page-header>

{{-- Period picker --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" action="{{ route('salary-payments.bulk') }}" class="d-flex align-items-center gap-2 flex-wrap">
            <label class="text-muted small mb-0">Period:</label>
            <select name="month" class="form-select form-select-sm" style="max-width:130px" onchange="this.form.submit()">
                @foreach($months as $num => $name)
                    <option value="{{ $num }}" @selected($num == $month)>{{ $name }}</option>
                @endforeach
            </select>
            <input type="number" name="year" class="form-control form-control-sm" value="{{ $year }}" min="2000" max="2100" style="max-width:85px" onchange="this.form.submit()" />
        </form>
    </div>
</div>

@if($employees->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-people" title="No employees" message="Add employees first to process payroll.">
            @can('create', App\Models\Employee::class)
                <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">Add Employee</a>
            @endcan
        </x-empty-state>
    </div>
@else
<form method="post" action="{{ route('salary-payments.bulk.save') }}">
    @csrf
    <input type="hidden" name="month" value="{{ $month }}" />
    <input type="hidden" name="year" value="{{ $year }}" />

    {{-- Global payment settings --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control form-control-sm" value="{{ today()->format('Y-m-d') }}" required />
                </div>

                {{-- Payment Method CS-dropdown --}}
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Payment Method</label>
                    <div class="cs-wrapper cs-sm" id="bulkPmWrapper">
                        <div class="cs-trigger">
                            <span class="cs-value {{ $paymentMethods->isNotEmpty() ? 'cs-placeholder' : 'cs-placeholder' }}">Select or add…</span>
                            <i class="bi bi-chevron-down cs-arrow"></i>
                        </div>
                        <div class="cs-panel">
                            <div class="cs-list" id="bulkPmCsList">
                                <div class="cs-item" data-value="">— None —</div>
                                @foreach($paymentMethods as $pm)
                                    <div class="cs-item" data-value="{{ $pm }}">{{ $pm }}</div>
                                @endforeach
                            </div>
                            <div class="cs-footer">
                                <button type="button" class="cs-add-btn" id="bulkPmAddBtn">
                                    <i class="bi bi-plus-circle"></i> Add new method
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="payment_method" id="bulkPmInput" value="">
                    </div>
                </div>

                {{-- Status CS-dropdown --}}
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Status <span class="text-danger">*</span></label>
                    <div class="cs-wrapper cs-sm" id="bulkStatusWrapper">
                        <div class="cs-trigger">
                            <span class="cs-value">Paid</span>
                            <i class="bi bi-chevron-down cs-arrow"></i>
                        </div>
                        <div class="cs-panel">
                            <div class="cs-list">
                                @foreach($statuses as $val => $label)
                                    <div class="cs-item" data-value="{{ $val }}">{{ $label }}</div>
                                @endforeach
                            </div>
                        </div>
                        <input type="hidden" name="status" id="bulkStatusInput" value="paid">
                    </div>
                </div>

                <div class="col-sm-6 col-md-3 d-flex align-items-end gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="checkAll">Select All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="uncheckAll">Deselect All</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Employee table --}}
    <div class="card">
        <div class="card-header">
            <strong class="small text-uppercase tracking-wide text-muted">{{ $months[$month] }} {{ $year }} — {{ $employees->count() }} employees</strong>
        </div>
        <div style="overflow:visible">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:4%"></th>
                        <th style="width:35%">Employee</th>
                        <th style="width:25%">Basic Pay (₹)</th>
                        <th style="width:36%">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $emp)
                    @php
                        $existingPay   = $existing[$emp->id] ?? null;
                        $defaultAmount = $existingPay ? (float) $existingPay->amount : (float) ($emp->salary_amount ?? 0);
                        $autoCheck     = $existingPay !== null || $defaultAmount > 0;
                    @endphp
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" name="rows[{{ $emp->id }}][include]" value="1"
                                class="form-check-input row-check" @checked($autoCheck) />
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $emp->name }}</div>
                            @if($emp->designation)
                                <div class="text-muted small">{{ $emp->designation }}</div>
                            @endif
                            @if($emp->salary_type)
                                <span class="badge badge-soft-info mt-1" style="font-size:0.68rem">{{ $emp->salary_type }}</span>
                            @endif
                            @if($existingPay)
                                <div class="text-success small mt-1">
                                    <i class="bi bi-check-circle me-1"></i>Saved: ₹{{ number_format((float)$existingPay->amount, 2) }}
                                    <span class="badge {{ $existingPay->status->badgeClass() }} ms-1" style="font-size:0.65rem">{{ $existingPay->status->label() }}</span>
                                </div>
                            @endif
                        </td>
                        <td>
                            <input type="number"
                                name="rows[{{ $emp->id }}][amount]"
                                class="form-control form-control-sm"
                                value="{{ $defaultAmount > 0 ? $defaultAmount : '' }}"
                                min="0" step="0.01" placeholder="0.00" />
                        </td>
                        <td>
                            <input type="text"
                                name="rows[{{ $emp->id }}][notes]"
                                class="form-control form-control-sm"
                                value="{{ $existingPay?->notes ?? '' }}"
                                placeholder="Note…" maxlength="500" />
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <a href="{{ route('salary-payments.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Save Payments</button>
        </div>
    </div>
</form>
@endif

{{-- Add Payment Method modal --}}
<div class="modal fade" id="bulkAddPmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-credit-card me-2"></i>New Payment Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="bulkPmModalName" maxlength="100" autocomplete="off" placeholder="e.g. NEFT, Wallet…" />
                <div class="invalid-feedback" id="bulkPmModalError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="bulkPmModalSave">Create &amp; Select</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // ── Select / deselect all ──
    document.getElementById('checkAll')?.addEventListener('click', function () {
        document.querySelectorAll('.row-check').forEach(function (cb) { cb.checked = true; });
    });
    document.getElementById('uncheckAll')?.addEventListener('click', function () {
        document.querySelectorAll('.row-check').forEach(function (cb) { cb.checked = false; });
    });

    // ── Payment Method CS-dropdown ──
    var pmCs    = new CustomSelect('bulkPmWrapper');
    var pmInput = document.getElementById('bulkPmInput');
    document.getElementById('bulkPmWrapper').addEventListener('cs:change', function (e) { pmInput.value = e.detail.value; });

    var pmModal     = new bootstrap.Modal(document.getElementById('bulkAddPmModal'));
    var pmModalName  = document.getElementById('bulkPmModalName');
    var pmModalError = document.getElementById('bulkPmModalError');
    var pmModalSave  = document.getElementById('bulkPmModalSave');

    document.getElementById('bulkPmAddBtn').addEventListener('click', function (e) {
        e.stopPropagation(); pmCs._close(); pmModal.show();
        setTimeout(function () { pmModalName.focus(); }, 300);
    });
    document.getElementById('bulkAddPmModal').addEventListener('hidden.bs.modal', function () {
        pmModalName.value = ''; pmModalName.classList.remove('is-invalid'); pmModalError.textContent = '';
    });
    pmModalSave.addEventListener('click', async function () {
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
            if (!res.ok) { var msg = data.errors ? Object.values(data.errors).flat().join(' ') : 'Error.'; pmModalName.classList.add('is-invalid'); pmModalError.textContent = msg; return; }
            var noData = document.querySelector('#bulkPmCsList .no-data'); if (noData) noData.remove();
            pmCs.addOption(data.name, data.name, true);
            pmInput.value = data.name;
            pmModal.hide();
        } catch (err) { pmModalName.classList.add('is-invalid'); pmModalError.textContent = 'Something went wrong.'; }
        finally { pmModalSave.disabled = false; }
    });

    // ── Status CS-dropdown ──
    var statusCs    = new CustomSelect('bulkStatusWrapper');
    var statusInput = document.getElementById('bulkStatusInput');
    statusCs._select('paid', 'Paid');
    document.getElementById('bulkStatusWrapper').addEventListener('cs:change', function (e) { statusInput.value = e.detail.value; });

    // ── Fix CS panels escaping overflow containers ──
    document.querySelectorAll('.cs-wrapper').forEach(function (wrapper) {
        var panel = wrapper.querySelector('.cs-panel');
        if (!panel) return;
        new MutationObserver(function () {
            if (wrapper.classList.contains('open')) {
                var rect = wrapper.getBoundingClientRect();
                panel.style.cssText = 'position:fixed!important;top:' + (rect.bottom + 2) + 'px;left:' + rect.left + 'px;right:auto;width:' + Math.max(rect.width, 160) + 'px;z-index:9999';
            } else {
                panel.style.cssText = '';
            }
        }).observe(wrapper, { attributes: true, attributeFilter: ['class'] });
    });
})();
</script>
@endpush
@endsection
