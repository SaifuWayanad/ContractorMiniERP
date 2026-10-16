@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<x-page-header title="Edit company profile" subtitle="Fields marked * are required. Your account status can only be changed by an administrator.">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('profile.show') }}">My Profile</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="card form-card">
        <div class="card-body">
            <x-form.section title="Company Information" :first="true">
                <x-form.input name="company_name" label="Company / Contractor Name" :value="$contractor->company_name" :required="true" maxlength="150" />
                <x-form.input name="website" label="Website" type="url" :value="$contractor->website" maxlength="200" placeholder="https://" />
                <div class="col-md-6 col-xl-4">
                    <label for="logo" class="form-label">Company Logo</label>
                    <div class="d-flex align-items-center gap-3">
                        <span class="entity-mark">
                            @if ($contractor->logoUrl())
                                <img src="{{ $contractor->logoUrl() }}" alt="Current logo" />
                            @else
                                {{ $contractor->initials() }}
                            @endif
                        </span>
                        <div class="flex-grow-1 min-w-0">
                            <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" @class(['form-control', 'is-invalid' => $errors->has('logo')]) />
                            @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">PNG, JPG or WebP, up to 1 MB.</div>
                            @if ($contractor->logo_path)
                                <div class="form-check mt-1">
                                    <input type="hidden" name="remove_logo" value="0" />
                                    <input class="form-check-input" type="checkbox" name="remove_logo" id="remove_logo" value="1" />
                                    <label class="form-check-label small" for="remove_logo">Remove current logo</label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </x-form.section>

            <x-form.section title="Contact Information">
                <x-form.input name="contact_person" label="Contact Person" :value="$contractor->contact_person" :required="true" maxlength="100" />
                <x-form.input name="email" label="Email" type="email" :value="$contractor->email" :required="true" maxlength="150" />
                <x-form.input name="phone" label="Phone" type="tel" :value="$contractor->phone" :required="true" maxlength="20" />
                <x-form.input name="alternate_phone" label="Alternate Phone" type="tel" :value="$contractor->alternate_phone" maxlength="20" />
            </x-form.section>

            <x-form.section title="Address Information">
                <x-form.input name="address" label="Address" :value="$contractor->address" maxlength="250" col="col-12 col-xl-8" />
                <x-form.input name="city" label="City" :value="$contractor->city" maxlength="80" />
                <x-form.input name="state" label="State" :value="$contractor->state" maxlength="80" />
                <x-form.input name="country" label="Country" :value="$contractor->country" :required="true" maxlength="80" />
                <x-form.input name="postal_code" label="Postal Code" :value="$contractor->postal_code" maxlength="12" />
            </x-form.section>

            <x-form.section title="Registration Information">
                <x-form.input name="tax_number" label="Tax / VAT / GST Number" :value="$contractor->tax_number" maxlength="30" />
                <x-form.input name="license_number" label="License Number" :value="$contractor->license_number" maxlength="50" />
            </x-form.section>
        </div>
    </div>
    <x-form.actions :cancel="route('profile.show')" submit="Save Profile" />
</form>
@endsection
