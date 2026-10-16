@extends('layouts.app')

@section('title', 'Add Client')

@section('content')
<x-page-header title="Add Client" subtitle="Fields marked * are required.">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('admin.clients.index') }}">Clients</a></li>
        <li class="breadcrumb-item active" aria-current="page">New</li>
    </x-slot:breadcrumbs>
</x-page-header>

<form method="post" action="{{ route('admin.clients.store') }}">
    @csrf

    <x-card class="mb-3">
        <x-form.section title="Account" :first="true">
            <x-form.select name="contractor_id" label="Contractor" :options="$contractors" placeholder="Select contractor" :value="old('contractor_id')" :required="true" />
        </x-form.section>
    </x-card>

    <x-card class="mb-3">
        <x-form.section title="Client Details" :first="true">
            <x-form.input name="name" label="Client Name" :required="true" autocomplete="off" />
            <x-form.input name="phone" type="tel" label="Phone" />
        </x-form.section>

        <div class="px-3 pb-2">
            <button type="button" class="btn btn-link btn-sm px-0 text-decoration-none" data-bs-toggle="collapse" data-bs-target="#moreOptions" aria-expanded="false" aria-controls="moreOptions">
                <i class="bi bi-chevron-down me-1" id="moreOptionsChevron"></i>More options
            </button>
        </div>

        <div class="collapse" id="moreOptions">
            <x-form.section title="More Details">
                <x-form.input name="company" label="Company" />
                <x-form.input name="contact_person" label="Contact Person" />
                <x-form.input name="email" type="email" label="Email" />
                <x-form.select name="status" label="Status" :options="App\Enums\ClientStatus::options()" placeholder="Select status" />
                <x-form.textarea name="notes" label="Notes" rows="3" col="col-12" />
            </x-form.section>
        </div>
    </x-card>

    <x-form.actions :cancel="route('admin.clients.index')" submit="Add Client" />
</form>

<script>
    (function () {
        var el = document.getElementById('moreOptions');
        var chevron = document.getElementById('moreOptionsChevron');
        if (!el) return;
        el.addEventListener('show.bs.collapse', function () { chevron.className = 'bi bi-chevron-up me-1'; });
        el.addEventListener('hide.bs.collapse', function () { chevron.className = 'bi bi-chevron-down me-1'; });
    })();
</script>
@endsection
