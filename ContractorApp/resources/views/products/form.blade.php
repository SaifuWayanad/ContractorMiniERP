{{-- Shared product form. Expects $product. --}}
<div class="card form-card">
    <div class="card-body">
        <x-form.section title="Product Details" :first="true">
            <x-form.input name="name" label="Product Name" :value="$product->name" :required="true" maxlength="150" autofocus col="col-md-8" />
            <x-form.input name="unit" label="Unit" :value="$product->unit" maxlength="50" col="col-md-4" help="e.g. kg, m², bag, piece" />
            <x-form.input name="default_rate" label="Default Rate (₹)" type="number" step="0.01" min="0" :value="$product->default_rate" col="col-md-4" />
            <x-form.textarea name="description" label="Description" :value="$product->description" rows="3" maxlength="5000" col="col-md-12 col-xl-8" />
        </x-form.section>
    </div>
</div>
