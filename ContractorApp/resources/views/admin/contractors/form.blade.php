{{-- Admin contractor form. Expects $contractor. On create it can also create the first owner login. --}}
<div class="card form-card">
    <div class="card-body">
        @if ($contractor->exists)
            {{-- Edit mode: full form --}}
            <x-form.section title="Company Information" :first="true">
                <x-form.input name="company_name" label="Company Name" :value="$contractor->company_name" :required="true" maxlength="150" autofocus />
                <x-form.input name="website" label="Website" type="url" :value="$contractor->website" maxlength="200" placeholder="https://" />
                <x-form.select name="status" label="Status" :options="App\Enums\ContractorStatus::options()" :value="$contractor->status" :required="true" help="Inactive contractors' users cannot sign in. Data is kept." />
            </x-form.section>

            <x-form.section title="Contact Information">
                <x-form.input name="contact_person" label="Contact Person" :value="$contractor->contact_person" maxlength="100" />
                <x-form.input name="email" label="Email" type="email" :value="$contractor->email" maxlength="150" />
                <x-form.input name="phone" label="Phone" type="tel" :value="$contractor->phone" maxlength="20" />
                <x-form.input name="alternate_phone" label="Alternate Phone" type="tel" :value="$contractor->alternate_phone" maxlength="20" />
            </x-form.section>

            <x-form.section title="Address Information">
                <x-form.input name="address" label="Address" :value="$contractor->address" maxlength="250" col="col-12 col-xl-8" />
                <x-form.input name="city" label="City" :value="$contractor->city" maxlength="80" />
                <x-form.input name="state" label="State" :value="$contractor->state" maxlength="80" />
                <x-form.input name="country" label="Country" :value="$contractor->country" maxlength="80" />
                <x-form.input name="postal_code" label="Postal Code" :value="$contractor->postal_code" maxlength="12" />
            </x-form.section>

            <x-form.section title="Registration Information">
                <x-form.input name="tax_number" label="Tax / VAT / GST Number" :value="$contractor->tax_number" maxlength="30" />
                <x-form.input name="license_number" label="License Number" :value="$contractor->license_number" maxlength="50" />
            </x-form.section>
        @else
            {{-- Create mode: simplified form with More Options --}}
            <x-form.section title="Company Information" :first="true">
                <x-form.input name="company_name" label="Company Name" :value="old('company_name')" :required="true" maxlength="150" autofocus />
                <x-form.input name="contact_person" label="Contact Person" :value="old('contact_person')" maxlength="100" />
                <x-form.input name="email" label="Email" type="email" :value="old('email')" maxlength="150" />
                <x-form.input name="phone" label="Phone" type="tel" :value="old('phone')" maxlength="20" />
            </x-form.section>

            <div class="mb-3">
                <button type="button" class="btn btn-link px-0 text-decoration-none"
                    data-bs-toggle="collapse" data-bs-target="#moreOptions" aria-expanded="false" aria-controls="moreOptions"
                    id="moreOptionsBtn">
                    <i class="bi bi-chevron-right me-1" id="moreOptionsChevron"></i> More options
                </button>
            </div>
            <div class="collapse" id="moreOptions">
                <x-form.section title="Additional Details">
                    <x-form.input name="alternate_phone" label="Alternate Phone" type="tel" :value="old('alternate_phone')" maxlength="20" />
                    <x-form.input name="website" label="Website" type="url" :value="old('website')" maxlength="200" placeholder="https://" />
                    <x-form.select name="status" label="Status" :options="App\Enums\ContractorStatus::options()" :value="old('status', App\Enums\ContractorStatus::Active->value)" help="Defaults to Active if left unchanged." />
                </x-form.section>

                <x-form.section title="Address">
                    <x-form.input name="address" label="Address" :value="old('address')" maxlength="250" col="col-12 col-xl-8" />
                    <x-form.input name="city" label="City" :value="old('city')" maxlength="80" />
                    <x-form.input name="state" label="State" :value="old('state')" maxlength="80" />
                    <x-form.input name="country" label="Country" :value="old('country', 'India')" maxlength="80" />
                    <x-form.input name="postal_code" label="Postal Code" :value="old('postal_code')" maxlength="12" />
                </x-form.section>

                <x-form.section title="Registration">
                    <x-form.input name="tax_number" label="Tax / VAT / GST Number" :value="old('tax_number')" maxlength="30" />
                    <x-form.input name="license_number" label="License Number" :value="old('license_number')" maxlength="50" />
                </x-form.section>
            </div>

            <hr class="form-divider" />
            <h2 class="form-section-title">Initial Login Credentials</h2>
            <div class="form-check mb-3">
                <input type="hidden" name="create_user" value="0" />
                <input class="form-check-input" type="checkbox" name="create_user" id="create_user" value="1" @checked(old('create_user', true)) data-toggle-target="#initialUser" />
                <label class="form-check-label" for="create_user">Create the contractor's first user (owner) now</label>
            </div>
            <div class="row g-3 g-lg-4" id="initialUser">
                <x-form.input name="user_name" label="User Name" maxlength="100" />
                <x-form.input name="user_password" label="Password" type="password" autocomplete="new-password" />
                <x-form.input name="user_password_confirmation" label="Confirm Password" type="password" autocomplete="new-password" />
            </div>
        @endif
    </div>
</div>

@unless ($contractor->exists)
<script>
    document.getElementById('moreOptions').addEventListener('show.bs.collapse', function () {
        document.getElementById('moreOptionsChevron').classList.replace('bi-chevron-right', 'bi-chevron-down');
    });
    document.getElementById('moreOptions').addEventListener('hide.bs.collapse', function () {
        document.getElementById('moreOptionsChevron').classList.replace('bi-chevron-down', 'bi-chevron-right');
    });
</script>
@endunless
