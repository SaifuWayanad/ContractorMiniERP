{{-- Admin user form. Expects $user, $roles (id, name, type), $contractors. --}}
@php
    $selectedRole = (string) old('role_id', $user->role_id);
@endphp
<div class="card form-card">
    <div class="card-body">
        <x-form.section title="User Information" :first="true">
            <x-form.input name="name" label="Name" :value="$user->name" :required="true" maxlength="100" autofocus />
            <x-form.input name="username" label="Username" :value="$user->username" :required="true" maxlength="50" autocomplete="off" help="Used to sign in. Lowercase letters, numbers, dots, underscores or hyphens." />
            <x-form.input name="email" label="Email" type="email" :value="$user->email" maxlength="150" autocomplete="off" />
            <x-form.input name="phone" label="Phone" type="tel" :value="$user->phone" maxlength="20" />
        </x-form.section>

        <x-form.section title="Access">
            <div class="col-md-6 col-xl-4">
                <label for="role_id" class="form-label">Role <span class="req">*</span></label>
                <select id="role_id" name="role_id" required data-role-select @class(['form-select', 'is-invalid' => $errors->has('role_id')])>
                    <option value="">Select role</option>
                    @foreach ($roles->groupBy(fn ($role) => $role->type->label()) as $group => $groupRoles)
                        <optgroup label="{{ $group }}">
                            @foreach ($groupRoles as $role)
                                <option value="{{ $role->id }}" data-type="{{ $role->type->value }}" @selected($selectedRole === (string) $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            @if ($user->exists)
                <x-form.select name="status" label="Status" :options="App\Enums\UserStatus::options()" :value="$user->status" :required="true" />
            @endif
        </x-form.section>

        @if ($user->exists && $user->contractor_id)
            <hr class="form-divider" />
            <h2 class="form-section-title">Contractor</h2>
            <div class="row g-3 g-lg-4">
                <div class="col-md-6 col-xl-4">
                    <label class="form-label">Linked Contractor</label>
                    <p class="form-control-plaintext fw-semibold">
                        <a href="{{ route('admin.contractors.show', $user->contractor) }}">{{ $user->contractor->company_name }}</a>
                    </p>
                </div>
            </div>
        @endif

        <div id="contractor-section"@if ($user->exists && $user->contractor_id) style="display:none"@endif>
            <hr class="form-divider" />
            <h2 class="form-section-title">Contractor</h2>
            <div class="row g-3 g-lg-4">
                <x-form.input name="company_name" label="Company Name" :value="old('company_name')" maxlength="150" />
            </div>
        </div>

        <x-form.section :title="$user->exists ? 'Reset Password' : 'Password'">
            <x-form.input name="password" label="Password" type="password" :required="! $user->exists" autocomplete="new-password" :value="''" :help="$user->exists ? 'Leave blank to keep the current password.' : null" />
            <x-form.input name="password_confirmation" label="Confirm Password" type="password" :required="! $user->exists" autocomplete="new-password" :value="''" />
        </x-form.section>
    </div>
</div>

@if (! ($user->exists && $user->contractor_id))
<script>
(function () {
    const roleSelect = document.querySelector('[data-role-select]');
    const section = document.getElementById('contractor-section');
    const companyInput = section.querySelector('[name="company_name"]');

    function toggle() {
        const selected = roleSelect.options[roleSelect.selectedIndex];
        const isContractor = selected && selected.dataset.type === 'contractor';
        section.style.display = isContractor ? '' : 'none';
        companyInput.required = isContractor;
    }

    roleSelect.addEventListener('change', toggle);
    toggle();
})();
</script>
@endif

