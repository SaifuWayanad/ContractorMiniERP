@extends('layouts.app')

@section('title', 'Add Project')

@section('content')
<x-page-header title="Add Project" subtitle="Fields marked * are required.">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('admin.projects.index') }}">Projects</a></li>
        <li class="breadcrumb-item active" aria-current="page">New</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('admin.projects.store') }}">
    @csrf

    {{-- Project Details --}}
    <x-card class="mb-3">
        <x-form.section title="Project Details" :first="true">

            {{-- Contractor --}}
            <x-form.select
                name="contractor_id"
                label="Contractor"
                :options="$contractors"
                placeholder="Select contractor"
                :value="$selectedContractorId ?? old('contractor_id')"
                :required="true"
                id="contractor_id_select"
            />

            {{-- Customer: always rendered; disabled until a contractor is picked --}}
            <div class="col-md-6 col-xl-4">
                <label for="client_id" class="form-label">Customer <span class="req">*</span></label>
                <div class="input-group">
                    <select
                        id="client_id"
                        name="client_id"
                        required
                        class="form-select{{ $errors->has('client_id') ? ' is-invalid' : '' }}"
                        @if(! $selectedContractorId) disabled @endif
                    >
                        @if (! $selectedContractorId)
                            <option value="">Select a contractor first</option>
                        @else
                            <option value="">Select customer</option>
                            @foreach ($clients as $id => $name)
                                <option value="{{ $id }}" @selected(old('client_id') == $id)>{{ $name }}</option>
                            @endforeach
                        @endif
                    </select>
                    @if ($selectedContractorId)
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#adminQuickCreateCustomer" title="Quick add customer">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    @endif
                    @error('client_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                @if (! $selectedContractorId)
                    <div class="form-text text-muted">Select a contractor first to load customers.</div>
                @endif
            </div>

            <x-form.input name="name" label="Project Name" :required="true" autocomplete="off" />

            <x-form.select name="status" label="Status" :options="App\Enums\ProjectStatus::options()" placeholder="Select status" />
            <x-form.input name="start_date" type="date" label="Start Date" />
            <x-form.textarea name="description" label="Description" rows="3" col="col-md-12 col-xl-8" />
        </x-form.section>
    </x-card>

    {{-- Site Location --}}
    <x-card class="mb-3">
        <x-form.section title="Site Location" :first="true">
            <div class="col-12">
                <p class="text-muted small mb-2">Optional — add the primary site location for this project.</p>
                {{-- Summary shown after location filled via modal --}}
                <div id="locationSummary" class="d-none mb-2">
                    <div class="alert alert-secondary d-flex align-items-center gap-2 py-2 mb-0">
                        <i class="bi bi-geo-alt-fill text-primary"></i>
                        <span id="locationSummaryText" class="flex-grow-1 small"></span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="locationEditBtn"><i class="bi bi-pencil"></i> Edit</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="locationClearBtn"><i class="bi bi-x"></i></button>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="locationAddBtn" data-bs-toggle="modal" data-bs-target="#adminQuickCreateLocation">
                    <i class="bi bi-plus-lg me-1"></i> Add Location
                </button>
            </div>
            {{-- Hidden inputs --}}
            <input type="hidden" name="location_name" id="hLocationName">
            <input type="hidden" name="location_address" id="hLocationAddress">
            <input type="hidden" name="location_city" id="hLocationCity">
            <input type="hidden" name="location_state" id="hLocationState">
            <input type="hidden" name="location_district" id="hLocationDistrict">
            <input type="hidden" name="location_country" id="hLocationCountry" value="India">
            <input type="hidden" name="location_postal_code" id="hLocationPostalCode">
            <input type="hidden" name="location_contact_person" id="hLocationContactPerson">
            <input type="hidden" name="location_contact_phone" id="hLocationContactPhone">
            <input type="hidden" name="location_notes" id="hLocationNotes">
        </x-form.section>
    </x-card>

    <x-form.actions :cancel="route('admin.projects.index')" submit="Add Project" />
</form>

{{-- Quick Create Customer modal --}}
@if ($selectedContractorId)
<div class="modal fade" id="adminQuickCreateCustomer" tabindex="-1" aria-labelledby="adminQCCustomerLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adminQCCustomerLabel">New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="qcAdminCustomerName" class="form-label">Customer Name <span class="req">*</span></label>
                    <input type="text" class="form-control" id="qcAdminCustomerName" maxlength="150" autocomplete="off" />
                    <div class="invalid-feedback" id="qcAdminCustomerError"></div>
                </div>
                <div class="mb-3">
                    <label for="qcAdminCustomerPhone" class="form-label">Phone</label>
                    <input type="tel" class="form-control" id="qcAdminCustomerPhone" maxlength="20" autocomplete="off" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="qcAdminCustomerSave">Create &amp; Select</button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Quick Create Location modal --}}
<div class="modal fade" id="adminQuickCreateLocation" tabindex="-1" aria-labelledby="adminQCLocationLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adminQCLocationLabel">Add Site Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="qcLocName" class="form-label">Location Name <span class="req">*</span></label>
                        <input type="text" class="form-control" id="qcLocName" maxlength="120" placeholder="e.g. Main site, Block C" autocomplete="off" />
                        <div class="invalid-feedback" id="qcLocNameError"></div>
                    </div>
                    <div class="col-12">
                        <label for="qcLocAddress" class="form-label">Address</label>
                        <input type="text" class="form-control" id="qcLocAddress" maxlength="250" autocomplete="off" />
                    </div>
                    <div class="col-md-6">
                        <label for="qcLocCity" class="form-label">City</label>
                        <input type="text" class="form-control" id="qcLocCity" maxlength="80" autocomplete="off" />
                    </div>
                    <div class="col-md-6">
                        <label for="qcLocState" class="form-label">State</label>
                        <input type="text" class="form-control" id="qcLocState" maxlength="80" list="qcStateOptions" autocomplete="off" data-state-input="qcDistrictOptions" />
                        <datalist id="qcStateOptions">
                            @foreach (array_keys($districtsByState) as $state)
                                <option value="{{ $state }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="col-md-6">
                        <label for="qcLocDistrict" class="form-label">District</label>
                        <input type="text" class="form-control" id="qcLocDistrict" maxlength="80" list="qcDistrictOptions" autocomplete="off" />
                        <datalist id="qcDistrictOptions"></datalist>
                    </div>
                    <div class="col-md-6">
                        <label for="qcLocPostal" class="form-label">Postal Code</label>
                        <input type="text" class="form-control" id="qcLocPostal" maxlength="12" inputmode="numeric" />
                    </div>
                    <div class="col-12"><hr class="my-1" /></div>
                    <div class="col-md-6">
                        <label for="qcLocContact" class="form-label">Contact Person</label>
                        <input type="text" class="form-control" id="qcLocContact" maxlength="100" autocomplete="off" />
                    </div>
                    <div class="col-md-6">
                        <label for="qcLocPhone" class="form-label">Contact Phone</label>
                        <input type="tel" class="form-control" id="qcLocPhone" maxlength="20" />
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="qcLocationSave">Add Location</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
@if ($selectedContractorId) var contractorId = {{ $selectedContractorId }}; @endif

/* Reload page when contractor changes to fetch its customers */
document.getElementById('contractor_id_select')?.addEventListener('change', function () {
    const id = this.value;
    if (id) window.location.href = '{{ route('admin.projects.create') }}?contractor_id=' + id;
});

/* Location quick-create modal */
(function () {
    var modalEl = document.getElementById('adminQuickCreateLocation');
    if (!modalEl) return;
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var fields = {
        name: document.getElementById('qcLocName'),
        address: document.getElementById('qcLocAddress'),
        city: document.getElementById('qcLocCity'),
        state: document.getElementById('qcLocState'),
        district: document.getElementById('qcLocDistrict'),
        postal: document.getElementById('qcLocPostal'),
        contact: document.getElementById('qcLocContact'),
        phone: document.getElementById('qcLocPhone'),
    };
    var nameError = document.getElementById('qcLocNameError');

    document.getElementById('qcLocationSave').addEventListener('click', function () {
        fields.name.classList.remove('is-invalid'); nameError.textContent = '';
        if (!fields.name.value.trim()) {
            fields.name.classList.add('is-invalid'); nameError.textContent = 'Location name is required.'; return;
        }
        document.getElementById('hLocationName').value = fields.name.value.trim();
        document.getElementById('hLocationAddress').value = fields.address.value.trim();
        document.getElementById('hLocationCity').value = fields.city.value.trim();
        document.getElementById('hLocationState').value = fields.state.value.trim();
        document.getElementById('hLocationDistrict').value = fields.district.value.trim();
        document.getElementById('hLocationPostalCode').value = fields.postal.value.trim();
        document.getElementById('hLocationContactPerson').value = fields.contact.value.trim();
        document.getElementById('hLocationContactPhone').value = fields.phone.value.trim();

        var parts = [fields.name.value.trim(), fields.city.value.trim(), fields.state.value.trim()].filter(Boolean);
        var summary = parts.join(', ');
        if (fields.contact.value.trim()) summary += ' · ' + fields.contact.value.trim();
        document.getElementById('locationSummaryText').textContent = summary;
        document.getElementById('locationSummary').classList.remove('d-none');
        document.getElementById('locationAddBtn').classList.add('d-none');
        modal.hide();
    });

    document.getElementById('locationClearBtn').addEventListener('click', function () {
        Object.values(fields).forEach(function (f) { f.value = ''; });
        ['hLocationName','hLocationAddress','hLocationCity','hLocationState','hLocationDistrict',
         'hLocationPostalCode','hLocationContactPerson','hLocationContactPhone'].forEach(function (id) {
            document.getElementById(id).value = '';
        });
        document.getElementById('locationSummary').classList.add('d-none');
        document.getElementById('locationAddBtn').classList.remove('d-none');
    });

    document.getElementById('locationEditBtn').addEventListener('click', function () { modal.show(); });

    /* State → district suggestions */
    var districtData = @json($districtsByState);
    var qcDistrictList = document.getElementById('qcDistrictOptions');
    ['input', 'change'].forEach(function (evt) {
        fields.state.addEventListener(evt, function () {
            qcDistrictList.replaceChildren(...(districtData[fields.state.value] || []).map(function (d) { return new Option(d); }));
        });
    });
})();

@if ($selectedContractorId)
/* Customer quick-create modal */
(function () {
    var modalEl = document.getElementById('adminQuickCreateCustomer');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var nameInput = document.getElementById('qcAdminCustomerName');
    var phoneInput = document.getElementById('qcAdminCustomerPhone');
    var errorEl = document.getElementById('qcAdminCustomerError');
    var saveBtn = document.getElementById('qcAdminCustomerSave');
    var select = document.getElementById('client_id');

    modalEl.addEventListener('hidden.bs.modal', function () {
        nameInput.value = ''; phoneInput.value = '';
        nameInput.classList.remove('is-invalid'); errorEl.textContent = '';
    });

    saveBtn.addEventListener('click', async function () {
        nameInput.classList.remove('is-invalid'); errorEl.textContent = '';
        var name = nameInput.value.trim();
        if (!name) { nameInput.classList.add('is-invalid'); errorEl.textContent = 'Name is required.'; return; }
        saveBtn.disabled = true;
        try {
            var body = { contractor_id: contractorId, name: name };
            if (phoneInput.value.trim()) body.phone = phoneInput.value.trim();
            var res = await fetch('{{ route('admin.clients.quick') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });
            var data = await res.json();
            if (!res.ok) {
                var msg = data.errors ? Object.values(data.errors)[0][0] : 'Failed to create.';
                nameInput.classList.add('is-invalid'); errorEl.textContent = msg; return;
            }
            select.disabled = false;
            var opt = new Option(data.name, data.id, true, true);
            select.add(opt);
            modal.hide();
        } catch (e) {
            nameInput.classList.add('is-invalid'); errorEl.textContent = 'Something went wrong. Please try again.';
        } finally { saveBtn.disabled = false; }
    });
})();
@endif
</script>
@endpush
