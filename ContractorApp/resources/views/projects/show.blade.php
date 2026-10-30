@extends('layouts.app')

@section('title', $project->name)

@use('Illuminate\Support\Number')

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 small">
                <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">Projects</a></li>
                <li class="breadcrumb-item active">{{ $project->project_number }}</li>
            </ol>
        </nav>
        <h1 class="page-title mb-0">{{ $project->name }}</h1>
        <div class="d-flex flex-wrap align-items-center gap-2 mt-1 small">
            <x-status-badge :status="$project->status" />
            <span class="text-muted">{{ $project->project_number }}</span>
            @if ($project->type_of_work) <span class="text-muted">· {{ $project->type_of_work }}</span> @endif
            <span class="text-muted">· <a href="{{ route('clients.show', $project->client) }}">{{ $project->client->name }}</a></span>
            @if ($project->isOverdue()) <span class="badge badge-soft-danger">Overdue</span> @endif
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @can('update', $project)
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-arrow-repeat me-1"></i>Change Status
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @foreach (App\Enums\ProjectStatus::cases() as $status)
                        <li>
                            <form method="post" action="{{ route('projects.status', $project) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $status->value }}" />
                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 @if($status === $project->status) active @endif">
                                    <span class="badge {{ $status->badgeClass() }}">{{ $status->label() }}</span>
                                    @if($status === $project->status) <i class="bi bi-check ms-auto"></i> @endif
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </div>
            <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Edit</a>
        @endcan
        @can('delete', $project)
            <x-delete-button :action="route('projects.destroy', $project)" title="Delete this project?" :confirm="'Delete &quot;'.$project->name.'&quot;? This cannot be undone.'" class="btn btn-sm btn-outline-danger" />
        @endcan
    </div>
</div>

{{-- Tabs --}}
<ul class="nav nav-tabs mb-0" id="projectTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link active" data-bs-toggle="tab" href="#overview" role="tab">Overview</a>
    </li>
    @can('viewAny', App\Models\Work::class)
    <li class="nav-item" role="presentation">
        <a class="nav-link" data-bs-toggle="tab" href="#works" role="tab">
            Works <span class="badge bg-secondary ms-1">{{ $works->count() }}</span>
        </a>
    </li>
    @endcan
    @can('viewAny', App\Models\Purchase::class)
    <li class="nav-item" role="presentation">
        <a class="nav-link" data-bs-toggle="tab" href="#purchases" role="tab">
            Purchases <span class="badge bg-secondary ms-1">{{ $purchases->count() }}</span>
        </a>
    </li>
    @endcan
    @can('viewAny', App\Models\Expense::class)
    <li class="nav-item" role="presentation">
        <a class="nav-link" data-bs-toggle="tab" href="#expenses" role="tab">
            Expenses <span class="badge bg-secondary ms-1">{{ $expenses->count() + $works->filter(fn($w) => $w->amount !== null)->count() }}</span>
        </a>
    </li>
    @endcan
    @can('viewAny', App\Models\ProjectPayment::class)
    <li class="nav-item" role="presentation">
        <a class="nav-link" data-bs-toggle="tab" href="#payments" role="tab">
            Payments <span class="badge bg-secondary ms-1">{{ $payments->count() }}</span>
        </a>
    </li>
    @endcan
    <li class="nav-item" role="presentation">
        <a class="nav-link" data-bs-toggle="tab" href="#meetings" role="tab">
            Meetings @if($meetings->isNotEmpty()) <span class="badge bg-secondary ms-1">{{ $meetings->count() }}</span> @endif
        </a>
    </li>
</ul>

<div class="tab-content card border-top-0" style="border-radius:0 0 8px 8px;">

    {{-- ── OVERVIEW TAB ── --}}
    <div class="tab-pane fade show active p-3" id="overview" role="tabpanel">

        {{-- Financial KPI cards --}}
        @canany(['viewAny'], [App\Models\Purchase::class, App\Models\Expense::class, App\Models\ProjectPayment::class])
        <p class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:.05em;">Financial Summary</p>
        @php $bal = $financials['outstanding']; @endphp
        <div class="row g-3 mb-4">
            @if($financials['budget'] > 0)
            <div class="col-6 col-md">
                <div class="card kpi-card h-100" style="border-left-color:#3b82f6;background:#eff6ff;">
                    <div class="card-body d-flex align-items-center gap-3 py-3 px-3">
                        <div class="kpi-icon" style="background:#dbeafe;color:#2563eb;"><i class="bi bi-wallet2"></i></div>
                        <div>
                            <div class="kpi-label">Budget</div>
                            <div class="kpi-value">₹{{ number_format($financials['budget'], 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            <div class="col-6 col-md">
                <div class="card kpi-card h-100" style="border-left-color:#0891b2;background:#ecfeff;">
                    <div class="card-body d-flex align-items-center gap-3 py-3 px-3">
                        <div class="kpi-icon" style="background:#cffafe;color:#0e7490;"><i class="bi bi-cart3"></i></div>
                        <div>
                            <div class="kpi-label">Purchases</div>
                            <div class="kpi-value">₹{{ number_format($financials['purchases_total'], 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="card kpi-card h-100" style="border-left-color:#d97706;background:#fffbeb;">
                    <div class="card-body d-flex align-items-center gap-3 py-3 px-3">
                        <div class="kpi-icon" style="background:#fef3c7;color:#b45309;"><i class="bi bi-receipt"></i></div>
                        <div>
                            <div class="kpi-label">Expenses</div>
                            <div class="kpi-value">₹{{ number_format($financials['expenses_total'], 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="card kpi-card h-100" style="border-left-color:#16a34a;background:#f0fdf4;">
                    <div class="card-body d-flex align-items-center gap-3 py-3 px-3">
                        <div class="kpi-icon" style="background:#dcfce7;color:#15803d;"><i class="bi bi-cash-coin"></i></div>
                        <div>
                            <div class="kpi-label">Collection</div>
                            <div class="kpi-value">₹{{ number_format($financials['payments_total'], 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="card kpi-card h-100" style="border-left-color:{{ $bal <= 0 ? '#16a34a' : '#dc2626' }};background:{{ $bal <= 0 ? '#f0fdf4' : '#fef2f2' }};">
                    <div class="card-body d-flex align-items-center gap-3 py-3 px-3">
                        <div class="kpi-icon" style="background:{{ $bal <= 0 ? '#dcfce7' : '#fee2e2' }};color:{{ $bal <= 0 ? '#15803d' : '#b91c1c' }};"><i class="bi bi-{{ $bal <= 0 ? 'check-circle' : 'hourglass-split' }}"></i></div>
                        <div>
                            <div class="kpi-label">Balance Due</div>
                            <div class="kpi-value" style="color:{{ $bal <= 0 ? '#15803d' : '#b91c1c' }};">₹{{ number_format(abs($bal), 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr class="my-3">
        @endcanany

        {{-- Project Details --}}
        <h6 class="text-muted fw-semibold mb-3">Project Details</h6>
        <div class="row g-3 row-cols-2 row-cols-md-4">
            <div>
                <div class="text-muted small fw-semibold">Customer</div>
                <div><a href="{{ route('clients.show', $project->client) }}">{{ $project->client->name }}</a></div>
            </div>
            @if ($project->type_of_work)
            <div>
                <div class="text-muted small fw-semibold">Type of Work</div>
                <div>{{ $project->type_of_work }}</div>
            </div>
            @endif
            @if ($project->location)
            <div>
                <div class="text-muted small fw-semibold">Location</div>
                <div>{{ $project->location }}</div>
            </div>
            @endif
            @if ($project->budget !== null)
            <div>
                <div class="text-muted small fw-semibold">Budget</div>
                <div>{{ Number::currency((float) $project->budget, 'INR', 'en_IN', 0) }}</div>
            </div>
            @endif
            @if ($project->start_date)
            <div>
                <div class="text-muted small fw-semibold">Start Date</div>
                <div>{{ $project->start_date->format('d M Y') }}</div>
            </div>
            @endif
            @if ($project->expected_end_date)
            <div>
                <div class="text-muted small fw-semibold">Expected End</div>
                <div>{{ $project->expected_end_date->format('d M Y') }}</div>
            </div>
            @endif
            @if ($project->client->phone)
            <div>
                <div class="text-muted small fw-semibold">Phone</div>
                <div><a href="tel:{{ $project->client->phone }}">{{ $project->client->phone }}</a></div>
            </div>
            @endif
            @if ($project->client->email)
            <div>
                <div class="text-muted small fw-semibold">Email</div>
                <div><a href="mailto:{{ $project->client->email }}">{{ $project->client->email }}</a></div>
            </div>
            @endif
        </div>
        @if ($project->description || $project->notes)
        <div class="mt-3">
            @if ($project->description)
                <div class="mb-2">
                    <div class="text-muted small fw-semibold mb-1">Description</div>
                    <div style="white-space:pre-line;">{{ $project->description }}</div>
                </div>
            @endif
            @if ($project->notes)
                <div>
                    <div class="text-muted small fw-semibold mb-1">Notes</div>
                    <div style="white-space:pre-line;">{{ $project->notes }}</div>
                </div>
            @endif
        </div>
        @endif

        {{-- Activity --}}
        @can('activity.view')
        <hr class="my-3">
        <h6 class="text-muted fw-semibold mb-2">Recent Activity</h6>
        @include('partials.activity-feed', ['activity' => $activity])
        @endcan
    </div>

    {{-- ── WORKS TAB ── --}}
    @can('viewAny', App\Models\Work::class)
    <div class="tab-pane fade" id="works" role="tabpanel">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Works <span class="badge bg-secondary ms-1">{{ $works->count() }}</span></h6>
            @can('create', App\Models\Work::class)
                <a href="{{ route('works.create', ['project_id' => $project->id, 'from' => 'project']) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Add Work
                </a>
            @endcan
        </div>
        @if ($works->isEmpty())
            <div class="empty-state"><i class="bi bi-list-check d-block"></i><p>No works recorded yet.</p></div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr>
                        <th>Work</th>
                        <th class="d-none d-sm-table-cell">Due</th>
                        <th class="d-none d-md-table-cell text-end">Amount</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th class="text-center"></th>
                    </tr></thead>
                    <tbody>
                        @foreach ($works as $work)
                        <tr>
                            <td><a href="{{ route('works.show', $work) }}" class="row-title">{{ $work->title }}</a></td>
                            <td class="d-none d-sm-table-cell text-nowrap">
                                {{ $work->due_date?->format('d M Y') ?? '—' }}
                                @if ($work->isOverdue()) <span class="badge badge-soft-danger ms-1">Overdue</span> @endif
                            </td>
                            <td class="d-none d-md-table-cell text-end">{{ $work->amount !== null ? '₹'.number_format((float)$work->amount, 2) : '—' }}</td>
                            <td><x-status-badge :status="$work->priority" /></td>
                            <td><x-status-badge :status="$work->status" /></td>
                            <td class="text-center">
                                @can('update', $work)
                                    <a href="{{ route('works.show', $work) }}" class="btn-action" title="View"><i class="bi bi-eye"></i></a>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @endcan

    {{-- ── PURCHASES TAB ── --}}
    @can('viewAny', App\Models\Purchase::class)
    <div class="tab-pane fade" id="purchases" role="tabpanel">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Purchases — Total: <strong>₹{{ number_format($financials['purchases_total'], 2) }}</strong></h6>
            @can('create', App\Models\Purchase::class)
                <a href="{{ route('purchases.create', ['project_id' => $project->id, 'from' => 'project']) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Add Purchase
                </a>
            @endcan
        </div>
        @if ($purchases->isEmpty())
            <div class="empty-state"><i class="bi bi-cart3 d-block"></i><p>No purchases yet.</p></div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th class="text-end d-none d-md-table-cell">Qty</th>
                        <th class="text-end d-none d-md-table-cell">Unit Rate</th>
                        <th class="text-end fw-semibold">Total</th>
                        <th class="text-center"></th>
                    </tr></thead>
                    <tbody>
                        @foreach ($purchases as $purchase)
                        <tr>
                            <td class="text-nowrap">{{ $purchase->purchase_date?->format('d M Y') ?? '—' }}</td>
                            <td>
                                {{ $purchase->product?->name ?? '—' }}
                                @if ($purchase->supplier)<div class="cell-sub">{{ $purchase->supplier }}</div>@endif
                            </td>
                            <td class="text-end d-none d-md-table-cell">{{ number_format((float)$purchase->quantity, 2) }} {{ $purchase->product?->unit }}</td>
                            <td class="text-end d-none d-md-table-cell">₹{{ number_format((float)$purchase->unit_amount, 2) }}</td>
                            <td class="text-end fw-semibold">₹{{ number_format((float)$purchase->line_total, 2) }}</td>
                            <td class="text-center">
                                @can('update', $purchase)
                                    <a href="{{ route('purchases.edit', [$purchase, 'from' => 'project']) }}" class="btn-action" title="Edit"><i class="bi bi-pencil"></i></a>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @endcan

    {{-- ── EXPENSES TAB ── --}}
    @can('viewAny', App\Models\Expense::class)
    <div class="tab-pane fade" id="expenses" role="tabpanel">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Expenses — Total: <strong>₹{{ number_format($financials['expenses_total'], 2) }}</strong></h6>
            @can('create', App\Models\Expense::class)
                <a href="{{ route('expenses.create', ['project_id' => $project->id, 'from' => 'project']) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Add Expense
                </a>
            @endcan
        </div>
        @if ($expenses->isEmpty() && $works->filter(fn($w) => $w->amount !== null)->isEmpty())
            <div class="empty-state"><i class="bi bi-receipt d-block"></i><p>No expenses yet.</p></div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr>
                        <th>Date</th>
                        <th>Heading</th>
                        <th class="d-none d-md-table-cell">Category</th>
                        <th class="d-none d-md-table-cell">Method</th>
                        <th class="text-end fw-semibold">Amount</th>
                        <th class="text-center"></th>
                    </tr></thead>
                    <tbody>
                        @foreach ($expenses as $expense)
                        <tr>
                            <td class="text-nowrap">{{ $expense->expense_date?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $expense->heading }}</td>
                            <td class="d-none d-md-table-cell">{{ $expense->category ?? '—' }}</td>
                            <td class="d-none d-md-table-cell">{{ $expense->payment_method ?? '—' }}</td>
                            <td class="text-end fw-semibold">₹{{ number_format((float)$expense->amount, 2) }}</td>
                            <td class="text-center">
                                @can('update', $expense)
                                    <a href="{{ route('expenses.edit', [$expense, 'from' => 'project']) }}" class="btn-action" title="Edit"><i class="bi bi-pencil"></i></a>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                        @foreach ($works->filter(fn($w) => $w->amount !== null) as $work)
                        <tr>
                            <td class="text-nowrap">{{ $work->created_at->format('d M Y') }}</td>
                            <td>{{ $work->title }} <span class="badge bg-secondary ms-1">Work</span></td>
                            <td class="d-none d-md-table-cell">Labour</td>
                            <td class="d-none d-md-table-cell">—</td>
                            <td class="text-end fw-semibold">₹{{ number_format((float)$work->amount, 2) }}</td>
                            <td class="text-center">
                                @can('update', $work)
                                    <a href="{{ route('works.edit', [$work, 'from' => 'project']) }}" class="btn-action" title="Edit"><i class="bi bi-pencil"></i></a>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @endcan

    {{-- ── PAYMENTS TAB ── --}}
    @can('viewAny', App\Models\ProjectPayment::class)
    <div class="tab-pane fade" id="payments" role="tabpanel">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Payments Received — Total: <strong class="text-success">₹{{ number_format($financials['payments_total'], 2) }}</strong></h6>
            @can('create', App\Models\ProjectPayment::class)
                <a href="{{ route('payments.create', ['project_id' => $project->id, 'from' => 'project']) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Add Payment
                </a>
            @endcan
        </div>
        @if ($payments->isEmpty())
            <div class="empty-state"><i class="bi bi-credit-card d-block"></i><p>No payments yet.</p></div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr>
                        <th>Date</th>
                        <th class="d-none d-md-table-cell">Method</th>
                        <th class="d-none d-lg-table-cell">Reference</th>
                        <th class="text-end fw-semibold text-success">Amount</th>
                        <th class="text-center"></th>
                    </tr></thead>
                    <tbody>
                        @foreach ($payments as $payment)
                        <tr>
                            <td class="text-nowrap">{{ $payment->payment_date?->format('d M Y') ?? '—' }}</td>
                            <td class="d-none d-md-table-cell">{{ $payment->payment_method ?? '—' }}</td>
                            <td class="d-none d-lg-table-cell">{{ $payment->receipt_reference ?? '—' }}</td>
                            <td class="text-end fw-semibold text-success">₹{{ number_format((float)$payment->amount_received, 2) }}</td>
                            <td class="text-center">
                                @can('update', $payment)
                                    <a href="{{ route('payments.edit', [$payment, 'from' => 'project']) }}" class="btn-action" title="Edit"><i class="bi bi-pencil"></i></a>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @endcan

    {{-- ── MEETINGS TAB ── --}}
    <div class="tab-pane fade" id="meetings" role="tabpanel">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-journal-text me-1"></i>Meeting History</h6>
            @can('update', $project)
                <button type="button" class="btn btn-sm btn-primary" id="toggleLogMeeting">
                    <i class="bi bi-plus-circle me-1"></i>Log Meeting
                </button>
            @endcan
        </div>

        @can('update', $project)
        <div class="p-3 border-bottom" id="logMeetingFormWrap" style="display:none;">
            <form method="post" action="{{ route('projects.meetings.store', $project) }}" id="logMeetingForm">
                @csrf
                <div class="row g-2">
                    <div class="col-sm-6">
                        <label for="log_meeting_at" class="form-label form-label-sm fw-semibold">Date &amp; Time <span class="req">*</span></label>
                        <input type="datetime-local" class="form-control form-control-sm @error('meeting_at') is-invalid @enderror"
                            name="meeting_at" id="log_meeting_at"
                            value="{{ old('meeting_at', now()->format('Y-m-d\TH:i')) }}" required />
                        @error('meeting_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label for="log_purpose" class="form-label form-label-sm fw-semibold">Purpose <span class="req">*</span></label>
                        <input type="text" class="form-control form-control-sm @error('purpose') is-invalid @enderror"
                            name="purpose" id="log_purpose"
                            value="{{ old('purpose') }}" maxlength="500" placeholder="e.g. Site inspection…" required autocomplete="off" />
                        @error('purpose')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    <button type="button" class="btn btn-secondary btn-sm" id="cancelLogMeeting">Cancel</button>
                </div>
            </form>
        </div>
        @endcan

        @if ($meetings->isEmpty())
            <div class="empty-state"><i class="bi bi-journal-text d-block"></i><p>No meetings logged yet.</p></div>
        @else
            <ul class="feed">
                @foreach ($meetings as $meeting)
                    <li class="feed-item align-items-start">
                        <div class="deadline-date flex-shrink-0">
                            <span class="day">{{ $meeting->meeting_at->day }}</span>
                            <span class="month">{{ $meeting->meeting_at->format('M Y') }}</span>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold">{{ $meeting->purpose }}</div>
                            <div class="feed-meta">
                                {{ $meeting->meeting_at->format('h:i A') }}
                                @if ($meeting->createdBy) · {{ $meeting->createdBy->name }} @endif
                                · {{ $meeting->created_at->diffForHumans() }}
                            </div>
                        </div>
                        @can('update', $project)
                            <form method="post" action="{{ route('projects.meetings.destroy', [$project, $meeting]) }}" class="flex-shrink-0 ms-2">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-action btn-action-danger" onclick="return confirm('Remove this meeting entry?')" title="Delete">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        @endcan
                    </li>
                @endforeach
            </ul>
        @endif
    </div>


</div>{{-- end tab-content --}}
@endsection

@push('scripts')
<script>
(function () {
    /* Log meeting toggle */
    var toggleLog = document.getElementById('toggleLogMeeting');
    if (toggleLog) {
        var logWrap = document.getElementById('logMeetingFormWrap');
        var cancelLog = document.getElementById('cancelLogMeeting');
        @if ($errors->has('meeting_at') || $errors->has('purpose'))
        if (logWrap) logWrap.style.display = '';
        // Switch to meetings tab if validation error
        var meetingsTab = document.querySelector('[href="#meetings"]');
        if (meetingsTab) new bootstrap.Tab(meetingsTab).show();
        @endif
        toggleLog.addEventListener('click', function () {
            logWrap.style.display = logWrap.style.display === 'none' ? '' : 'none';
        });
        if (cancelLog) cancelLog.addEventListener('click', function () { logWrap.style.display = 'none'; });
    }

})();
</script>
@endpush
