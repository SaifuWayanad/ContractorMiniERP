{{-- Shared visit form. Expects $visit, $clients (id => name). --}}
<div class="card form-card">
    <div class="card-body">
        <x-form.section title="Visit Details" :first="true">

            {{-- Customer CS-dropdown --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">Customer <span class="req">*</span></label>
                <div class="cs-wrapper" id="visitClientWrapper">
                    <div class="cs-trigger">
                        <span class="cs-value cs-placeholder">Select customer</span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                        <div class="cs-list" id="visitClientCsList">
                            @forelse($clients as $cId => $cName)
                                <div class="cs-item" data-value="{{ $cId }}">{{ $cName }}</div>
                            @empty
                                <div class="cs-item no-data">No customers yet</div>
                            @endforelse
                        </div>
                        <div class="cs-footer">
                            <button type="button" class="cs-add-btn" id="visitClientAddBtn">
                                <i class="bi bi-plus-circle"></i> Add new customer
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="client_id" id="visitClientIdInput" value="{{ old('client_id', $visit->client_id) }}" required>
                </div>
                @error('client_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <x-form.input name="meeting_date" label="Visit Date" type="date" :value="$visit->meeting_date?->format('Y-m-d') ?? today()->format('Y-m-d')" :required="true" col="col-md-4" />
            <x-form.input name="purpose" label="Purpose" :value="$visit->purpose" maxlength="200" placeholder="e.g. Site inspection, Follow-up…" col="col-md-8" />

            {{-- Location CS-dropdown --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">Location</label>
                <div class="cs-wrapper" id="visitLocationWrapper">
                    <div class="cs-trigger">
                        <span class="cs-value cs-placeholder">Select or add location...</span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                        <div class="cs-list" id="visitLocationCsList">
                            @forelse($visitLocations as $loc)
                                <div class="cs-item" data-value="{{ $loc }}">{{ $loc }}</div>
                            @empty
                                <div class="cs-item no-data">No locations yet</div>
                            @endforelse
                        </div>
                        <div class="cs-footer">
                            <button type="button" class="cs-add-btn" id="visitLocationAddBtn">
                                <i class="bi bi-plus-circle"></i> Add new location
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="location" id="visitLocationInput" value="{{ old('location', $visit->location) }}">
                </div>
            </div>
            <x-form.textarea name="description" label="Notes" :value="$visit->description" rows="3" maxlength="5000" />
            <x-form.input name="next_meeting_date" label="Next Visit Date" type="date" :value="$visit->next_meeting_date?->format('Y-m-d')" col="col-md-4" />
        </x-form.section>
    </div>
</div>

{{-- Add new location modal --}}
<div class="modal fade" id="visitAddLocationModal" tabindex="-1" aria-labelledby="visitAddLocationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="visitAddLocationModalLabel"><i class="bi bi-geo-alt me-2"></i>New Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="visitLocModalName" class="form-label">Location <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="visitLocModalName" maxlength="200" autocomplete="off" placeholder="e.g. Kalpetta, Wayanad…" />
                <div class="invalid-feedback" id="visitLocModalError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="visitLocModalSave">Add &amp; Select</button>
            </div>
        </div>
    </div>
</div>

{{-- Add new customer modal --}}
<div class="modal fade" id="visitAddCustomerModal" tabindex="-1" aria-labelledby="visitAddCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="visitAddCustomerModalLabel"><i class="bi bi-person-plus me-2"></i>New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="visitQcName" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="visitQcName" maxlength="150" autocomplete="off" />
                    <div class="invalid-feedback" id="visitQcError"></div>
                </div>
                <div class="mb-3">
                    <label for="visitQcPhone" class="form-label">Phone</label>
                    <input type="tel" class="form-control" id="visitQcPhone" maxlength="20" autocomplete="off" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="visitQcSave">Create &amp; Select</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // ── Location CS-wrapper ──
    var locationCs    = new CustomSelect('visitLocationWrapper');
    var locationInput = document.getElementById('visitLocationInput');
    var origLocationSelect = locationCs._select.bind(locationCs);
    locationCs._select = function(value, label) { origLocationSelect(value, label); if (locationInput) locationInput.value = value; };

    var addLocationModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('visitAddLocationModal'));
    var locModalName  = document.getElementById('visitLocModalName');
    var locModalError = document.getElementById('visitLocModalError');
    var locModalSave  = document.getElementById('visitLocModalSave');

    document.getElementById('visitLocationAddBtn').addEventListener('click', function (e) {
        e.stopPropagation();
        locationCs._close();
        addLocationModal.show();
        setTimeout(function() { if (locModalName) locModalName.focus(); }, 300);
    });

    document.getElementById('visitAddLocationModal').addEventListener('hidden.bs.modal', function () {
        locModalName.value = '';
        locModalName.classList.remove('is-invalid'); locModalError.textContent = '';
    });

    locModalSave.addEventListener('click', function () {
        locModalName.classList.remove('is-invalid'); locModalError.textContent = '';
        var val = locModalName.value.trim();
        if (!val) { locModalName.classList.add('is-invalid'); locModalError.textContent = 'Location is required.'; return; }
        var noData = document.querySelector('#visitLocationCsList .no-data');
        if (noData) noData.remove();
        locationCs.addOption(val, val, true);
        if (locationInput) locationInput.value = val;
        addLocationModal.hide();
    });

    @if(old('location', $visit->location))
    locationCs.selectByValue('{{ old('location', $visit->location) }}');
    @endif

    // ── Customer CS-wrapper ──
    var clientLocations = @json($clientLocations ?? []);
    var clientCs    = new CustomSelect('visitClientWrapper');
    var clientInput = document.getElementById('visitClientIdInput');
    var origClientSelect = clientCs._select.bind(clientCs);
    clientCs._select = function(value, label) {
        origClientSelect(value, label);
        if (clientInput) clientInput.value = value;
        if (locationInput && !locationInput.value && clientLocations[value]) {
            var loc = clientLocations[value];
            var noData = document.querySelector('#visitLocationCsList .no-data');
            if (noData) noData.remove();
            locationCs.addOption(loc, loc, true);
            locationInput.value = loc;
        }
    };

    var addCustomerModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('visitAddCustomerModal'));
    var qcName  = document.getElementById('visitQcName');
    var qcPhone = document.getElementById('visitQcPhone');
    var qcError = document.getElementById('visitQcError');
    var qcSave  = document.getElementById('visitQcSave');

    document.getElementById('visitClientAddBtn').addEventListener('click', function (e) {
        e.stopPropagation();
        clientCs._close();
        addCustomerModal.show();
        setTimeout(function() { if (qcName) qcName.focus(); }, 300);
    });

    document.getElementById('visitAddCustomerModal').addEventListener('hidden.bs.modal', function () {
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
            clientCs.addOption(data.id, data.name, true);
            addCustomerModal.hide();
        } catch (e) {
            qcName.classList.add('is-invalid'); qcError.textContent = 'Something went wrong.';
        } finally { qcSave.disabled = false; }
    });
})();
</script>
@endpush
