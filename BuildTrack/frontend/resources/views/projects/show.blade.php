@extends('layouts.app')

@section('title', 'Project: ' . ($project['projectName'] ?? ''))

@section('content')
@php
    $purchaseItems = $purchases['data'] ?? $purchases['items'] ?? (is_array($purchases) ? array_values($purchases) : []);
    $expenseItems  = $expenses['data']  ?? $expenses['items']  ?? (is_array($expenses)  ? array_values($expenses)  : []);
    $paymentItems  = $payments['data']  ?? $payments['items']  ?? (is_array($payments)  ? array_values($payments)  : []);
    $projectId = $project['id'] ?? 0;
@endphp

@if($error)
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>{{ $error }}</div>
@endif

<!-- Header -->
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div class="d-flex gap-2">
        <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <a href="{{ route('projects.edit', $projectId) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Edit</a>
    </div>
    <form action="{{ route('projects.destroy', $projectId) }}" method="POST" class="delete-form" data-confirm-message="Are you sure you want to delete this project? All purchases, expenses and payments will also be removed.">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button>
    </form>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-0" id="projectTabs">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#overview">Overview</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#purchases">Purchases <span class="badge bg-secondary">{{ count($purchaseItems) }}</span></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#expenses">Expenses <span class="badge bg-secondary">{{ count($expenseItems) }}</span></a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#payments">Payments <span class="badge bg-secondary">{{ count($paymentItems) }}</span></a>
    </li>
</ul>

<div class="tab-content card border-top-0 rounded-0 rounded-bottom">
    <!-- Overview Tab -->
    <div class="tab-pane fade show active p-3" id="overview">

        {{-- Financial Summary --}}
        @if(!empty($summary))
        @php
            $budget      = (float)($summary['budget'] ?? $project['budget'] ?? 0);
            $totalPurch  = (float)($summary['totalPurchases'] ?? 0);
            $totalExp    = (float)($summary['totalExpenses'] ?? 0);
            $balance     = isset($summary['outstandingAgainstBudget'])
                             ? (float)$summary['outstandingAgainstBudget']
                             : $budget - (float)($summary['totalReceived'] ?? 0);
        @endphp
        <div class="row g-3 mb-4">
            <div class="col-12"><p class="text-muted small fw-semibold text-uppercase mb-0" style="letter-spacing:.05em;">Financial Summary</p></div>
            @if($budget > 0)
            <div class="col-6 col-md-3">
                <div class="card kpi-card h-100" style="border-left-color:#3b82f6;background:#eff6ff;">
                    <div class="card-body d-flex align-items-center gap-3 py-3 px-3">
                        <div class="kpi-icon" style="background:#dbeafe;color:#2563eb;"><i class="bi bi-wallet2"></i></div>
                        <div><div class="kpi-label">Budget</div><div class="kpi-value">{{ number_format($budget, 2) }}</div></div>
                    </div>
                </div>
            </div>
            @endif
            <div class="col-6 col-md-3">
                <div class="card kpi-card h-100" style="border-left-color:#0891b2;background:#ecfeff;">
                    <div class="card-body d-flex align-items-center gap-3 py-3 px-3">
                        <div class="kpi-icon" style="background:#cffafe;color:#0e7490;"><i class="bi bi-cart3"></i></div>
                        <div><div class="kpi-label">Purchases</div><div class="kpi-value">{{ number_format($totalPurch, 2) }}</div></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card kpi-card h-100" style="border-left-color:#d97706;background:#fffbeb;">
                    <div class="card-body d-flex align-items-center gap-3 py-3 px-3">
                        <div class="kpi-icon" style="background:#fef3c7;color:#b45309;"><i class="bi bi-receipt"></i></div>
                        <div><div class="kpi-label">Expenses</div><div class="kpi-value">{{ number_format($totalExp, 2) }}</div></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card kpi-card h-100" style="border-left-color:{{ $balance <= 0 ? '#16a34a' : '#dc2626' }};background:{{ $balance <= 0 ? '#f0fdf4' : '#fef2f2' }};">
                    <div class="card-body d-flex align-items-center gap-3 py-3 px-3">
                        <div class="kpi-icon" style="background:{{ $balance <= 0 ? '#dcfce7' : '#fee2e2' }};color:{{ $balance <= 0 ? '#15803d' : '#b91c1c' }};"><i class="bi bi-{{ $balance <= 0 ? 'check-circle' : 'hourglass-split' }}"></i></div>
                        <div><div class="kpi-label">Balance Due</div><div class="kpi-value" style="color:{{ $balance <= 0 ? '#15803d' : '#b91c1c' }};">{{ number_format($balance, 2) }}</div></div>
                    </div>
                </div>
            </div>
        </div>
        <hr class="my-3">
        @endif

        {{-- Project Details --}}
        <h6 class="text-muted fw-semibold mb-3">Project Details</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Name</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $project['projectName'] ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Customer</dt>
                    <dd class="col-sm-8">
                        @if(!empty($project['customerId']))
                            <a href="{{ route('customers.show', $project['customerId']) }}" class="text-body text-decoration-none" style="color:#374151!important;">{{ $project['customerName'] ?? ($project['customer']['name'] ?? '—') }}</a>
                        @else
                            {{ $project['customerName'] ?? '—' }}
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Location</dt>
                    <dd class="col-sm-8">{{ $project['locationName'] ?? ($project['location']['name'] ?? '—') }}</dd>

                    <dt class="col-sm-4 text-muted">Address</dt>
                    <dd class="col-sm-8">{{ $project['projectAddress'] ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Status</dt>
                    <dd class="col-sm-8">
                        @php $archived = $project['isArchived'] ?? false; @endphp
                        <span class="badge {{ $archived ? 'bg-secondary' : 'bg-primary' }}">
                            {{ $archived ? 'Archived' : 'Active' }}
                        </span>
                    </dd>

                    @if(!empty($project['assignedUserName']))
                    <dt class="col-sm-4 text-muted">Assigned Contractor</dt>
                    <dd class="col-sm-8">{{ $project['assignedUserName'] }}</dd>
                    @endif
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Budget</dt>
                    <dd class="col-sm-8">{{ isset($project['budget']) ? number_format((float)$project['budget'], 2) : '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Start Date</dt>
                    <dd class="col-sm-8">{{ isset($project['startDate']) ? date('d M Y', strtotime($project['startDate'])) : '—' }}</dd>

                    <dt class="col-sm-4 text-muted">Expected End Date</dt>
                    <dd class="col-sm-8">{{ isset($project['endDate']) ? date('d M Y', strtotime($project['endDate'])) : '—' }}</dd>

                    @if(!empty($project['description']))
                    <dt class="col-sm-4 text-muted">Description</dt>
                    <dd class="col-sm-8">{{ $project['description'] }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <!-- Purchases Tab -->
    <div class="tab-pane fade" id="purchases">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Purchases</h6>
            <a href="{{ route('purchases.create', ['projectId' => $projectId, 'from' => 'project']) }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Add Purchase
            </a>
        </div>
        @if(empty($purchaseItems))
            <div class="empty-state"><i class="bi bi-cart3 d-block"></i><p>No purchases yet.</p></div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Date</th><th>Product</th><th class="text-end">Qty</th><th class="text-end">Unit Rate</th><th class="text-end">Total</th><th class="text-center"></th></tr></thead>
                    <tbody>
                        @foreach($purchaseItems as $p)
                        <tr>
                            <td>{{ isset($p['purchaseDate']) ? date('d M Y', strtotime($p['purchaseDate'])) : (isset($p['date']) ? date('d M Y', strtotime($p['date'])) : '—') }}</td>
                            <td>{{ $p['productName'] ?? ($p['product']['name'] ?? '—') }}</td>
                            <td class="text-end">{{ $p['quantity'] ?? '—' }}</td>
                            <td class="text-end">{{ isset($p['unitAmount']) ? number_format((float)$p['unitAmount'], 2) : '—' }}</td>
                            <td class="text-end fw-semibold">{{ isset($p['lineTotal']) ? number_format((float)$p['lineTotal'], 2) : (isset($p['quantity'], $p['unitAmount']) ? number_format((float)$p['quantity'] * (float)$p['unitAmount'], 2) : '—') }}</td>
                            <td class="text-center">
                                <a href="{{ route('purchases.edit', $p['id']) }}?from=project&projectId={{ $projectId }}" class="btn-action" title="Edit"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Expenses Tab -->
    <div class="tab-pane fade" id="expenses">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Expenses</h6>
            <a href="{{ route('expenses.create', ['projectId' => $projectId, 'from' => 'project']) }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Add Expense
            </a>
        </div>
        @if(empty($expenseItems))
            <div class="empty-state"><i class="bi bi-receipt d-block"></i><p>No expenses yet.</p></div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Date</th><th>Heading</th><th class="text-end">Amount</th><th class="text-center"></th></tr></thead>
                    <tbody>
                        @foreach($expenseItems as $e)
                        <tr>
                            <td>{{ isset($e['expenseDate']) ? date('d M Y', strtotime($e['expenseDate'])) : (isset($e['date']) ? date('d M Y', strtotime($e['date'])) : '—') }}</td>
                            <td>{{ $e['expenseHeading'] ?? '—' }}</td>
                            <td class="text-end">{{ isset($e['amount']) ? number_format((float)$e['amount'], 2) : '—' }}</td>
                            <td class="text-center">
                                <a href="{{ route('expenses.edit', $e['id']) }}?from=project&projectId={{ $projectId }}" class="btn-action" title="Edit"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Payments Tab -->
    <div class="tab-pane fade" id="payments">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Payments Received</h6>
            <a href="{{ route('payments.create', ['projectId' => $projectId, 'from' => 'project']) }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Add Payment
            </a>
        </div>
        @if(empty($paymentItems))
            <div class="empty-state"><i class="bi bi-credit-card d-block"></i><p>No payments yet.</p></div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Date</th><th class="text-end">Amount</th><th>Method</th><th class="text-center"></th></tr></thead>
                    <tbody>
                        @foreach($paymentItems as $p)
                        <tr>
                            <td>{{ isset($p['paymentDate']) ? date('d M Y', strtotime($p['paymentDate'])) : (isset($p['date']) ? date('d M Y', strtotime($p['date'])) : '—') }}</td>
                            <td class="text-end">{{ isset($p['amountReceived']) ? number_format((float)$p['amountReceived'], 2) : '—' }}</td>
                            <td>{{ $p['paymentMethod'] ?? '—' }}</td>
                            <td class="text-center">
                                <a href="{{ route('payments.edit', $p['id']) }}?from=project&projectId={{ $projectId }}" class="btn-action" title="Edit"><i class="bi bi-pencil"></i></a>
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
