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
                <x-form.input name="address" label="Address" :value="$client->address" maxlength="250" col="col-12 col-xl-8" />
                <x-form.input name="phone" label="Phone" type="tel" :value="$client->phone" maxlength="20" inputmode="tel" />
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
<script>
    (function () {
        var el = document.getElementById('moreOptions');
        var chevron = document.getElementById('moreOptionsChevron');
        if (!el) return;
        el.addEventListener('show.bs.collapse', function () { chevron.className = 'bi bi-chevron-up me-1'; });
        el.addEventListener('hide.bs.collapse', function () { chevron.className = 'bi bi-chevron-down me-1'; });
    })();
</script>
@endif
