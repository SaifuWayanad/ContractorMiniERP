{{-- Shared purchase form. Expects $purchase, $projects, $products. --}}
@if(isset($from))
    <input type="hidden" name="from" value="{{ $from }}" />
@endif
<div class="card form-card">
    <div class="card-body">
        <x-form.section title="Purchase Details" :first="true">
            <x-form.select name="project_id" label="Project" :options="$projects" :value="$purchase->project_id" placeholder="Select project" :required="true" />
            <x-form.select name="product_id" label="Product" :options="$products" :value="$purchase->product_id" placeholder="Select product" />
            <x-form.input name="purchase_date" label="Date" type="date" :value="$purchase->purchase_date?->format('Y-m-d') ?? today()->format('Y-m-d')" :required="true" col="col-md-4" />
            <x-form.input name="quantity" label="Quantity" type="number" step="0.0001" min="0.0001" :value="$purchase->quantity ?? 1" :required="true" col="col-md-4" />
            <x-form.input name="unit_amount" label="Unit Rate (₹)" type="number" step="0.01" min="0" :value="$purchase->unit_amount ?? 0" :required="true" col="col-md-4" />
            <x-form.input name="supplier" label="Supplier" :value="$purchase->supplier" maxlength="200" col="col-md-6" />
            <x-form.input name="invoice_reference" label="Invoice / Reference" :value="$purchase->invoice_reference" maxlength="100" col="col-md-6" />
            <x-form.textarea name="notes" label="Notes" :value="$purchase->notes" rows="2" maxlength="5000" />
        </x-form.section>
    </div>
</div>
