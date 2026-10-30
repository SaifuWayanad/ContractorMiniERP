@extends('layouts.app')

@section('title', 'New Project')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
                <span><i class="bi bi-kanban me-2"></i>Create New Project</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('projects.store') }}" id="projectForm">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Project Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Budget</label>
                            <input type="number" step="0.01" name="budget" class="form-control @error('budget') is-invalid @enderror" value="{{ old('budget') }}">
                            @error('budget')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                            <div class="cs-wrapper" id="customerSelectWrapper">
                                <div class="cs-trigger @error('customerId') is-invalid @enderror" tabindex="0">
                                    <span class="cs-value cs-placeholder">Select a customer...</span>
                                    <i class="bi bi-chevron-down cs-arrow"></i>
                                </div>
                                <div class="cs-panel">
                                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                                    <div class="cs-list">
                                        @php $customerItems = $customers['items'] ?? $customers['data'] ?? []; @endphp
                                        @forelse($customerItems as $cust)
                                            @php $c = is_array($cust) ? $cust : []; @endphp
                                            <div class="cs-item" data-value="{{ $c['id'] ?? '' }}" data-location-id="{{ $c['locationId'] ?? '' }}" data-location-name="{{ $c['locationName'] ?? '' }}">{{ $c['customerName'] ?? '' }}</div>
                                        @empty
                                            <div class="cs-item no-data">No customers yet</div>
                                        @endforelse
                                    </div>
                                    <div class="cs-footer">
                                        <button type="button" class="cs-add-btn" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                                            <i class="bi bi-plus"></i> Add new customer
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="customerId" id="customerSelectInput" value="{{ old('customerId') }}">
                            </div>
                            @error('customerId')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Location <span class="text-danger">*</span></label>
                            <div class="cs-wrapper" id="locationSelectWrapper">
                                <div class="cs-trigger @error('locationId') is-invalid @enderror" tabindex="0">
                                    <span class="cs-value cs-placeholder">Select a location...</span>
                                    <i class="bi bi-chevron-down cs-arrow"></i>
                                </div>
                                <div class="cs-panel">
                                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                                    <div class="cs-list">
                                        @php $locationItems = $locations['items'] ?? $locations['data'] ?? []; @endphp
                                        @forelse($locationItems as $loc)
                                            @php $l = is_array($loc) ? $loc : []; @endphp
                                            <div class="cs-item" data-value="{{ $l['id'] ?? '' }}">{{ $l['locationName'] ?? '' }}</div>
                                        @empty
                                            <div class="cs-item no-data">No locations yet</div>
                                        @endforelse
                                    </div>
                                    <div class="cs-footer">
                                        <button type="button" class="cs-add-btn" data-bs-toggle="modal" data-bs-target="#addLocationModal">
                                            <i class="bi bi-plus"></i> Add new location
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="locationId" id="locationSelectInput" value="{{ old('locationId') }}">
                            </div>
                            @error('locationId')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Project Address</label>
                            <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}">
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Start Date</label>
                            <input type="date" name="startDate" id="startDate" class="form-control @error('startDate') is-invalid @enderror" value="{{ old('startDate') }}">
                            @error('startDate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Expected End Date</label>
                            <input type="date" name="endDate" id="endDate" class="form-control @error('endDate') is-invalid @enderror" value="{{ old('endDate') }}">
                            @error('endDate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="on-hold" {{ old('status') === 'on-hold' ? 'selected' : '' }}>On Hold</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @if((session('auth_user')['role'] ?? '') === 'Admin')
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Assigned Contractor <span class="text-muted">(optional)</span></label>
                            <div class="cs-wrapper" id="contractorSelectWrapper">
                                <div class="cs-trigger" tabindex="0">
                                    <span class="cs-value cs-placeholder">None (unassigned)</span>
                                    <i class="bi bi-chevron-down cs-arrow"></i>
                                </div>
                                <div class="cs-panel">
                                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search contractors..."></div>
                                    <div class="cs-list" id="contractorList">
                                        <div class="cs-item" data-value="">None (unassigned)</div>
                                        <div class="cs-item no-data" id="contractorsLoading">Loading...</div>
                                    </div>
                                </div>
                                <input type="hidden" name="assignedUserId" id="contractorSelectInput" value="{{ old('assignedUserId', '') }}">
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Create Project</button>
                        <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="customerModalError" class="alert alert-danger d-none"></div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                    <input type="text" id="newCustomerName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" id="newCustomerPhone" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" id="newCustomerEmail" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveCustomerBtn">
                    <i class="bi bi-check-circle me-1"></i>Save Customer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Location Modal -->
<div class="modal fade" id="addLocationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-geo-alt me-2"></i>Add New Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="locationModalError" class="alert alert-danger d-none"></div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Location Name <span class="text-danger">*</span></label>
                    <input type="text" id="newLocationName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea id="newLocationDesc" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveLocationBtn">
                    <i class="bi bi-check-circle me-1"></i>Save Location
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

new CustomSelect('customerSelectWrapper');
new CustomSelect('locationSelectWrapper');

// Auto-fill location from customer's default location
document.getElementById('customerSelectInput').addEventListener('change', function() {
    const cs = csRegistry['customerSelectWrapper'];
    if (!cs) return;
    const selected = cs.list.querySelector('.cs-item.selected');
    if (!selected) return;
    const locId = selected.dataset.locationId;
    const locName = selected.dataset.locationName;
    if (locId && locName) {
        csRegistry['locationSelectWrapper']._select(locId, locName);
    }
});

// Date validation
document.getElementById('endDate').addEventListener('change', function() {
    const start = document.getElementById('startDate').value;
    const end = this.value;
    if (start && end && end < start) {
        this.setCustomValidity('End date must be on or after start date');
    } else {
        this.setCustomValidity('');
    }
});

// Add Customer
document.getElementById('saveCustomerBtn').addEventListener('click', async function() {
    const name = document.getElementById('newCustomerName').value.trim();
    const errDiv = document.getElementById('customerModalError');
    if (!name) { errDiv.textContent = 'Name is required.'; errDiv.classList.remove('d-none'); return; }
    errDiv.classList.add('d-none');
    this.disabled = true;
    try {
        const resp = await fetch('/api-proxy/customers', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ customerName: name, phoneNumber: document.getElementById('newCustomerPhone').value })
        });
        const data = await resp.json();
        if (!resp.ok) { errDiv.textContent = data.errors ? Object.values(data.errors).flat().join(', ') : (data.error || 'Failed to save.'); errDiv.classList.remove('d-none'); }
        else {
            csRegistry['customerSelectWrapper'].addOption(data.id, data.customerName);
            bootstrap.Modal.getInstance(document.getElementById('addCustomerModal')).hide();
            document.getElementById('newCustomerName').value = '';
            document.getElementById('newCustomerPhone').value = '';
            document.getElementById('newCustomerEmail').value = '';
        }
    } catch(e) { errDiv.textContent = 'Connection error. Please try again.'; errDiv.classList.remove('d-none'); }
    this.disabled = false;
});

// Add Location
document.getElementById('saveLocationBtn').addEventListener('click', async function() {
    const name = document.getElementById('newLocationName').value.trim();
    const errDiv = document.getElementById('locationModalError');
    if (!name) { errDiv.textContent = 'Name is required.'; errDiv.classList.remove('d-none'); return; }
    errDiv.classList.add('d-none');
    this.disabled = true;
    try {
        const resp = await fetch('/api-proxy/locations', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ locationName: name, notes: document.getElementById('newLocationDesc').value })
        });
        const data = await resp.json();
        if (!resp.ok) { errDiv.textContent = data.errors ? Object.values(data.errors).flat().join(', ') : (data.error || 'Failed to save.'); errDiv.classList.remove('d-none'); }
        else {
            csRegistry['locationSelectWrapper'].addOption(data.id, data.locationName);
            bootstrap.Modal.getInstance(document.getElementById('addLocationModal')).hide();
            document.getElementById('newLocationName').value = '';
            document.getElementById('newLocationDesc').value = '';
        }
    } catch(e) { errDiv.textContent = 'Connection error. Please try again.'; errDiv.classList.remove('d-none'); }
    this.disabled = false;
});

// Load contractors
(function() {
    const csWrap = document.getElementById('contractorSelectWrapper');
    if (!csWrap) return;
    const cs = new CustomSelect('contractorSelectWrapper');
    const loading = document.getElementById('contractorsLoading');
    fetch('/api-proxy/contractors')
        .then(r => r.json())
        .then(contractors => {
            if (loading) loading.remove();
            if (!Array.isArray(contractors) || contractors.length === 0) {
                const nd = document.createElement('div');
                nd.className = 'cs-item no-data';
                nd.textContent = 'No contractors available — add a Contractor user first';
                cs.list.appendChild(nd);
                return;
            }
            const oldVal = '{{ old('assignedUserId', '') }}';
            contractors.forEach(c => {
                const item = document.createElement('div');
                item.className = 'cs-item';
                item.dataset.value = String(c.id);
                item.textContent = c.fullName + ' (' + c.username + ')';
                cs.list.appendChild(item);
            });
            cs._updateSearch();
            if (oldVal) { const it = cs.list.querySelector('[data-value="' + CSS.escape(oldVal) + '"]'); if (it) cs._select(oldVal, it.textContent.trim()); }
        })
        .catch(() => { if (loading) loading.textContent = 'Could not load contractors'; });
})();

let formDirty = false;
document.getElementById('projectForm').addEventListener('input', () => formDirty = true);
window.addEventListener('beforeunload', e => { if (formDirty) { e.preventDefault(); e.returnValue = ''; } });
document.getElementById('projectForm').addEventListener('submit', () => formDirty = false);
</script>
@endsection
