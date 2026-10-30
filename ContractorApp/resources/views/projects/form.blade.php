{{-- Shared project form fields. Expects $project, $clients (id => name), $workTypes (collection). --}}
<div class="card form-card">
    <div class="card-body">
        @if ($project->exists)
            {{-- Edit mode --}}
            <x-form.section title="Project Information" :first="true">
                <x-form.input name="name" label="Project Name" :value="$project->name" :required="true" maxlength="150" autofocus />
                <x-form.input name="project_number" label="Project Number" :value="$project->project_number" maxlength="30" />

                {{-- Customer CS-dropdown --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Customer <span class="req">*</span></label>
                    <div class="cs-wrapper" id="clientWrapper">
                        <div class="cs-trigger">
                            <span class="cs-value cs-placeholder">Select customer</span>
                            <i class="bi bi-chevron-down cs-arrow"></i>
                        </div>
                        <div class="cs-panel">
                            <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                            <div class="cs-list" id="clientCsList">
                                @forelse($clients as $cId => $cName)
                                    <div class="cs-item" data-value="{{ $cId }}">{{ $cName }}</div>
                                @empty
                                    <div class="cs-item no-data">No customers yet</div>
                                @endforelse
                            </div>
                            <div class="cs-footer">
                                <button type="button" class="cs-add-btn" id="clientAddBtn">
                                    <i class="bi bi-plus-circle"></i> Add new customer
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="client_id" id="clientIdInput" value="{{ old('client_id', $project->client_id) }}" required>
                    </div>
                    @error('client_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                {{-- Type of Work CS-dropdown --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Type of Work</label>
                    <div class="cs-wrapper" id="workTypeWrapper">
                        <div class="cs-trigger">
                            <span class="cs-value cs-placeholder">Select or add type...</span>
                            <i class="bi bi-chevron-down cs-arrow"></i>
                        </div>
                        <div class="cs-panel">
                            <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                            <div class="cs-list" id="workTypeCsList">
                                @forelse($workTypes as $wt)
                                    <div class="cs-item" data-value="{{ $wt->name }}">{{ $wt->name }}</div>
                                @empty
                                    <div class="cs-item no-data" id="workTypeNoData">No types yet</div>
                                @endforelse
                            </div>
                            <div class="cs-footer">
                                <button type="button" class="cs-add-btn" id="wtAddBtn">
                                    <i class="bi bi-plus-circle"></i> Add new type
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="type_of_work" id="workTypeInput" value="{{ old('type_of_work', $project->type_of_work) }}">
                    </div>
                </div>

                {{-- Location CS-dropdown --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Location</label>
                    <div class="cs-wrapper" id="locationWrapper">
                        <div class="cs-trigger">
                            <span class="cs-value cs-placeholder">Select or add location...</span>
                            <i class="bi bi-chevron-down cs-arrow"></i>
                        </div>
                        <div class="cs-panel">
                            <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                            <div class="cs-list" id="locationCsList">
                                @forelse($locations as $loc)
                                    <div class="cs-item" data-value="{{ $loc }}">{{ $loc }}</div>
                                @empty
                                    <div class="cs-item no-data">No locations yet</div>
                                @endforelse
                            </div>
                            <div class="cs-footer">
                                <button type="button" class="cs-add-btn" id="locationAddBtn">
                                    <i class="bi bi-plus-circle"></i> Add new location
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="location" id="locationInput" value="{{ old('location', $project->location) }}">
                    </div>
                </div>

                <x-form.select name="status" label="Status" :options="App\Enums\ProjectStatus::options()" :value="$project->status" :required="true" />
                <x-form.textarea name="description" label="Description" :value="$project->description" rows="3" maxlength="5000" />
            </x-form.section>

            <x-form.section title="Schedule & Budget">
                <x-form.input name="start_date" label="Start Date" type="date" :value="$project->start_date?->format('Y-m-d')" />
                <x-form.input name="expected_end_date" label="Expected End Date" type="date" :value="$project->expected_end_date?->format('Y-m-d')" />
                <x-form.input name="actual_end_date" label="Actual End Date" type="date" :value="$project->actual_end_date?->format('Y-m-d')" />
                <x-form.input name="budget" label="Budget" type="number" step="0.01" min="0" :value="$project->budget" prepend="₹" />
            </x-form.section>
        @else
            {{-- Create mode --}}
            <x-form.section title="Project Information" :first="true">
                <x-form.input name="name" label="Project Name" :value="$project->name" :required="true" maxlength="150" autofocus />

                {{-- Customer CS-dropdown --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Customer <span class="req">*</span></label>
                    <div class="cs-wrapper" id="clientWrapper">
                        <div class="cs-trigger">
                            <span class="cs-value cs-placeholder">Select customer</span>
                            <i class="bi bi-chevron-down cs-arrow"></i>
                        </div>
                        <div class="cs-panel">
                            <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                            <div class="cs-list" id="clientCsList">
                                @forelse($clients as $cId => $cName)
                                    <div class="cs-item" data-value="{{ $cId }}">{{ $cName }}</div>
                                @empty
                                    <div class="cs-item no-data">No customers yet</div>
                                @endforelse
                            </div>
                            <div class="cs-footer">
                                <button type="button" class="cs-add-btn" id="clientAddBtn">
                                    <i class="bi bi-plus-circle"></i> Add new customer
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="client_id" id="clientIdInput" value="{{ old('client_id', $project->client_id) }}" required>
                    </div>
                    @error('client_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                {{-- Type of Work CS-dropdown --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Type of Work</label>
                    <div class="cs-wrapper" id="workTypeWrapper">
                        <div class="cs-trigger">
                            <span class="cs-value cs-placeholder">Select or add type...</span>
                            <i class="bi bi-chevron-down cs-arrow"></i>
                        </div>
                        <div class="cs-panel">
                            <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                            <div class="cs-list" id="workTypeCsList">
                                @forelse($workTypes as $wt)
                                    <div class="cs-item" data-value="{{ $wt->name }}">{{ $wt->name }}</div>
                                @empty
                                    <div class="cs-item no-data" id="workTypeNoData">No types yet</div>
                                @endforelse
                            </div>
                            <div class="cs-footer">
                                <button type="button" class="cs-add-btn" id="wtAddBtn">
                                    <i class="bi bi-plus-circle"></i> Add new type
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="type_of_work" id="workTypeInput" value="{{ old('type_of_work', $project->type_of_work) }}">
                    </div>
                </div>

                {{-- Location CS-dropdown --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Location</label>
                    <div class="cs-wrapper" id="locationWrapper">
                        <div class="cs-trigger">
                            <span class="cs-value cs-placeholder">Select or add location...</span>
                            <i class="bi bi-chevron-down cs-arrow"></i>
                        </div>
                        <div class="cs-panel">
                            <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                            <div class="cs-list" id="locationCsList">
                                @forelse($locations as $loc)
                                    <div class="cs-item" data-value="{{ $loc }}">{{ $loc }}</div>
                                @empty
                                    <div class="cs-item no-data">No locations yet</div>
                                @endforelse
                            </div>
                            <div class="cs-footer">
                                <button type="button" class="cs-add-btn" id="locationAddBtn">
                                    <i class="bi bi-plus-circle"></i> Add new location
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="location" id="locationInput" value="{{ old('location', $project->location) }}">
                    </div>
                </div>
            </x-form.section>

            <x-form.section title="Description">
                <x-form.textarea name="description" label="Description" :value="$project->description" rows="3" maxlength="5000" />
            </x-form.section>

            <x-form.section title="Schedule & Budget">
                <x-form.input name="start_date" label="Start Date" type="date" :value="$project->start_date?->format('Y-m-d')" />
                <x-form.input name="expected_end_date" label="Expected End Date" type="date" :value="$project->expected_end_date?->format('Y-m-d')" />
                <x-form.input name="budget" label="Budget" type="number" step="0.01" min="0" :value="$project->budget" prepend="₹" />
            </x-form.section>
        @endif
    </div>
</div>

{{-- Add new location modal --}}
<div class="modal fade" id="addLocationModal" tabindex="-1" aria-labelledby="addLocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addLocationModalLabel"><i class="bi bi-geo-alt me-2"></i>New Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="locationModalName" class="form-label">Location <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="locationModalName" maxlength="150" autocomplete="off" placeholder="e.g. Kozhikode, Calicut…" />
                <div class="invalid-feedback" id="locationModalError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="locationModalSave">Add &amp; Select</button>
            </div>
        </div>
    </div>
</div>

{{-- Add new type of work modal --}}
<div class="modal fade" id="addWorkTypeModal" tabindex="-1" aria-labelledby="addWorkTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addWorkTypeModalLabel"><i class="bi bi-tools me-2"></i>New Type of Work</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="wtModalName" class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="wtModalName" maxlength="100" autocomplete="off" placeholder="e.g. Interior, Exterior…" />
                <div class="invalid-feedback" id="wtModalError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="wtModalSave">Create &amp; Select</button>
            </div>
        </div>
    </div>
</div>

{{-- Add new customer modal --}}
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCustomerModalLabel"><i class="bi bi-person-plus me-2"></i>New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="qcCustomerName" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="qcCustomerName" maxlength="150" autocomplete="off" />
                    <div class="invalid-feedback" id="qcCustomerError"></div>
                </div>
                <div class="mb-3">
                    <label for="qcCustomerPhone" class="form-label">Phone</label>
                    <input type="tel" class="form-control" id="qcCustomerPhone" maxlength="20" autocomplete="off" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="qcCustomerSave">Create &amp; Select</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // ── Customer CS-wrapper ──
    var clientCs    = new CustomSelect('clientWrapper');
    var clientInput = document.getElementById('clientIdInput');
    var origClientSelect = clientCs._select.bind(clientCs);
    clientCs._select = function(value, label) { origClientSelect(value, label); if (clientInput) clientInput.value = value; };

    var addCustomerModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addCustomerModal'));
    var qcName   = document.getElementById('qcCustomerName');
    var qcPhone  = document.getElementById('qcCustomerPhone');
    var qcError  = document.getElementById('qcCustomerError');
    var qcSave   = document.getElementById('qcCustomerSave');

    document.getElementById('clientAddBtn').addEventListener('click', function (e) {
        e.stopPropagation();
        clientCs._close();
        addCustomerModal.show();
        setTimeout(function() { if (qcName) qcName.focus(); }, 300);
    });

    document.getElementById('addCustomerModal').addEventListener('hidden.bs.modal', function () {
        qcName.value = ''; qcPhone.value = '';
        qcName.classList.remove('is-invalid'); qcError.textContent = '';
    });

    qcSave.addEventListener('click', async function () {
        qcName.classList.remove('is-invalid'); qcError.textContent = '';
        var name = qcName.value.trim();
        if (!name) { qcName.classList.add('is-invalid'); qcError.textContent = 'Name is required.'; return; }
        qcSave.disabled = true;
        try {
            var body = { name: name };
            if (qcPhone.value.trim()) body.phone = qcPhone.value.trim();
            var res = await fetch('{{ route('clients.quick') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });
            var data = await res.json();
            if (!res.ok) {
                var msg = data.errors ? Object.values(data.errors)[0][0] : 'Failed to create.';
                qcName.classList.add('is-invalid'); qcError.textContent = msg; return;
            }
            var noData = document.querySelector('#clientCsList .no-data');
            if (noData) noData.remove();
            clientCs.addOption(data.id, data.name, true);
            if (clientInput) clientInput.value = data.id;
            addCustomerModal.hide();
        } catch (e) {
            qcName.classList.add('is-invalid'); qcError.textContent = 'Something went wrong.';
        } finally { qcSave.disabled = false; }
    });

    // ── Location CS-wrapper ──
    var locationCs    = new CustomSelect('locationWrapper');
    var locationInput = document.getElementById('locationInput');
    var origLocationSelect = locationCs._select.bind(locationCs);
    locationCs._select = function(value, label) { origLocationSelect(value, label); if (locationInput) locationInput.value = value; };

    var addLocationModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addLocationModal'));
    var locModalName  = document.getElementById('locationModalName');
    var locModalError = document.getElementById('locationModalError');
    var locModalSave  = document.getElementById('locationModalSave');

    document.getElementById('locationAddBtn').addEventListener('click', function (e) {
        e.stopPropagation();
        locationCs._close();
        addLocationModal.show();
        setTimeout(function() { if (locModalName) locModalName.focus(); }, 300);
    });

    document.getElementById('addLocationModal').addEventListener('hidden.bs.modal', function () {
        locModalName.value = '';
        locModalName.classList.remove('is-invalid'); locModalError.textContent = '';
    });

    locModalSave.addEventListener('click', function () {
        locModalName.classList.remove('is-invalid'); locModalError.textContent = '';
        var val = locModalName.value.trim();
        if (!val) { locModalName.classList.add('is-invalid'); locModalError.textContent = 'Location is required.'; return; }
        var noData = document.querySelector('#locationCsList .no-data');
        if (noData) noData.remove();
        locationCs.addOption(val, val, true);
        if (locationInput) locationInput.value = val;
        addLocationModal.hide();
    });

    // ── Type of Work CS-wrapper ──
    var wtCs    = new CustomSelect('workTypeWrapper');
    var wtInput = document.getElementById('workTypeInput');
    var origWtSelect = wtCs._select.bind(wtCs);
    wtCs._select = function(value, label) { origWtSelect(value, label); if (wtInput) wtInput.value = value; };

    var addWorkTypeModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addWorkTypeModal'));
    var wtModalName  = document.getElementById('wtModalName');
    var wtModalError = document.getElementById('wtModalError');
    var wtModalSave  = document.getElementById('wtModalSave');

    document.getElementById('wtAddBtn').addEventListener('click', function (e) {
        e.stopPropagation();
        wtCs._close();
        addWorkTypeModal.show();
        setTimeout(function() { if (wtModalName) wtModalName.focus(); }, 300);
    });

    document.getElementById('addWorkTypeModal').addEventListener('hidden.bs.modal', function () {
        wtModalName.value = '';
        wtModalName.classList.remove('is-invalid'); wtModalError.textContent = '';
    });

    wtModalSave.addEventListener('click', async function () {
        wtModalName.classList.remove('is-invalid'); wtModalError.textContent = '';
        var name = wtModalName.value.trim();
        if (!name) { wtModalName.classList.add('is-invalid'); wtModalError.textContent = 'Name is required.'; return; }
        wtModalSave.disabled = true;
        try {
            var res = await fetch('{{ route('work-types.quick') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ name: name }),
            });
            var data = await res.json();
            if (!res.ok) {
                var msg = data.errors ? Object.values(data.errors).flat().join(' ') : 'Error saving.';
                wtModalName.classList.add('is-invalid'); wtModalError.textContent = msg; return;
            }
            var noData = document.querySelector('#workTypeCsList .no-data');
            if (noData) noData.remove();
            wtCs.addOption(data.name, data.name, true);
            if (wtInput) wtInput.value = data.name;
            addWorkTypeModal.hide();
        } catch (e) {
            wtModalName.classList.add('is-invalid'); wtModalError.textContent = 'Something went wrong.';
        } finally { wtModalSave.disabled = false; }
    });

})();
</script>
@endpush
