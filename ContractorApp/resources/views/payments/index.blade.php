@extends('layouts.app')

@section('title', 'Payments Received')

@section('content')
<x-page-header title="Payments received" subtitle="Client payments received across all projects.">
    <x-slot:actions>
        @can('create', App\Models\ProjectPayment::class)
            <a href="{{ route('payments.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Record Payment</a>
        @endcan
    </x-slot:actions>
</x-page-header>

{{-- Filters --}}
<form method="get" action="{{ route('payments.index') }}" class="mb-4" data-auto-filter>
    <div class="row g-2 align-items-end">
        <div class="col-sm-4 col-md-3">
            <input type="text" class="form-control form-control-sm" name="search" value="{{ $filters['search'] }}" placeholder="Search reference, method…" />
        </div>
        <div class="col-sm-4 col-md-3">
            <div class="cs-wrapper cs-sm" id="pmtClientWrapper">
                <div class="cs-trigger">
                    <span class="cs-value {{ $filters['client_id'] ? '' : 'cs-placeholder' }}">{{ $filters['client_id'] ? ($clients[$filters['client_id']] ?? 'All customers') : 'All customers' }}</span>
                    <i class="bi bi-chevron-down cs-arrow"></i>
                </div>
                <div class="cs-panel">
                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                    <div class="cs-list">
                        <div class="cs-item" data-value="">All customers</div>
                        @foreach($clients as $id => $name)
                            <div class="cs-item" data-value="{{ $id }}">{{ $name }}</div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="client_id" id="pmtClientInput" value="{{ $filters['client_id'] ?? '' }}">
            </div>
        </div>
        <div class="col-sm-4 col-md-3">
            <div class="cs-wrapper cs-sm" id="pmtProjectWrapper">
                <div class="cs-trigger">
                    <span class="cs-value {{ $filters['project_id'] ? '' : 'cs-placeholder' }}">{{ $filters['project_id'] ? ($projects[$filters['project_id']] ?? 'All projects') : 'All projects' }}</span>
                    <i class="bi bi-chevron-down cs-arrow"></i>
                </div>
                <div class="cs-panel">
                    <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                    <div class="cs-list">
                        <div class="cs-item" data-value="">All projects</div>
                        @foreach($projects as $id => $name)
                            <div class="cs-item" data-value="{{ $id }}">{{ $name }}</div>
                        @endforeach
                    </div>
                </div>
                <input type="hidden" name="project_id" id="pmtProjectInput" value="{{ $filters['project_id'] ?? '' }}">
            </div>
        </div>
        @if($filters['search'] || $filters['client_id'] || $filters['project_id'])
        <div class="col-auto">
            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
        @endif
    </div>
</form>

@if ($payments->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-cash-coin" title="No payments yet" message="Record payments received from clients against projects.">
            @can('create', App\Models\ProjectPayment::class)
                <a href="{{ route('payments.create') }}" class="btn btn-primary btn-sm">Record Payment</a>
            @endcan
        </x-empty-state>
    </div>
@else
    <x-table-card :paginator="$payments">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Project</th>
                    <th class="d-none d-sm-table-cell">Date</th>
                    <th class="d-none d-md-table-cell">Method</th>
                    <th class="d-none d-lg-table-cell">Reference</th>
                    <th class="text-end fw-semibold">Amount</th>
                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments as $payment)
                    <tr>
                        <td>
                            <a href="{{ route('projects.show', $payment->project_id) }}" class="row-title">{{ $payment->project?->name ?? '—' }}</a>
                        </td>
                        <td class="d-none d-sm-table-cell text-nowrap">{{ $payment->payment_date?->format('d M Y') }}</td>
                        <td class="d-none d-md-table-cell">{{ $payment->payment_method ?? '—' }}</td>
                        <td class="d-none d-lg-table-cell">{{ $payment->receipt_reference ?? '—' }}</td>
                        <td class="text-end fw-semibold text-success">₹{{ number_format((float)$payment->amount_received, 2) }}</td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-icon btn-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions"><i class="bi bi-three-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @can('update', $payment)
                                        <li><a class="dropdown-item" href="{{ route('payments.edit', $payment) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    @endcan
                                    @can('delete', $payment)
                                        <li><hr class="dropdown-divider" /></li>
                                        <li><x-delete-button :action="route('payments.destroy', $payment)" class="dropdown-item text-danger" title="Delete payment?" confirm="Delete this payment record? This cannot be undone." /></li>
                                    @endcan
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-table-card>
@endif
@push('scripts')
<script>
(function () {
    var form = document.querySelector('form[data-auto-filter]');
    function bindCs(wrapperId, inputId) {
        var cs = new CustomSelect(wrapperId);
        var input = document.getElementById(inputId);
        var orig = cs._select.bind(cs);
        cs._select = function(value, label) { orig(value, label); input.value = value; form.submit(); };
    }
    bindCs('pmtClientWrapper', 'pmtClientInput');
    bindCs('pmtProjectWrapper', 'pmtProjectInput');
})();
</script>
@endpush
@endsection
