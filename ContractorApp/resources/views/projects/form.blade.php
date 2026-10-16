{{-- Shared project form fields. Expects $project, $clients (id => name), $dealers (id => label). --}}
<div class="card form-card">
    <div class="card-body">
        @if ($project->exists)
            {{-- Edit mode: show all fields --}}
            <x-form.section title="Project Information" :first="true">
                <x-form.input name="name" label="Project Name" :value="$project->name" :required="true" maxlength="150" autofocus />
                <x-form.input name="project_number" label="Project Number" :value="$project->project_number" maxlength="30" />
                <x-form.select name="client_id" label="Customer" :options="$clients" :value="$project->client_id" placeholder="Select customer" :required="true" />
                <x-form.input name="type_of_work" label="Type of Work" :value="$project->type_of_work" list="typeOfWorkOptions" placeholder="Pick or type" maxlength="80" />
                <datalist id="typeOfWorkOptions">
                    @foreach (['Residential – House', 'Residential – Villas', 'Residential – Apartment', 'Commercial – Office', 'Commercial – Retail', 'Industrial – Warehouse', 'Industrial – Factory', 'Institutional', 'Infrastructure – Road / Bridge', 'Renovation'] as $option)
                        <option value="{{ $option }}"></option>
                    @endforeach
                </datalist>
                <x-form.select name="status" label="Status" :options="App\Enums\ProjectStatus::options()" :value="$project->status" :required="true" />
                <x-form.textarea name="description" label="Description" :value="$project->description" rows="3" maxlength="5000" />
            </x-form.section>

            <x-form.section title="Schedule & Budget">
                <x-form.input name="start_date" label="Start Date" type="date" :value="$project->start_date?->format('Y-m-d')" />
                <x-form.input name="expected_end_date" label="Expected End Date" type="date" :value="$project->expected_end_date?->format('Y-m-d')" />
                <x-form.input name="actual_end_date" label="Actual End Date" type="date" :value="$project->actual_end_date?->format('Y-m-d')" />
                <x-form.input name="budget" label="Budget" type="number" step="0.01" min="0" :value="$project->budget" prepend="₹" />
            </x-form.section>

            <x-form.section title="Material Requirement">
                <x-form.select name="dealer_id" label="Contact" :options="$dealers" :value="$project->dealer_id" placeholder="Nothing selected" quickCreate="#quickCreateContact" />
                <x-form.input name="brand_preferred" label="Brand Preferred" :value="$project->brand_preferred" maxlength="80" />
                <x-form.input name="expected_qty" label="Expected Qty" type="number" min="0" step="1" :value="$project->expected_qty" />
                <x-form.input name="expected_maturity_date" label="Expected Maturity Date" type="date" :value="$project->expected_maturity_date?->format('Y-m-d')" prepend="<i class='bi bi-calendar3'></i>" />
                <x-form.input name="specification_details" label="Details For Specification" :value="$project->specification_details" maxlength="500" col="col-md-12 col-xl-8" />
            </x-form.section>

            <x-form.section title="Next Meeting / Site Visit">
                <x-form.input name="next_meeting_at" label="Date &amp; Time" type="datetime-local" :value="$project->next_meeting_at?->format('Y-m-d\TH:i')" prepend="<i class='bi bi-calendar-event'></i>" />
                <x-form.input name="next_meeting_notes" label="Notes" :value="$project->next_meeting_notes" maxlength="250" placeholder="e.g. Site inspection with client" />
            </x-form.section>

            <x-form.section title="Additional Information">
                <x-form.textarea name="notes" label="Notes / Remarks" :value="$project->notes" rows="4" maxlength="5000" />
            </x-form.section>
        @else
            {{-- Create mode: simplified with "More options" toggle --}}
            <x-form.section title="Project Information" :first="true">
                <x-form.input name="name" label="Project Name" :value="$project->name" :required="true" maxlength="150" autofocus />
                <x-form.select name="client_id" label="Customer" :options="$clients" :value="$project->client_id" placeholder="Select customer" :required="true" quickCreate="#quickCreateCustomer" />
                <x-form.input name="type_of_work" label="Type of Work" :value="$project->type_of_work" list="typeOfWorkOptions" placeholder="Pick or type" maxlength="80" />
                <datalist id="typeOfWorkOptions">
                    @foreach (['Residential – House', 'Residential – Villas', 'Residential – Apartment', 'Commercial – Office', 'Commercial – Retail', 'Industrial – Warehouse', 'Industrial – Factory', 'Institutional', 'Infrastructure – Road / Bridge', 'Renovation'] as $option)
                        <option value="{{ $option }}"></option>
                    @endforeach
                </datalist>
            </x-form.section>

            <div class="mb-3">
                <button type="button" class="btn btn-link btn-sm px-0 text-decoration-none" data-bs-toggle="collapse" data-bs-target="#moreOptions" aria-expanded="false" aria-controls="moreOptions">
                    <i class="bi bi-chevron-down me-1" id="moreOptionsChevron"></i>More options
                </button>
            </div>

            <div class="collapse" id="moreOptions">
                <x-form.section title="Description">
                    <x-form.textarea name="description" label="Description" :value="$project->description" rows="3" maxlength="5000" />
                </x-form.section>

                <x-form.section title="Schedule & Budget">
                    <x-form.input name="start_date" label="Start Date" type="date" :value="$project->start_date?->format('Y-m-d')" />
                    <x-form.input name="expected_end_date" label="Expected End Date" type="date" :value="$project->expected_end_date?->format('Y-m-d')" />
                    <x-form.input name="budget" label="Budget" type="number" step="0.01" min="0" :value="$project->budget" prepend="₹" />
                </x-form.section>

                <x-form.section title="Material Requirement">
                    <x-form.select name="dealer_id" label="Contact" :options="$dealers" :value="$project->dealer_id" placeholder="Nothing selected" :help="$dealers->isEmpty() ? 'Add contacts under Contacts to pick one here.' : null" />
                    <x-form.input name="brand_preferred" label="Brand Preferred" :value="$project->brand_preferred" maxlength="80" />
                    <x-form.input name="expected_qty" label="Expected Qty" type="number" min="0" step="1" :value="$project->expected_qty" />
                    <x-form.input name="expected_maturity_date" label="Expected Maturity Date" type="date" :value="$project->expected_maturity_date?->format('Y-m-d')" prepend="<i class='bi bi-calendar3'></i>" />
                    <x-form.input name="specification_details" label="Details For Specification" :value="$project->specification_details" maxlength="500" col="col-md-12 col-xl-8" />
                </x-form.section>

                <x-form.section title="Next Meeting / Site Visit">
                    <x-form.input name="next_meeting_at" label="Date &amp; Time" type="datetime-local" :value="$project->next_meeting_at?->format('Y-m-d\TH:i')" prepend="<i class='bi bi-calendar-event'></i>" />
                    <x-form.input name="next_meeting_notes" label="Notes" :value="$project->next_meeting_notes" maxlength="250" placeholder="e.g. Site inspection with client" />
                </x-form.section>

                <x-form.section title="Notes">
                    <x-form.textarea name="notes" label="Notes / Remarks" :value="$project->notes" rows="4" maxlength="5000" />
                </x-form.section>
            </div>
        @endif
    </div>
</div>

@if (!$project->exists)
{{-- Quick Create Customer --}}
<div class="modal fade" id="quickCreateCustomer" tabindex="-1" aria-labelledby="quickCreateCustomerLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickCreateCustomerLabel">New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="qcCustomerName" class="form-label">Customer Name <span class="req">*</span></label>
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

{{-- Quick Create Contact --}}
<div class="modal fade" id="quickCreateContact" tabindex="-1" aria-labelledby="quickCreateContactLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickCreateContactLabel">New Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="qcContactName" class="form-label">Contact Name <span class="req">*</span></label>
                    <input type="text" class="form-control" id="qcContactName" maxlength="120" autocomplete="off" />
                    <div class="invalid-feedback" id="qcContactError"></div>
                </div>
                <div class="mb-3">
                    <label for="qcContactPhone" class="form-label">Phone</label>
                    <input type="tel" class="form-control" id="qcContactPhone" maxlength="20" autocomplete="off" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="qcContactSave">Create &amp; Select</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function quickCreate(config) {
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById(config.modalId));
        var nameInput = document.getElementById(config.nameInputId);
        var phoneInput = document.getElementById(config.phoneInputId);
        var errorEl = document.getElementById(config.errorId);
        var saveBtn = document.getElementById(config.saveBtnId);
        var select = document.getElementById(config.selectId);

        document.getElementById(config.modalId).addEventListener('hidden.bs.modal', function () {
            nameInput.value = '';
            if (phoneInput) phoneInput.value = '';
            nameInput.classList.remove('is-invalid');
            errorEl.textContent = '';
        });

        saveBtn.addEventListener('click', async function () {
            nameInput.classList.remove('is-invalid');
            errorEl.textContent = '';
            var name = nameInput.value.trim();
            if (!name) {
                nameInput.classList.add('is-invalid');
                errorEl.textContent = 'Name is required.';
                return;
            }
            saveBtn.disabled = true;
            try {
                var body = { name: name };
                if (phoneInput && phoneInput.value.trim()) body.phone = phoneInput.value.trim();
                var res = await fetch(config.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify(body),
                });
                var data = await res.json();
                if (!res.ok) {
                    var msg = data.errors ? Object.values(data.errors)[0][0] : 'Failed to create.';
                    nameInput.classList.add('is-invalid');
                    errorEl.textContent = msg;
                    return;
                }
                var opt = new Option(data.name, data.id, true, true);
                select.add(opt);
                modal.hide();
            } catch (e) {
                nameInput.classList.add('is-invalid');
                errorEl.textContent = 'Something went wrong. Please try again.';
            } finally {
                saveBtn.disabled = false;
            }
        });
    }

    quickCreate({
        modalId: 'quickCreateCustomer', nameInputId: 'qcCustomerName', phoneInputId: 'qcCustomerPhone',
        errorId: 'qcCustomerError', saveBtnId: 'qcCustomerSave', selectId: 'client_id',
        url: '{{ route("clients.quick") }}',
    });
    quickCreate({
        modalId: 'quickCreateContact', nameInputId: 'qcContactName', phoneInputId: 'qcContactPhone',
        errorId: 'qcContactError', saveBtnId: 'qcContactSave', selectId: 'dealer_id',
        url: '{{ route("dealers.quick") }}',
    });

    var el = document.getElementById('moreOptions');
    var chevron = document.getElementById('moreOptionsChevron');
    if (el) {
        el.addEventListener('show.bs.collapse', function () { chevron.className = 'bi bi-chevron-up me-1'; });
        el.addEventListener('hide.bs.collapse', function () { chevron.className = 'bi bi-chevron-down me-1'; });
    }
})();
</script>
@endif
