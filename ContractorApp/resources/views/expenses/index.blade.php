@extends('layouts.app')

@section('title', 'Expenses')

@section('content')
<x-page-header title="Expenses" subtitle="Project expenses across all your work.">
    <x-slot:actions>
        @can('create', App\Models\Expense::class)
            <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Expense</a>
        @endcan
    </x-slot:actions>
</x-page-header>

{{-- Filters --}}
<form method="get" action="{{ route('expenses.index') }}" class="mb-4" data-auto-filter>
    <div class="row g-2 align-items-end">
        <div class="col-sm-4 col-md-3">
            <input type="text" class="form-control form-control-sm" name="search" value="{{ $filters['search'] }}" placeholder="Search heading, category…" />
        </div>
        <div class="col-sm-4 col-md-3">
            <div class="cs-wrapper cs-sm" id="expClientWrapper">
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
                <input type="hidden" name="client_id" id="expClientInput" value="{{ $filters['client_id'] ?? '' }}">
            </div>
        </div>
        <div class="col-sm-4 col-md-3">
            <div class="cs-wrapper cs-sm" id="expProjectWrapper">
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
                <input type="hidden" name="project_id" id="expProjectInput" value="{{ $filters['project_id'] ?? '' }}">
            </div>
        </div>
        @if($filters['search'] || $filters['client_id'] || $filters['project_id'] || $filters['category'])
        <div class="col-auto">
            <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
        @endif
    </div>
</form>

@if ($expenses->isEmpty())
    <div class="card">
        <x-empty-state icon="bi-receipt" title="No expenses yet" message="Record expenses to track project costs.">
            @can('create', App\Models\Expense::class)
                <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm">Add Expense</a>
            @endcan
        </x-empty-state>
    </div>
@else
    <x-table-card :paginator="$expenses">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Heading</th>
                    <th class="d-none d-md-table-cell">Project</th>
                    <th class="d-none d-lg-table-cell">Category</th>
                    <th class="d-none d-sm-table-cell">Date</th>
                    <th class="text-end fw-semibold">Amount</th>
                    <th class="text-end"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($expenses as $expense)
                    <tr>
                        <td>
                            <span class="row-title">{{ $expense->heading }}</span>
                            @if ($expense->payment_method)
                                <div class="cell-sub">{{ $expense->payment_method }}</div>
                            @endif
                        </td>
                        <td class="d-none d-md-table-cell">{{ $expense->project?->name ?? '—' }}</td>
                        <td class="d-none d-lg-table-cell">{{ $expense->category ?? '—' }}</td>
                        <td class="d-none d-sm-table-cell text-nowrap">{{ $expense->expense_date?->format('d M Y') }}</td>
                        <td class="text-end fw-semibold">₹{{ number_format((float)$expense->amount, 2) }}</td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-icon btn-sm" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions"><i class="bi bi-three-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @can('update', $expense)
                                        <li><a class="dropdown-item" href="{{ route('expenses.edit', $expense) }}"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    @endcan
                                    @can('delete', $expense)
                                        <li><hr class="dropdown-divider" /></li>
                                        <li><x-delete-button :action="route('expenses.destroy', $expense)" class="dropdown-item text-danger" title="Delete expense?" confirm="Delete this expense record? This cannot be undone." /></li>
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
    bindCs('expClientWrapper', 'expClientInput');
    bindCs('expProjectWrapper', 'expProjectInput');
})();
</script>
@endpush
@endsection
