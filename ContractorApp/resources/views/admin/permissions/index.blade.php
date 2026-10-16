@extends('layouts.app')

@section('title', 'Permissions')

@section('content')
<x-page-header title="Permissions" subtitle="Which roles hold each permission. Edit a role to change its permissions." />

<div class="card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0" style="min-width: 600px;">
            <thead class="table-dark">
                <tr>
                    <th style="width:200px">Section / Action</th>
                    @foreach ($roles as $role)
                        <th class="text-center" style="min-width:110px">
                            @can('update', $role)
                                <a href="{{ route('admin.roles.edit', $role) }}" class="text-white text-decoration-none">{{ $role->name }}</a>
                            @else
                                {{ $role->name }}
                            @endcan
                            <div class="fw-normal small opacity-75">{{ ucfirst($role->type->value) }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($groups as $key => $group)
                    <tr class="table-secondary">
                        <td colspan="{{ $roles->count() + 1 }}">
                            <span class="fw-semibold text-dark">{{ $group['label'] }}</span>
                        </td>
                    </tr>
                    @foreach ($group['abilities'] as $ability)
                        @php($name = "{$key}.{$ability}")
                        <tr>
                            <td class="ps-4 text-muted small">{{ ucfirst($ability) }}</td>
                            @foreach ($roles as $role)
                                <td class="text-center">
                                    @if (! in_array($role->type->value, $group['portals'], true))
                                        <span class="text-muted" title="Not applicable for {{ $role->type->label() }} roles">—</span>
                                    @elseif (in_array($name, $grants[$role->id], true))
                                        <span class="badge rounded-pill bg-success px-2"><i class="bi bi-check-lg"></i></span>
                                    @else
                                        <span class="text-muted"><i class="bi bi-x-lg" style="font-size:.75rem"></i></span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
