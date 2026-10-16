{{-- Shared location form. Expects $location, $districtsByState. State/district suggest Indian values but accept any text for other countries. --}}
<div class="card form-card">
    <div class="card-body">
        <x-form.section title="Location Information" :first="true">
            <x-form.input name="name" label="Location Name" :value="$location->name" :required="true" maxlength="120" placeholder="e.g. Main site, Block C" autofocus />
            <x-form.input name="address" label="Address" :value="$location->address" :required="true" maxlength="250" col="col-md-12 col-xl-8" />
            <x-form.input name="city" label="City" :value="$location->city" :required="true" maxlength="80" />
            <x-form.input name="state" label="State" :value="$location->state" :required="true" maxlength="80" list="stateOptions" data-state-input="districtOptions" autocomplete="off" />
            <x-form.input name="district" label="District" :value="$location->district" maxlength="80" list="districtOptions" autocomplete="off" help="Suggestions follow the selected Indian state." />
            <x-form.input name="country" label="Country" :value="$location->country" :required="true" maxlength="80" />
            <x-form.input name="postal_code" label="Postal Code" :value="$location->postal_code" maxlength="12" inputmode="numeric" />
            <datalist id="stateOptions">
                @foreach (array_keys($districtsByState) as $state)
                    <option value="{{ $state }}"></option>
                @endforeach
            </datalist>
            <datalist id="districtOptions"></datalist>
        </x-form.section>

        <x-form.section title="Map Coordinates">
            <x-form.input name="latitude" label="Latitude" type="number" step="0.0000001" min="-90" max="90" :value="$location->latitude" placeholder="e.g. 10.0159" />
            <x-form.input name="longitude" label="Longitude" type="number" step="0.0000001" min="-180" max="180" :value="$location->longitude" placeholder="e.g. 76.3419" />
        </x-form.section>

        <x-form.section title="Site Contact">
            <x-form.input name="contact_person" label="Contact Person" :value="$location->contact_person" maxlength="100" />
            <x-form.input name="contact_phone" label="Contact Phone" type="tel" :value="$location->contact_phone" maxlength="20" inputmode="tel" />
            <x-form.textarea name="notes" label="Notes" :value="$location->notes" rows="3" maxlength="5000" />
        </x-form.section>
    </div>
</div>

<script id="districtData" type="application/json">@json($districtsByState)</script>
