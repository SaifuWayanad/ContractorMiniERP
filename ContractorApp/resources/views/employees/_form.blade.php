{{-- Shared employee form partial. Expects: $employee, $salaryTypes, $statusOptions --}}
<div class="card form-card">
    <div class="card-body">
        <x-form.section title="Personal Details" :first="true">
            <x-form.input name="name" label="Full Name" :value="$employee->name" :required="true" maxlength="200" autofocus col="col-md-8" />
            <x-form.input name="employee_code" label="Employee Code" :value="$employee->employee_code" placeholder="e.g. EMP001" col="col-md-4" />
            <x-form.input name="phone" label="Phone" type="tel" :value="$employee->phone" col="col-md-4" />
            <x-form.input name="email" label="Email" type="email" :value="$employee->email" col="col-md-4" />
            <x-form.input name="designation" label="Designation / Role" :value="$employee->designation" col="col-md-8" />
            <x-form.input name="joining_date" label="Joining Date" type="date" :value="$employee->joining_date?->format('Y-m-d')" col="col-md-4" />
        </x-form.section>

        <x-form.section title="Salary">
            <x-form.select name="salary_type" label="Salary Type" :options="$salaryTypes" :value="$employee->salary_type?->value ?? 'daily'" :required="true" col="col-md-4" />
            <x-form.input name="salary_amount" label="Salary Amount (₹)" type="number" step="0.01" min="0" :value="$employee->salary_amount" :required="true" col="col-md-4" />
            <x-form.select name="status" label="Status" :options="$statusOptions" :value="$employee->status?->value ?? 'active'" :required="true" col="col-md-4" />
        </x-form.section>

        <x-form.section title="Notes">
            <div class="col-12">
                <label class="form-label fw-semibold">Notes <span class="text-muted fw-normal small">(optional)</span></label>
                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" maxlength="5000">{{ old('notes', $employee->notes) }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </x-form.section>
    </div>
</div>
