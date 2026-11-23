{{-- Shared employee form partial. Expects: $employee, $salaryTypes, $designations --}}
<div class="card form-card">
    <div class="card-body">
        <x-form.section title="Personal Details" :first="true">
            <x-form.input name="name" label="Full Name" :value="$employee->name" :required="true" maxlength="200" autofocus col="col-md-8" />
            <x-form.input name="employee_code" label="Employee Code" :value="$employee->employee_code" placeholder="e.g. EMP001" col="col-md-4" />
            <x-form.input name="phone" label="Phone" type="tel" :value="$employee->phone" col="col-md-4" />
            <x-form.input name="email" label="Email" type="email" :value="$employee->email" col="col-md-4" />

            {{-- Designation CS-dropdown with add button --}}
            <div class="col-md-4">
                <label class="form-label fw-semibold">Designation / Role</label>
                <div class="cs-wrapper" id="designationWrapper">
                    <div class="cs-trigger">
                        <span class="cs-value {{ $employee->designation ? '' : 'cs-placeholder' }}">{{ $employee->designation ?: 'Select or add…' }}</span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                        <div class="cs-list" id="designationCsList">
                            @forelse($designations as $d)
                                <div class="cs-item" data-value="{{ $d }}">{{ $d }}</div>
                            @empty
                                <div class="cs-item no-data" id="designationNoData">No designations yet</div>
                            @endforelse
                        </div>
                        <div class="cs-footer">
                            <button type="button" class="cs-add-btn" id="desigAddBtn">
                                <i class="bi bi-plus-circle"></i> Add new designation
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="designation" id="designationInput" value="{{ old('designation', $employee->designation) }}">
                </div>
                @error('designation')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <x-form.input name="joining_date" label="Joining Date" type="date" :value="$employee->joining_date?->format('Y-m-d')" col="col-md-4" />
        </x-form.section>

        <x-form.section title="Salary">
            {{-- Salary Type CS-dropdown --}}
            <div class="col-md-4">
                <label class="form-label fw-semibold">Salary Type</label>
                <div class="cs-wrapper" id="salaryTypeWrapper">
                    <div class="cs-trigger">
                        <span class="cs-value {{ $employee->salary_type ? '' : 'cs-placeholder' }}">{{ $employee->salary_type ?: 'Select or add…' }}</span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                        <div class="cs-list" id="salaryTypeCsList">
                            @forelse($salaryTypes as $st)
                                <div class="cs-item" data-value="{{ $st }}">{{ $st }}</div>
                            @empty
                                <div class="cs-item no-data" id="salaryTypeNoData">No salary types yet</div>
                            @endforelse
                        </div>
                        <div class="cs-footer">
                            <button type="button" class="cs-add-btn" id="salaryTypeAddBtn">
                                <i class="bi bi-plus-circle"></i> Add new salary type
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="salary_type" id="salaryTypeInput" value="{{ old('salary_type', $employee->salary_type) }}">
                </div>
                @error('salary_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <x-form.input name="salary_amount" label="Salary Amount (₹)" type="number" step="0.01" min="0" :value="$employee->salary_amount" col="col-md-4" />
        </x-form.section>

        <x-form.section title="Notes">
            <div class="col-12">
                <label class="form-label fw-semibold">Notes <span class="text-muted fw-normal small">(optional)</span></label>
                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" maxlength="5000">{{ old('notes', $employee->notes) }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </x-form.section>
    </div>
</div>

{{-- Add salary type modal --}}
<div class="modal fade" id="addSalaryTypeModal" tabindex="-1" aria-labelledby="addSalaryTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSalaryTypeModalLabel"><i class="bi bi-cash-stack me-2"></i>New Salary Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="salaryTypeModalName" class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="salaryTypeModalName" maxlength="100" autocomplete="off" placeholder="e.g. Daily, Monthly, Weekly…" />
                <div class="invalid-feedback" id="salaryTypeModalError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="salaryTypeModalSave">Create &amp; Select</button>
            </div>
        </div>
    </div>
</div>

{{-- Add designation modal --}}
<div class="modal fade" id="addDesignationModal" tabindex="-1" aria-labelledby="addDesignationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDesignationModalLabel"><i class="bi bi-person-badge me-2"></i>New Designation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="desigModalName" class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="desigModalName" maxlength="100" autocomplete="off" placeholder="e.g. Mason, Supervisor…" />
                <div class="invalid-feedback" id="desigModalError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="desigModalSave">Create &amp; Select</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // ── Salary Type ──
    var stCs    = new CustomSelect('salaryTypeWrapper');
    var stInput = document.getElementById('salaryTypeInput');
    var stModal = new bootstrap.Modal(document.getElementById('addSalaryTypeModal'));
    var stModalName  = document.getElementById('salaryTypeModalName');
    var stModalError = document.getElementById('salaryTypeModalError');
    var stModalSave  = document.getElementById('salaryTypeModalSave');

    if (stInput && stInput.value) stCs._select(stInput.value, stInput.value);
    document.getElementById('salaryTypeWrapper').addEventListener('cs:change', function (e) {
        if (stInput) stInput.value = e.detail.value;
    });
    document.getElementById('salaryTypeAddBtn').addEventListener('click', function (e) {
        e.stopPropagation(); stCs._close(); stModal.show();
        setTimeout(function () { if (stModalName) stModalName.focus(); }, 300);
    });
    document.getElementById('addSalaryTypeModal').addEventListener('hidden.bs.modal', function () {
        stModalName.value = ''; stModalName.classList.remove('is-invalid'); stModalError.textContent = '';
    });
    stModalSave.addEventListener('click', async function () {
        stModalName.classList.remove('is-invalid'); stModalError.textContent = '';
        var name = stModalName.value.trim();
        if (!name) { stModalName.classList.add('is-invalid'); stModalError.textContent = 'Name is required.'; return; }
        stModalSave.disabled = true;
        try {
            var res = await fetch('{{ route('employee-salary-types.quick', [], false) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ name: name }),
            });
            var data = await res.json();
            if (!res.ok) { var msg = data.errors ? Object.values(data.errors).flat().join(' ') : 'Error saving.'; stModalName.classList.add('is-invalid'); stModalError.textContent = msg; return; }
            var noData = document.querySelector('#salaryTypeCsList .no-data'); if (noData) noData.remove();
            stCs.addOption(data.name, data.name, true);
            if (stInput) stInput.value = data.name;
            stModal.hide();
        } catch (e) { stModalName.classList.add('is-invalid'); stModalError.textContent = 'Something went wrong.'; }
        finally { stModalSave.disabled = false; }
    });

    var desigCs       = new CustomSelect('designationWrapper');
    var desigInput    = document.getElementById('designationInput');
    var desigModal    = new bootstrap.Modal(document.getElementById('addDesignationModal'));
    var desigModalName  = document.getElementById('desigModalName');
    var desigModalError = document.getElementById('desigModalError');
    var desigModalSave  = document.getElementById('desigModalSave');

    // Pre-select current value
    var current = desigInput ? desigInput.value : '';
    if (current) {
        desigCs._select(current, current);
    }

    // Keep hidden input in sync when user picks from list
    document.getElementById('designationWrapper').addEventListener('cs:change', function (e) {
        if (desigInput) desigInput.value = e.detail.value;
    });

    document.getElementById('desigAddBtn').addEventListener('click', function (e) {
        e.stopPropagation();
        desigCs._close();
        desigModal.show();
        setTimeout(function () { if (desigModalName) desigModalName.focus(); }, 300);
    });

    document.getElementById('addDesignationModal').addEventListener('hidden.bs.modal', function () {
        desigModalName.value = '';
        desigModalName.classList.remove('is-invalid');
        desigModalError.textContent = '';
    });

    desigModalSave.addEventListener('click', async function () {
        desigModalName.classList.remove('is-invalid');
        desigModalError.textContent = '';
        var name = desigModalName.value.trim();
        if (!name) { desigModalName.classList.add('is-invalid'); desigModalError.textContent = 'Name is required.'; return; }
        desigModalSave.disabled = true;
        try {
            var res = await fetch('{{ route('employee-designations.quick', [], false) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ name: name }),
            });
            var data = await res.json();
            if (!res.ok) {
                var msg = data.errors ? Object.values(data.errors).flat().join(' ') : 'Error saving.';
                desigModalName.classList.add('is-invalid');
                desigModalError.textContent = msg;
                return;
            }
            var noData = document.querySelector('#designationCsList .no-data');
            if (noData) noData.remove();
            desigCs.addOption(data.name, data.name, true);
            if (desigInput) desigInput.value = data.name;
            desigModal.hide();
        } catch (e) {
            desigModalName.classList.add('is-invalid');
            desigModalError.textContent = 'Something went wrong.';
        } finally { desigModalSave.disabled = false; }
    });
})();
</script>
@endpush
