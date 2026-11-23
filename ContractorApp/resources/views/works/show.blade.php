@extends('layouts.app')

@section('title', $work->title)

@section('content')
<x-page-header :title="$work->title">
    <x-slot:breadcrumbs>
        <li class="breadcrumb-item"><a href="{{ route('works.index') }}">Works</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($work->title, 40) }}</li>
    </x-slot:breadcrumbs>
    <x-slot:actions>
        @can('update', $work)
            @if ($work->status !== App\Enums\WorkStatus::Completed)
                <form method="post" action="{{ route('works.status', $work) }}" class="d-inline">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="completed" />
                    <button type="submit" class="btn btn-success btn-sm" data-loading-text="Updating…"><i class="bi bi-check2 me-1"></i>Mark completed</button>
                </form>
            @endif
            @include('works.status-menu', ['work' => $work])
        @endcan
        @can('delete', $work)
            <x-delete-button :action="route('works.destroy', $work)" title="Delete this work?" :confirm="'Delete &quot;'.$work->title.'&quot;? This cannot be undone.'" />
        @endcan
        @can('update', $work)
            <a href="{{ route('works.edit', $work) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="row g-3">
    {{-- LEFT: Details, Description, Work Expenses --}}
    <div class="col-12 col-lg-7 d-flex flex-column gap-3">

        <x-card title="Details">
            <dl class="detail-list">
                <dt>Status</dt><dd><x-status-badge :status="$work->status" /> @if ($work->isOverdue()) <span class="badge badge-soft-danger">Overdue</span> @endif</dd>
                <dt>Priority</dt><dd><x-status-badge :status="$work->priority" /></dd>
                <dt>Project</dt><dd><a href="{{ route('projects.show', $work->project) }}">{{ $work->project->name }}</a></dd>
                <dt>Client</dt><dd>@if ($work->client)<a href="{{ route('clients.show', $work->client) }}">{{ $work->client->name }}</a>@else — @endif</dd>
            </dl>
        </x-card>

        @if ($work->description)
            <x-card title="Description"><p class="mb-0" style="white-space: pre-line">{{ $work->description }}</p></x-card>
        @endif

        {{-- Work Expenses --}}
        <x-card title="Work Expenses">
            @can('update', $work)
            <form method="post" action="{{ route('works.expenses.store', $work) }}" class="mb-3">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-sm-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">Amount <span class="req">*</span></label>
                        <input type="number" name="amount" class="form-control form-control-sm" placeholder="0.00" min="0.01" step="0.01" required />
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label form-label-sm fw-semibold mb-1">Date <span class="req">*</span></label>
                        <input type="date" name="expense_date" class="form-control form-control-sm" value="{{ today()->format('Y-m-d') }}" required />
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">Method</label>
                        <div class="cs-wrapper" id="workExpPmWrapper">
                            <div class="cs-trigger">
                                <span class="cs-value cs-placeholder">Select…</span>
                                <i class="bi bi-chevron-down cs-arrow"></i>
                            </div>
                            <div class="cs-panel">
                                <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                                <div class="cs-list">
                                    <div class="cs-item" data-value="">— None —</div>
                                    @foreach($paymentMethods as $pm)
                                        <div class="cs-item" data-value="{{ $pm }}">{{ $pm }}</div>
                                    @endforeach
                                </div>
                            </div>
                            <input type="hidden" name="payment_method" id="workExpPmInput" value="">
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label form-label-sm fw-semibold mb-1">Notes</label>
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional…" maxlength="500" />
                    </div>
                    <div class="col-sm-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-lg me-1"></i>Add</button>
                    </div>
                </div>
            </form>
            @endcan

            @if($work->expenses->isEmpty())
                <p class="text-muted small mb-0">No expenses recorded yet.</p>
            @else
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Notes</th>
                            <th class="text-end">Amount</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($work->expenses as $exp)
                        <tr>
                            <td class="text-muted small text-nowrap">{{ $exp->expense_date->format('d M Y') }}</td>
                            <td class="text-muted small">{{ $exp->payment_method ?? '—' }}</td>
                            <td class="text-muted small">{{ $exp->notes ?? '—' }}</td>
                            <td class="text-end fw-semibold small text-nowrap">₹{{ number_format((float)$exp->amount, 2) }}</td>
                            <td class="text-end">
                                @can('update', $work)
                                <form method="post" action="{{ route('works.expenses.destroy', [$work, $exp]) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-action btn-action-danger" onclick="return confirm('Delete this expense?')" title="Delete">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="border-top:2px solid #dee2e6">
                            <td colspan="3" class="fw-semibold small">Total</td>
                            <td class="text-end fw-bold small text-nowrap">₹{{ number_format($work->expenses->sum(fn($e) => (float)$e->amount), 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </x-card>

        {{-- Collections --}}
        <x-card title="Collections">
            @can('update', $work)
            <form method="post" action="{{ route('works.collections.store', $work) }}" class="mb-3">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-sm-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">Amount <span class="req">*</span></label>
                        <input type="number" name="amount_received" class="form-control form-control-sm" placeholder="0.00" min="0.01" step="0.01" required />
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label form-label-sm fw-semibold mb-1">Date <span class="req">*</span></label>
                        <input type="date" name="payment_date" class="form-control form-control-sm" value="{{ today()->format('Y-m-d') }}" required />
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label form-label-sm fw-semibold mb-1">Method</label>
                        <div class="cs-wrapper" id="workColPmWrapper">
                            <div class="cs-trigger">
                                <span class="cs-value cs-placeholder">Select…</span>
                                <i class="bi bi-chevron-down cs-arrow"></i>
                            </div>
                            <div class="cs-panel">
                                <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                                <div class="cs-list">
                                    <div class="cs-item" data-value="">— None —</div>
                                    @foreach($paymentMethods as $pm)
                                        <div class="cs-item" data-value="{{ $pm }}">{{ $pm }}</div>
                                    @endforeach
                                </div>
                            </div>
                            <input type="hidden" name="payment_method" id="workColPmInput" value="">
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label form-label-sm fw-semibold mb-1">Reference</label>
                        <input type="text" name="receipt_reference" class="form-control form-control-sm" placeholder="Receipt / ref no." maxlength="100" />
                    </div>
                    <div class="col-sm-2">
                        <button type="submit" class="btn btn-success btn-sm w-100"><i class="bi bi-plus-lg me-1"></i>Add</button>
                    </div>
                </div>
            </form>
            @endcan

            @if($work->collections->isEmpty())
                <p class="text-muted small mb-0">No collections recorded yet.</p>
            @else
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th class="text-end">Amount</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($work->collections as $col)
                        <tr>
                            <td class="text-muted small text-nowrap">{{ $col->payment_date->format('d M Y') }}</td>
                            <td class="text-muted small">{{ $col->payment_method ?? '—' }}</td>
                            <td class="text-muted small">{{ $col->receipt_reference ?? '—' }}</td>
                            <td class="text-end fw-semibold small text-nowrap">₹{{ number_format((float)$col->amount_received, 2) }}</td>
                            <td class="text-end">
                                @can('update', $work)
                                <form method="post" action="{{ route('works.collections.destroy', [$work, $col]) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-action btn-action-danger" onclick="return confirm('Delete this collection?')" title="Delete">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="border-top:2px solid #dee2e6">
                            <td colspan="3" class="fw-semibold small">Total</td>
                            <td class="text-end fw-bold small text-nowrap text-success">₹{{ number_format($work->collections->sum(fn($c) => (float)$c->amount_received), 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </x-card>

        @if($work->employees->isNotEmpty())
        <x-card title="Assigned Employees">
            <div class="d-flex flex-wrap gap-2">
                @foreach($work->employees as $emp)
                <a href="{{ route('employees.show', $emp) }}" class="d-inline-flex align-items-center gap-2 border rounded px-3 py-2 text-decoration-none text-body" style="font-size:.88rem">
                    <i class="bi bi-person-fill text-muted"></i>
                    <span>{{ $emp->name }}</span>
                </a>
                @endforeach
            </div>
        </x-card>
        @endif

    </div>

    {{-- RIGHT: Schedule, Workers, Site Workers Log --}}
    <div class="col-12 col-lg-5 d-flex flex-column gap-3">

        <x-card title="Schedule">
            <dl class="detail-list">
                <dt>Start date</dt><dd>{{ $work->start_date?->format('d M Y') ?? '—' }}</dd>
                <dt>Due date</dt><dd>{{ $work->due_date?->format('d M Y') ?? '—' }}</dd>
                <dt>Completed</dt><dd>{{ $work->completed_at?->format('d M Y, h:i A') ?? '—' }}</dd>
                <dt>Created</dt><dd>{{ $work->created_at->format('d M Y') }}</dd>
            </dl>
        </x-card>

        {{-- Workers (casual / named) --}}
        <x-card title="Workers">
            @can('update', $work)
            <form id="addWorkerForm" class="mb-3 d-flex gap-2">
                <input type="text" id="workerNameInput" class="form-control form-control-sm" placeholder="Worker name…" maxlength="200" required />
                <button type="submit" class="btn btn-primary btn-sm text-nowrap">Add</button>
            </form>
            @endcan

            <div id="workersContainer">
                @if($work->workers->isEmpty())
                    <p class="text-muted small mb-0" id="noWorkersMsg">No workers added yet.</p>
                @else
                    <div class="d-flex flex-wrap gap-2" id="workersList">
                        @foreach($work->workers as $worker)
                        <span class="d-inline-flex align-items-center gap-2 badge badge-soft-secondary px-3 py-2" style="font-size:1rem;font-weight:500">
                            <i class="bi bi-person"></i> {{ $worker->name }}
                            @can('update', $work)
                            <button type="button" class="border-0 bg-transparent p-0 text-muted remove-worker-btn ms-1" style="line-height:1;cursor:pointer" title="Remove" data-id="{{ $worker->id }}">
                                <i class="bi bi-x" style="font-size:1rem"></i>
                            </button>
                            @endcan
                        </span>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-card>

        <x-card title="Site Workers Log">
            @can('update', $work)
            <form method="post" action="{{ route('works.daily-workers.store', $work) }}" class="mb-3">
                @csrf
                <div class="row g-2 align-items-end">
                    <div class="col-sm-4">
                        <label class="form-label form-label-sm fw-semibold mb-1">Date <span class="req">*</span></label>
                        <input type="date" name="date" class="form-control form-control-sm"
                               value="{{ old('date', today()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label form-label-sm fw-semibold mb-1">Morning <span class="req">*</span></label>
                        <input type="number" name="morning_count" class="form-control form-control-sm"
                               placeholder="0" min="0" max="9999" value="{{ old('morning_count', 0) }}" required>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label form-label-sm fw-semibold mb-1">Evening</label>
                        <input type="number" name="evening_count" class="form-control form-control-sm"
                               placeholder="0" min="0" max="9999" value="{{ old('evening_count', 0) }}">
                    </div>
                    <div class="col-sm-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Save</button>
                    </div>
                </div>
            </form>
            @endcan

            @if($work->dailyWorkers->isEmpty())
                <p class="text-muted small mb-0">No site worker records yet.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr>
                            <th>Date</th>
                            <th class="text-center">Morning</th>
                            <th class="text-center">Evening</th>
                            <th class="text-center">Total</th>
                            <th></th>
                        </tr></thead>
                        <tbody>
                            @foreach($work->dailyWorkers as $log)
                            <tr>
                                <td class="text-nowrap">{{ $log->date->format('d M Y') }}</td>
                                <td class="text-center">{{ $log->morning_count }}</td>
                                <td class="text-center">{{ $log->evening_count }}</td>
                                <td class="text-center fw-semibold">{{ $log->morning_count + $log->evening_count }}</td>
                                <td class="text-center">
                                    @can('update', $work)
                                    <form method="post" action="{{ route('works.daily-workers.destroy', [$work, $log]) }}" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-action btn-action-danger" onclick="return confirm('Delete this record?')" title="Delete">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-semibold" style="border-top:2px solid #dee2e6">
                                <td>Total</td>
                                <td class="text-center">{{ $work->dailyWorkers->sum('morning_count') }}</td>
                                <td class="text-center">{{ $work->dailyWorkers->sum('evening_count') }}</td>
                                <td class="text-center">{{ $work->dailyWorkers->sum(fn($l) => $l->morning_count + $l->evening_count) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </x-card>

    </div>
</div>

@push('scripts')
<script>
(function () {
    // Expense payment method CS-dropdown
    var expPmCs    = new CustomSelect('workExpPmWrapper');
    var expPmInput = document.getElementById('workExpPmInput');
    var expPmOrig  = expPmCs._select.bind(expPmCs);
    expPmCs._select = function (v, l) { expPmOrig(v, l); if (expPmInput) expPmInput.value = v; };

    // Collections payment method CS-dropdown
    var colPmCs    = new CustomSelect('workColPmWrapper');
    var colPmInput = document.getElementById('workColPmInput');
    var colPmOrig  = colPmCs._select.bind(colPmCs);
    colPmCs._select = function (v, l) { colPmOrig(v, l); if (colPmInput) colPmInput.value = v; };

    // Workers AJAX
    var storeUrl  = '{{ route('works.workers.store', $work) }}';
    var csrfToken = (document.querySelector('meta[name=csrf-token]') || {}).content || '';
    var addForm   = document.getElementById('addWorkerForm');
    var nameInput = document.getElementById('workerNameInput');
    var container = document.getElementById('workersContainer');

    function escHtml(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function getOrCreateList() {
        var list = document.getElementById('workersList');
        if (!list) {
            var msg = document.getElementById('noWorkersMsg');
            if (msg) msg.remove();
            list = document.createElement('div');
            list.id = 'workersList';
            list.className = 'd-flex flex-wrap gap-2';
            container.appendChild(list);
        }
        return list;
    }

    function appendChip(worker) {
        var list = getOrCreateList();
        var chip = document.createElement('span');
        chip.className = 'd-inline-flex align-items-center gap-2 badge badge-soft-secondary px-3 py-2';
        chip.style.cssText = 'font-size:1rem;font-weight:500';
        chip.innerHTML =
            '<i class="bi bi-person"></i> ' + escHtml(worker.name) +
            '<button type="button" class="border-0 bg-transparent p-0 text-muted remove-worker-btn ms-1"' +
            ' style="line-height:1;cursor:pointer" title="Remove" data-id="' + worker.id + '">' +
            '<i class="bi bi-x" style="font-size:1rem"></i></button>';
        list.appendChild(chip);
    }

    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var name = nameInput.value.trim();
            if (!name) return;
            var btn = addForm.querySelector('button[type=submit]');
            btn.disabled = true;
            var params = new URLSearchParams();
            params.append('name', name);
            fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: params.toString(),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.worker) {
                    appendChip(data.worker);
                    nameInput.value = '';
                }
                btn.disabled = false;
            })
            .catch(function () { btn.disabled = false; });
        });
    }

    if (container) {
        container.addEventListener('click', function (e) {
            var btn = e.target.closest('.remove-worker-btn');
            if (!btn || !confirm('Remove this worker?')) return;
            var id = btn.dataset.id;
            fetch(storeUrl + '/' + id, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            })
            .then(function (r) {
                if (r.ok) {
                    btn.closest('span').remove();
                    var list = document.getElementById('workersList');
                    if (list && !list.querySelector('span')) {
                        list.remove();
                        var p = document.createElement('p');
                        p.id = 'noWorkersMsg';
                        p.className = 'text-muted small mb-0';
                        p.textContent = 'No workers added yet.';
                        container.appendChild(p);
                    }
                }
            });
        });
    }
})();
</script>
@endpush
@endsection
