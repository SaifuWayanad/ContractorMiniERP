@extends('layouts.app')

@section('title', 'Edit Customer')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <a href="{{ route('customers.show', $customer['id'] ?? 0) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
                <span><i class="bi bi-pencil me-2"></i>Edit Customer: {{ $customer['customerName'] ?? '' }}</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('customers.update', $customer['id'] ?? 0) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $customer['customerName'] ?? '') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $customer['phoneNumber'] ?? '') }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Location</label>
                            @php $selLocation = old('locationId') ? '' : ($customer['locationName'] ?? ''); @endphp
                            <div class="cs-wrapper" id="locationSelectWrapper">
                                <div class="cs-trigger @error('locationId') is-invalid @enderror" tabindex="0">
                                    <span class="cs-value {{ $selLocation ? '' : 'cs-placeholder' }}">{{ $selLocation ?: 'Select a location (optional)...' }}</span>
                                    <i class="bi bi-chevron-down cs-arrow"></i>
                                </div>
                                <div class="cs-panel">
                                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                                    <div class="cs-list">
                                        @foreach($locations['items'] ?? $locations['data'] ?? $locations as $loc)
                                            @php $l = is_array($loc) ? $loc : []; @endphp
                                            <div class="cs-item" data-value="{{ $l['id'] ?? '' }}">{{ $l['locationName'] ?? '' }}</div>
                                        @endforeach
                                    </div>
                                    <div class="cs-footer">
                                        <button type="button" class="cs-add-btn" data-bs-toggle="modal" data-bs-target="#addLocationModal">
                                            <i class="bi bi-plus"></i> Add new location
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" name="locationId" id="locationSelectInput" value="{{ old('locationId', $customer['locationId'] ?? '') }}">
                            </div>
                            @error('locationId')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $customer['address'] ?? '') }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $customer['notes'] ?? '') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i>Update Customer</button>
                        <a href="{{ route('customers.show', $customer['id'] ?? 0) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
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

new CustomSelect('locationSelectWrapper');

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
</script>
@endsection
