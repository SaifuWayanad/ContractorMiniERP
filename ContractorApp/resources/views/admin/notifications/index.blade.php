@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<x-page-header title="Notifications" subtitle="Send a push notification to the mobile app. {{ $deviceCount }} device(s) registered." />

<form method="post" action="{{ route('admin.notifications.send') }}" style="max-width: 900px">
    @csrf
    <fieldset @disabled(! auth()->user()->can('notifications.send'))>
        <div class="card form-card">
            <div class="card-body">
                <x-form.section title="Message" :first="true">
                    <x-form.select name="contractor_id" label="Send To" :options="$contractors->all()" :value="old('contractor_id')" placeholder="All registered devices" col="col-md-6" help="Limit to one contractor's users, or leave blank for everyone." />
                    <x-form.input name="title" label="Title" :value="old('title')" :required="true" maxlength="100" col="col-md-6" />
                    <x-form.textarea name="body" label="Message" :value="old('body')" :required="true" rows="4" maxlength="500" />
                </x-form.section>
            </div>
        </div>
        @can('notifications.send')
            <x-form.actions :cancel="route('admin.dashboard')" submit="Send Notification" />
        @endcan
    </fieldset>
</form>

<div class="card mt-4" style="max-width: 900px">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Registered devices ({{ $devices->count() }})</h6>
            @can('notifications.send')
                @if ($devices->isNotEmpty())
                    <form method="post" action="{{ route('admin.notifications.devices.clear') }}" onsubmit="return confirm('Clear all registered devices? Users re-register the next time they log in to the app.')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Clear all</button>
                    </form>
                @endif
            @endcan
        </div>
        @if ($devices->isEmpty())
            <p class="text-muted mb-0">No devices registered yet. Log in on a phone with the installed app and allow notifications.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>User</th><th>Contractor</th><th>Type</th><th>Token</th><th>Last registered</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($devices as $device)
                            <tr>
                                <td>{{ $device->name }}<div class="small text-muted">{{ $device->email }}</div></td>
                                <td>{{ $device->contractor?->company_name ?? '—' }}</td>
                                <td><span class="badge badge-soft-secondary">{{ \App\Services\PushService::tokenType($device->push_token) }}</span></td>
                                <td class="small text-muted">{{ \Illuminate\Support\Str::limit($device->push_token, 18) }}</td>
                                <td class="small">{{ $device->updated_at?->diffForHumans() }}</td>
                                <td class="text-end">
                                    @can('notifications.send')
                                        <form method="post" action="{{ route('admin.notifications.devices.remove', $device) }}" onsubmit="return confirm('Remove this device?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
