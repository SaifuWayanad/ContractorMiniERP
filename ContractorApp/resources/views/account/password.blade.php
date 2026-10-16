@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
<x-page-header title="Change password" subtitle="Use at least 8 characters, including letters and numbers." />

<form method="post" action="{{ route('account.password.update') }}" style="max-width: 560px">
    @csrf
    @method('PUT')
    <div class="card form-card">
        <div class="card-body">
            <div class="row g-3">
                <x-form.input name="current_password" label="Current Password" type="password" :required="true" col="col-12" autocomplete="current-password" :value="''" />
                <x-form.input name="password" label="New Password" type="password" :required="true" col="col-12" autocomplete="new-password" :value="''" />
                <x-form.input name="password_confirmation" label="Confirm New Password" type="password" :required="true" col="col-12" autocomplete="new-password" :value="''" />
            </div>
        </div>
    </div>
    <x-form.actions :cancel="auth()->user()->homeRoute()" submit="Change Password" />
</form>
@endsection
