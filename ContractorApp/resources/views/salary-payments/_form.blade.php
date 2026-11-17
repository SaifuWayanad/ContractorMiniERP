{{-- Shared salary payment form partial. Expects: $payment, $employees, $statuses, $paymentMethods, $months --}}
@php $pmWrapperId = 'spPmWrapper'; @endphp

<div class="card form-card">
    <div class="card-body">
        <x-form.section title="Payment Details" :first="true">
            <x-form.select name="employee_id" label="Employee" :options="$employees" :value="$payment->employee_id" placeholder="Select employee" :required="true" col="col-md-6" />

            <div class="col-md-3">
                <label class="form-label fw-semibold">Month <span class="text-danger">*</span></label>
                <select name="period_month" class="form-select form-select @error('period_month') is-invalid @enderror" required>
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}" {{ old('period_month', $payment->period_month) == $num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
                @error('period_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Year <span class="text-danger">*</span></label>
                <input type="number" name="period_year" class="form-control @error('period_year') is-invalid @enderror" min="2000" max="2100" value="{{ old('period_year', $payment->period_year ?? now()->year) }}" required />
                @error('period_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <x-form.input name="amount" label="Amount (₹)" type="number" step="0.01" min="0" :value="$payment->amount" :required="true" col="col-md-4" />

            <x-form.input name="payment_date" label="Payment Date" type="date" :value="$payment->payment_date?->format('Y-m-d') ?? today()->format('Y-m-d')" :required="true" col="col-md-4" />

            {{-- Payment Method CS-dropdown --}}
            <div class="col-md-4">
                <label class="form-label fw-semibold">Payment Method</label>
                <div class="cs-wrapper" id="{{ $pmWrapperId }}">
                    <div class="cs-trigger @error('payment_method') is-invalid @enderror">
                        <span class="cs-value {{ old('payment_method', $payment->payment_method) ? '' : 'cs-placeholder' }}">{{ old('payment_method', $payment->payment_method) ?? 'Select method...' }}</span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-list">
                            @foreach($paymentMethods as $pm)
                                <div class="cs-item" data-value="{{ $pm }}">{{ $pm }}</div>
                            @endforeach
                        </div>
                    </div>
                    <input type="hidden" name="payment_method" id="spPmInput" value="{{ old('payment_method', $payment->payment_method ?? '') }}">
                </div>
                @error('payment_method')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <x-form.select name="status" label="Status" :options="$statuses" :value="$payment->status?->value ?? 'paid'" :required="true" col="col-md-4" />

            <div class="col-12">
                <label class="form-label fw-semibold">Notes <span class="text-muted fw-normal small">(optional)</span></label>
                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" maxlength="5000">{{ old('notes', $payment->notes) }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </x-form.section>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var cs = new CustomSelect('{{ $pmWrapperId }}');
    var input = document.getElementById('spPmInput');
    var orig = cs._select.bind(cs);
    cs._select = function(v, l) { orig(v, l); input.value = v; };
    if (input.value) cs._select(input.value, input.value);
})();
</script>
@endpush
