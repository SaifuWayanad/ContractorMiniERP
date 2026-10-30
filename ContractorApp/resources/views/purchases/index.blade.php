@extends('layouts.app')

@section('title', 'Purchases')

@section('content')
<x-page-header title="Purchases" subtitle="Material and supply purchases across all projects.">
    <x-slot:actions>
        @can('create', App\Models\Purchase::class)
            <a href="{{ route('purchases.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Record Purchase</a>
        @endcan
    </x-slot:actions>
</x-page-header>

{{-- Filters --}}
<form method="get" action="{{ route('purchases.index') }}" class="mb-4" data-auto-filter>
    <div class="row g-2 align-items-end">
        <div class="col-sm-4 col-md-3">
            <input type="text" class="form-control form-control-sm" name="search" value="{{ $filters['search'] }}" placeholder="Search product, supplier…" />
        </div>
        <div class="col-sm-4 col-md-3">
            <div class="cs-wrapper cs-sm" id="purchClientWrapper">
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
                <input type="hidden" name="client_id" id="purchClientInput" value="{{ $filters['client_id'] ?? '' }}">
            </div>
        </div>
        <div class="col-sm-4 col-md-3">
            <div class="cs-wrapper cs-sm" id="purchProjectWrapper">
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
                <input type="hidden" name="project_id" id="purchProjectInput" value="{{ $filters['project_id'] ?? '' }}">
            </div>
        </div>
        @if($filters['search'] || $filters['client_id'] || $filters['project_id'])
        <div class="col-auto">
            <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
        @endif
    </div>
</form>

@if ($purchases->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-cart3" title="No purchases yet" message="Record purchases to track material costs per project.">
            @can('create', App\Models\Purchase::class)
                <a href="{{ route('purchases.create') }}" class="btn btn-primary btn-sm">Record Purchase</a>
            @endcan
        </x-empty-state>
    </div>
@else
    <x-table-card :paginator="$purchases">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Product / Supplier</th>
                    <th class="d-none d-md-table-cell">Project</th>
                    <th class="d-none d-sm-table-cell">Date</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Unit Rate</th>
                    <th class="text-end fw-semibold">Total</th>
                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchases as $purchase)
                    <tr>
                        <td>
                            <span class="row-title">{{ $purchase->product?->name ?? 'Unknown product' }}</span>
                            @if ($purchase->supplier)
                                <div class="cell-sub">{{ $purchase->supplier }}</div>
                            @endif
                        </td>
                        <td class="d-none d-md-table-cell">{{ $purchase->project?->name ?? '—' }}</td>
                        <td class="d-none d-sm-table-cell text-nowrap">{{ $purchase->purchase_date?->format('d M Y') }}</td>
                        <td class="text-end">{{ number_format((float)$purchase->quantity, 2) }}</td>
                        <td class="text-end">₹{{ number_format((float)$purchase->unit_amount, 2) }}</td>
                        <td class="text-end fw-semibold">₹{{ number_format((float)$purchase->line_total, 2) }}</td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-icon btn-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions"><i class="bi bi-three-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @can('update', $purchase)
                                        <li><a class="dropdown-item" href="{{ route('purchases.edit', $purchase) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    @endcan
                                    @can('delete', $purchase)
                                        <li><hr class="dropdown-divider" /></li>
                                        <li><x-delete-button :action="route('purchases.destroy', $purchase)" class="dropdown-item text-danger" title="Delete purchase?" confirm="Delete this purchase record? This cannot be undone." /></li>
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
    bindCs('purchClientWrapper', 'purchClientInput');
    bindCs('purchProjectWrapper', 'purchProjectInput');
})();
</script>
@endpush
@endsection
