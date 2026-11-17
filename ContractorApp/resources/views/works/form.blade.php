{{-- Shared work form. Expects $work, $projects, $assignees, $paymentMethods, $existingPayments. --}}
@php
    $priorityOptions = App\Enums\WorkPriority::options();
    $statusOptions   = App\Enums\WorkStatus::options();
    $selectedPriority = old('priority', $work->priority instanceof \BackedEnum ? $work->priority->value : $work->priority);
    $selectedStatus   = old('status',   $work->status   instanceof \BackedEnum ? $work->status->value   : $work->status);
    $selectedProject  = old('project_id', $work->project_id);
@endphp

<div class="card form-card">
    <div class="card-body">
        <x-form.section title="Work Information" :first="true">
            <x-form.input name="title" label="Title" :value="$work->title" :required="true" maxlength="150" col="col-md-12 col-xl-8" autofocus />

            {{-- Project CS-dropdown --}}
            <div class="col-md-6 col-xl-4">
                <label class="form-label fw-semibold">Project <span class="req">*</span></label>
                <div class="cs-wrapper" id="workProjectWrapper">
                    <div class="cs-trigger">
                        <span class="cs-value {{ $selectedProject ? '' : 'cs-placeholder' }}">
                            {{ $selectedProject ? ($projects[$selectedProject] ?? 'Select project') : 'Select project' }}
                        </span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                        <div class="cs-list">
                            <div class="cs-item" data-value="">Select project</div>
                            @foreach($projects as $id => $name)
                                <div class="cs-item" data-value="{{ $id }}">{{ $name }}</div>
                            @endforeach
                        </div>
                    </div>
                    <input type="hidden" name="project_id" id="workProjectInput" value="{{ $selectedProject ?? '' }}">
                </div>
                @error('project_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                <div class="form-text">The client is taken from the project.</div>
            </div>

            <x-form.textarea name="description" label="Description" :value="$work->description" rows="3" maxlength="5000" />
        </x-form.section>

        <x-form.section title="Schedule & Details">
            {{-- Priority CS-dropdown --}}
            <div class="col-md-6 col-xl-4">
                <label class="form-label fw-semibold">Priority</label>
                <div class="cs-wrapper" id="workPriorityWrapper">
                    <div class="cs-trigger">
                        <span class="cs-value {{ $selectedPriority ? '' : 'cs-placeholder' }}">
                            {{ $selectedPriority ? ($priorityOptions[$selectedPriority] ?? 'Select priority') : 'Select priority' }}
                        </span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                        <div class="cs-list">
                            @foreach($priorityOptions as $val => $label)
                                <div class="cs-item" data-value="{{ $val }}">{{ $label }}</div>
                            @endforeach
                        </div>
                    </div>
                    <input type="hidden" name="priority" id="workPriorityInput" value="{{ $selectedPriority ?? '' }}">
                </div>
                @error('priority')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            {{-- Status CS-dropdown --}}
            <div class="col-md-6 col-xl-4">
                <label class="form-label fw-semibold">Status</label>
                <div class="cs-wrapper" id="workStatusWrapper">
                    <div class="cs-trigger">
                        <span class="cs-value {{ $selectedStatus ? '' : 'cs-placeholder' }}">
                            {{ $selectedStatus ? ($statusOptions[$selectedStatus] ?? 'Select status') : 'Select status' }}
                        </span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                        <div class="cs-list">
                            @foreach($statusOptions as $val => $label)
                                <div class="cs-item" data-value="{{ $val }}">{{ $label }}</div>
                            @endforeach
                        </div>
                    </div>
                    <input type="hidden" name="status" id="workStatusInput" value="{{ $selectedStatus ?? '' }}">
                </div>
                @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <x-form.input name="start_date" label="Start Date" type="date" :value="$work->start_date?->format('Y-m-d')" />
            <x-form.input name="due_date" label="Due Date" type="date" :value="$work->due_date?->format('Y-m-d')" />
        </x-form.section>

        <x-form.section title="Assign Employees">
            <div class="col-12">
                <label class="form-label fw-semibold">Assigned Employees <span class="text-muted fw-normal small">(optional)</span></label>
                <div class="d-flex flex-wrap gap-2" id="empAssignList">
                    @foreach($allEmployees as $emp)
                    @php $checked = in_array($emp->id, $selectedEmployeeIds); @endphp
                    <div class="form-check form-check-inline border rounded px-3 py-2" style="min-width:140px">
                        <input class="form-check-input" type="checkbox" name="employee_ids[]" value="{{ $emp->id }}" id="empCheck_{{ $emp->id }}" {{ $checked ? 'checked' : '' }}>
                        <label class="form-check-label" for="empCheck_{{ $emp->id }}">{{ $emp->name }}</label>
                        @if($emp->designation)<div class="text-muted" style="font-size:0.75rem">{{ $emp->designation }}</div>@endif
                    </div>
                    @endforeach
                    @if($allEmployees->isEmpty())
                        <div class="text-muted small">No active employees. <a href="{{ route('employees.create') }}">Add one</a>.</div>
                    @endif
                </div>
            </div>
        </x-form.section>

        @php
            $fixedMethods = ['Cash', 'Bank Transfer', 'UPI', 'Cheque', 'Credit Card'];
            $allMethods   = collect($fixedMethods)
                ->concat($paymentMethods)
                ->unique()->values();
        @endphp

        <x-form.section title="Payments">
            <div class="col-12">
                <table class="table table-sm mb-2" id="workPaymentsTable">
                    <thead>
                        <tr class="table-light">
                            <th style="width:55%">Payment Method</th>
                            <th style="width:35%">Amount (₹)</th>
                            <th style="width:10%"></th>
                        </tr>
                    </thead>
                    <tbody id="workPaymentsBody">
                        @forelse($existingPayments as $i => $ep)
                        <tr class="work-payment-row">
                            <td>
                                <div class="cs-wrapper" id="workPmWrapper_{{ $i }}">
                                    <div class="cs-trigger">
                                        <span class="cs-value {{ $ep->payment_method ? '' : 'cs-placeholder' }}">{{ $ep->payment_method ?: 'Select method…' }}</span>
                                        <i class="bi bi-chevron-down cs-arrow"></i>
                                    </div>
                                    <div class="cs-panel">
                                        <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                                        <div class="cs-list">
                                            @foreach($allMethods as $pm)
                                                <div class="cs-item" data-value="{{ $pm }}">{{ $pm }}</div>
                                            @endforeach
                                        </div>
                                        <div class="cs-footer">
                                            <button type="button" class="cs-add-btn work-pm-add-btn"><i class="bi bi-plus-circle"></i> Add new method</button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="payments[{{ $i }}][payment_method]" id="workPmInput_{{ $i }}" value="{{ $ep->payment_method }}">
                                </div>
                            </td>
                            <td>
                                <input type="number" name="payments[{{ $i }}][amount]"
                                    class="form-control form-control-sm work-pm-amount"
                                    step="0.01" min="0"
                                    value="{{ $ep->amount }}" placeholder="0.00">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-payment-row" title="Remove">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr class="work-payment-row">
                            <td>
                                <div class="cs-wrapper" id="workPmWrapper_0">
                                    <div class="cs-trigger">
                                        <span class="cs-value cs-placeholder">Select method…</span>
                                        <i class="bi bi-chevron-down cs-arrow"></i>
                                    </div>
                                    <div class="cs-panel">
                                        <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                                        <div class="cs-list">
                                            @foreach($allMethods as $pm)
                                                <div class="cs-item" data-value="{{ $pm }}">{{ $pm }}</div>
                                            @endforeach
                                        </div>
                                        <div class="cs-footer">
                                            <button type="button" class="cs-add-btn work-pm-add-btn"><i class="bi bi-plus-circle"></i> Add new method</button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="payments[0][payment_method]" id="workPmInput_0" value="">
                                </div>
                            </td>
                            <td>
                                <input type="number" name="payments[0][amount]"
                                    class="form-control form-control-sm work-pm-amount"
                                    step="0.01" min="0" placeholder="0.00">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-payment-row" title="Remove">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="addWorkPaymentRow">
                                    <i class="bi bi-plus-circle me-1"></i>Add Row
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-end text-muted small fw-semibold">Total</td>
                            <td><span id="workPaymentsTotal" class="fw-semibold">₹0.00</span></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-form.section>
    </div>
</div>

{{-- Add new payment method modal --}}
<div class="modal fade" id="workPmAddMethodModal" tabindex="-1" aria-labelledby="workPmAddMethodModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="workPmAddMethodModalLabel"><i class="bi bi-credit-card me-2"></i>New Payment Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="workPmModalInput" class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="workPmModalInput" maxlength="100" autocomplete="off" placeholder="e.g. NEFT, PayPal…" />
                <div class="invalid-feedback" id="workPmModalError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="workPmModalSave">Add &amp; Select</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    function bindCs(wrapperId, inputId) {
        var cs    = new CustomSelect(wrapperId);
        var input = document.getElementById(inputId);
        var orig  = cs._select.bind(cs);
        cs._select = function(v, l) { orig(v, l); if (input) input.value = v; };
    }

    bindCs('workProjectWrapper',  'workProjectInput');
    bindCs('workPriorityWrapper', 'workPriorityInput');
    bindCs('workStatusWrapper',   'workStatusInput');

    // ── Payments table ──
    var tbody        = document.getElementById('workPaymentsBody');
    var totalEl      = document.getElementById('workPaymentsTotal');
    var pmMethods    = @json($allMethods);
    var pmRowCounter = {{ max(count($existingPayments), 1) }};
    var activeAddCs  = null;

    var workPmModal      = bootstrap.Modal.getOrCreateInstance(document.getElementById('workPmAddMethodModal'));
    var workPmModalInput = document.getElementById('workPmModalInput');
    var workPmModalError = document.getElementById('workPmModalError');
    var workPmModalSave  = document.getElementById('workPmModalSave');

    function initRowCs(wrapperId, inputId) {
        var cs    = new CustomSelect(wrapperId);
        var input = document.getElementById(inputId);
        var orig  = cs._select.bind(cs);
        cs._select = function(v, l) { orig(v, l); if (input) input.value = v; };
        var saved = input ? input.value : '';
        if (saved) cs._select(saved, saved);
        var addBtn = document.querySelector('#' + wrapperId + ' .work-pm-add-btn');
        if (addBtn) {
            addBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                cs._close();
                activeAddCs = cs;
                workPmModal.show();
                setTimeout(function() { workPmModalInput.focus(); }, 300);
            });
        }
    }

    // Init CS-dropdowns for blade-rendered rows
    @forelse($existingPayments as $i => $ep)
        initRowCs('workPmWrapper_{{ $i }}', 'workPmInput_{{ $i }}');
    @empty
        initRowCs('workPmWrapper_0', 'workPmInput_0');
    @endforelse

    function csItemsHtml() {
        return pmMethods.map(function(m) {
            return '<div class="cs-item" data-value="' + m.replace(/&/g,'&amp;').replace(/"/g,'&quot;') + '">' + m + '</div>';
        }).join('');
    }

    function buildCsCell(wrapperId, inputId, nameAttr) {
        return '<div class="cs-wrapper" id="' + wrapperId + '">' +
            '<div class="cs-trigger"><span class="cs-value cs-placeholder">Select method…</span><i class="bi bi-chevron-down cs-arrow"></i></div>' +
            '<div class="cs-panel">' +
            '<div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>' +
            '<div class="cs-list">' + csItemsHtml() + '</div>' +
            '<div class="cs-footer"><button type="button" class="cs-add-btn work-pm-add-btn"><i class="bi bi-plus-circle"></i> Add new method</button></div>' +
            '</div>' +
            '<input type="hidden" name="' + nameAttr + '" id="' + inputId + '" value="">' +
            '</div>';
    }

    function reindex() {
        tbody.querySelectorAll('.work-payment-row').forEach(function (row, i) {
            row.querySelector('input[type=hidden]').name = 'payments[' + i + '][payment_method]';
            row.querySelector('input[type=number]').name = 'payments[' + i + '][amount]';
        });
        recalc();
    }

    function recalc() {
        var total = 0;
        tbody.querySelectorAll('.work-pm-amount').forEach(function (inp) {
            total += parseFloat(inp.value) || 0;
        });
        totalEl.textContent = '₹' + total.toFixed(2);
    }

    function newRow(index) {
        var wrapperId = 'workPmWrapper_' + index;
        var inputId   = 'workPmInput_'   + index;
        var tr = document.createElement('tr');
        tr.className = 'work-payment-row';
        tr.innerHTML =
            '<td>' + buildCsCell(wrapperId, inputId, 'payments[' + index + '][payment_method]') + '</td>' +
            '<td><input type="number" name="payments[' + index + '][amount]" class="form-control form-control-sm work-pm-amount" step="0.01" min="0" placeholder="0.00"></td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-payment-row" title="Remove"><i class="bi bi-trash"></i></button></td>';
        return tr;
    }

    document.getElementById('addWorkPaymentRow').addEventListener('click', function () {
        var index = pmRowCounter++;
        var tr = newRow(index);
        tbody.appendChild(tr);
        initRowCs('workPmWrapper_' + index, 'workPmInput_' + index);
    });

    tbody.addEventListener('click', function (e) {
        var btn = e.target.closest('.remove-payment-row');
        if (!btn) return;
        var rows = tbody.querySelectorAll('.work-payment-row');
        if (rows.length > 1) {
            btn.closest('tr').remove();
            reindex();
        } else {
            var row = btn.closest('tr');
            row.querySelector('input[type=hidden]').value = '';
            var span = row.querySelector('.cs-value');
            if (span) { span.textContent = 'Select method…'; span.classList.add('cs-placeholder'); }
            row.querySelector('input[type=number]').value = '';
            recalc();
        }
    });

    tbody.addEventListener('input', function (e) {
        if (e.target.classList.contains('work-pm-amount')) recalc();
    });

    // Modal handlers
    document.getElementById('workPmAddMethodModal').addEventListener('hidden.bs.modal', function () {
        workPmModalInput.value = '';
        workPmModalInput.classList.remove('is-invalid');
        workPmModalError.textContent = '';
    });

    workPmModalSave.addEventListener('click', function () {
        workPmModalInput.classList.remove('is-invalid');
        workPmModalError.textContent = '';
        var name = workPmModalInput.value.trim();
        if (!name) {
            workPmModalInput.classList.add('is-invalid');
            workPmModalError.textContent = 'Name is required.';
            return;
        }
        if (activeAddCs) {
            pmMethods.push(name);
            activeAddCs.addOption(name, name, true);
        }
        workPmModal.hide();
    });

    workPmModalInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter')  { e.preventDefault(); workPmModalSave.click(); }
        if (e.key === 'Escape') { workPmModal.hide(); }
    });

    recalc();
})();
</script>
@endpush
