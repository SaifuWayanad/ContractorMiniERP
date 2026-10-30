{{-- Shared client form fields. Expects $client. --}}
<div class="card form-card">
    <div class="card-body">
        @if ($client->exists)
            {{-- Edit mode: show all fields --}}
            <x-form.section title="Customer Information" :first="true">
                <x-form.input name="name" label="Customer Name" :value="$client->name" :required="true" maxlength="120" autofocus />
                <x-form.input name="company" label="Company" :value="$client->company" maxlength="150" />
                <x-form.input name="contact_person" label="Contact Person" :value="$client->contact_person" maxlength="100" />
                <x-form.input name="email" label="Email" type="email" :value="$client->email" maxlength="150" />
                <x-form.input name="phone" label="Phone" type="tel" :value="$client->phone" maxlength="20" inputmode="tel" />
            </x-form.section>

            <x-form.section title="Address Information">
                <x-form.input name="address" label="Address" :value="$client->address" maxlength="250" col="col-12 col-xl-8" />
                <x-form.input name="city" label="City" :value="$client->city" maxlength="80" />
                <x-form.input name="state" label="State" :value="$client->state" maxlength="80" list="stateOptions" />
                <x-form.input name="country" label="Country" :value="$client->country" maxlength="80" />
                <x-form.input name="postal_code" label="Postal Code" :value="$client->postal_code" maxlength="12" inputmode="numeric" />
                <datalist id="stateOptions">
                    @foreach (app(App\Services\LocationService::class)->states() as $state)
                        <option value="{{ $state }}"></option>
                    @endforeach
                </datalist>
            </x-form.section>

            <x-form.section title="Additional Information">
                <x-form.select name="status" label="Status" :options="App\Enums\ClientStatus::options()" :value="$client->status" :required="true" />
                <x-form.textarea name="notes" label="Notes" :value="$client->notes" rows="4" maxlength="5000" />
            </x-form.section>
        @else
            {{-- Create mode: simplified with "More options" toggle --}}
            <x-form.section title="Customer Information" :first="true">
                <x-form.input name="name" label="Customer Name" :value="$client->name" :required="true" maxlength="120" autofocus />
                <x-form.input name="phone" label="Phone" type="tel" :value="$client->phone" maxlength="20" inputmode="tel" />

                {{-- City / Location CS-dropdown --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold">City / Location</label>
                    <div class="cs-wrapper" id="clientCityWrapper">
                        <div class="cs-trigger">
                            <span class="cs-value cs-placeholder">Select or add location...</span>
                            <i class="bi bi-chevron-down cs-arrow"></i>
                        </div>
                        <div class="cs-panel">
                            <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search..."></div>
                            <div class="cs-list" id="clientCityCsList">
                                @forelse($locations ?? [] as $loc)
                                    <div class="cs-item" data-value="{{ $loc }}">{{ $loc }}</div>
                                @empty
                                    <div class="cs-item no-data">No locations yet</div>
                                @endforelse
                            </div>
                            <div class="cs-footer">
                                <button type="button" class="cs-add-btn" id="clientCityAddBtn">
                                    <i class="bi bi-plus-circle"></i> Add new location
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="city" id="clientCityInput" value="{{ old('city', $client->city) }}">
                    </div>
                </div>

                <x-form.input name="address" label="Address" :value="$client->address" maxlength="250" col="col-12 col-xl-8" />
            </x-form.section>

            <div class="mb-3">
                <button type="button" class="btn btn-link btn-sm px-0 text-decoration-none" data-bs-toggle="collapse" data-bs-target="#moreOptions" aria-expanded="false" aria-controls="moreOptions">
                    <i class="bi bi-chevron-down me-1" id="moreOptionsChevron"></i>More options
                </button>
            </div>

            <div class="collapse" id="moreOptions">
                <x-form.section title="More Details">
                    <x-form.input name="company" label="Company" :value="$client->company" maxlength="150" />
                    <x-form.input name="contact_person" label="Contact Person" :value="$client->contact_person" maxlength="100" />
                    <x-form.input name="email" label="Email" type="email" :value="$client->email" maxlength="150" />
                    <x-form.input name="city" label="City" :value="$client->city" maxlength="80" />
                    <x-form.input name="state" label="State" :value="$client->state" maxlength="80" list="stateOptions" />
                    <x-form.input name="country" label="Country" :value="$client->country" maxlength="80" />
                    <x-form.input name="postal_code" label="Postal Code" :value="$client->postal_code" maxlength="12" inputmode="numeric" />
                    <datalist id="stateOptions">
                        @foreach (app(App\Services\LocationService::class)->states() as $state)
                            <option value="{{ $state }}"></option>
                        @endforeach
                    </datalist>
                    <x-form.textarea name="notes" label="Notes" :value="$client->notes" rows="4" maxlength="5000" />
                </x-form.section>
            </div>
        @endif
    </div>
</div>

@if (!$client->exists)
{{-- Add new location modal --}}
<div class="modal fade" id="clientCityAddModal" tabindex="-1" aria-labelledby="clientCityAddModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clientCityAddModalLabel"><i class="bi bi-geo-alt me-2"></i>New Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="clientCityModalName" class="form-label">Location <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="clientCityModalName" maxlength="80" autocomplete="off" placeholder="e.g. Kalpetta, Wayanad…" />
                <div class="invalid-feedback" id="clientCityModalError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="clientCityModalSave">Add &amp; Select</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var el = document.getElementById('moreOptions');
        var chevron = document.getElementById('moreOptionsChevron');
        if (el) {
            el.addEventListener('show.bs.collapse', function () { chevron.className = 'bi bi-chevron-up me-1'; });
            el.addEventListener('hide.bs.collapse', function () { chevron.className = 'bi bi-chevron-down me-1'; });
        }

        // ── City / Location CS-wrapper ──
        var cityCs    = new CustomSelect('clientCityWrapper');
        var cityInput = document.getElementById('clientCityInput');
        var origCitySelect = cityCs._select.bind(cityCs);
        cityCs._select = function(value, label) { origCitySelect(value, label); if (cityInput) cityInput.value = value; };

        var addCityModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('clientCityAddModal'));
        var cityModalName  = document.getElementById('clientCityModalName');
        var cityModalError = document.getElementById('clientCityModalError');
        var cityModalSave  = document.getElementById('clientCityModalSave');

        document.getElementById('clientCityAddBtn').addEventListener('click', function (e) {
            e.stopPropagation();
            cityCs._close();
            addCityModal.show();
            setTimeout(function() { if (cityModalName) cityModalName.focus(); }, 300);
        });

        document.getElementById('clientCityAddModal').addEventListener('hidden.bs.modal', function () {
            cityModalName.value = '';
            cityModalName.classList.remove('is-invalid'); cityModalError.textContent = '';
        });

        cityModalSave.addEventListener('click', function () {
            cityModalName.classList.remove('is-invalid'); cityModalError.textContent = '';
            var val = cityModalName.value.trim();
            if (!val) { cityModalName.classList.add('is-invalid'); cityModalError.textContent = 'Location is required.'; return; }
            var noData = document.querySelector('#clientCityCsList .no-data');
            if (noData) noData.remove();
            cityCs.addOption(val, val, true);
            if (cityInput) cityInput.value = val;
            addCityModal.hide();
        });

        @if(old('city', $client->city))
        cityCs.selectByValue('{{ old('city', $client->city) }}');
        @endif
    })();
</script>
@endif
