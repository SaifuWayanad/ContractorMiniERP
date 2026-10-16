@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<x-page-header title="Settings" subtitle="Application-wide preferences." />

<form method="post" action="{{ route('admin.settings.update') }}" style="max-width: 900px">
    @csrf
    @method('PUT')
    <fieldset @disabled(! auth()->user()->can('settings.edit'))>
        <div class="card form-card">
            <div class="card-body">
                <x-form.section title="General" :first="true">
                    <x-form.input name="platform_name" label="Platform Name" :value="$settings['platform_name']" :required="true" maxlength="60" col="col-md-6" />
                    <x-form.input name="support_email" label="Support Email" type="email" :value="$settings['support_email']" maxlength="150" col="col-md-6" />
                    <x-form.input name="default_country" label="Default Country" :value="$settings['default_country']" :required="true" maxlength="80" col="col-md-6" />
                    <x-form.select name="records_per_page" label="Records Per Page" :options="['10' => '10', '25' => '25', '50' => '50', '100' => '100']" :value="$settings['records_per_page']" :required="true" col="col-md-6" help="Page size for all lists." />
                </x-form.section>
            </div>
        </div>
        @can('settings.edit')
            <x-form.actions :cancel="route('admin.dashboard')" submit="Save Settings" />
        @endcan
    </fieldset>
</form>
@endsection
