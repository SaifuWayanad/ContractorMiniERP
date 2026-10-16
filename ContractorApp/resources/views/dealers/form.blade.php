<div class="card form-card" style="max-width: 900px">
    <div class="card-body">
        <x-form.section title="Dealer Information" :first="true">
            <x-form.input name="name" label="Contact Name" :value="$dealer->name" :required="true" maxlength="120" col="col-md-6" autofocus />
            <x-form.input name="city" label="City" :value="$dealer->city" maxlength="80" col="col-md-6" />
            <x-form.input name="phone" label="Phone" type="tel" :value="$dealer->phone" maxlength="20" col="col-md-6" inputmode="tel" />
        </x-form.section>
    </div>
</div>
