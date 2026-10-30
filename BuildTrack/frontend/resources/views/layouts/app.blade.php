<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BuildTrack &mdash; @yield('title', 'Dashboard')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --sidebar-bg: #1a3a5c;
            --sidebar-text: #c8d8e8;
            --sidebar-hover: #2a5080;
            --sidebar-active: #3a6090;
            --sidebar-width: 250px;
            --topbar-height: 56px;
        }

        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        #sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background-color: var(--sidebar-bg);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        #sidebar .sidebar-brand {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        #sidebar .sidebar-brand h5 {
            color: #fff;
            font-weight: 700;
            font-size: 1.25rem;
            margin: 0;
        }

        #sidebar .sidebar-brand small {
            color: var(--sidebar-text);
            font-size: 0.75rem;
        }

        #sidebar .nav-link {
            color: var(--sidebar-text);
            padding: 0.6rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            transition: background-color 0.2s;
            border-left: 3px solid transparent;
        }

        #sidebar .nav-link:hover {
            background-color: var(--sidebar-hover);
            color: #fff;
        }

        #sidebar .nav-link.active {
            background-color: var(--sidebar-active);
            color: #fff;
            border-left-color: #7ec8f7;
        }

        #sidebar .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        #sidebar .nav-section {
            padding: 0.75rem 1.5rem 0.25rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(200, 216, 232, 0.5);
            font-weight: 600;
        }

        #main-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        #topbar {
            height: var(--topbar-height);
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        #topbar .page-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a3a5c;
            margin: 0;
        }

        #main-content {
            padding: 1.5rem;
            flex: 1;
        }

        .card {
            border: none;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            border-radius: 8px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
        }

        .stat-card {
            border-radius: 10px;
            padding: 1.25rem;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .stat-card .stat-value {
            font-size: 1.6rem;
            font-weight: 700;
        }

        .stat-card .stat-label {
            font-size: 0.8rem;
            opacity: 0.85;
        }

        .stat-card .stat-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.5rem;
            opacity: 0.2;
        }

        .btn-sidebar-toggle {
            display: none;
        }

        @media (max-width: 768px) {
            #sidebar {
                transform: translateX(-100%);
            }

            #sidebar.show {
                transform: translateX(0);
            }

            #main-wrapper {
                margin-left: 0;
            }

            .btn-sidebar-toggle {
                display: inline-flex;
            }
        }

        .table th {
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6c757d;
            background-color: #f8f9fa;
        }

        .badge-status {
            font-size: 0.75rem;
        }

        .empty-state {
            padding: 3rem;
            text-align: center;
            color: #adb5bd;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* ── Dropdowns & form controls — force light theme regardless of OS dark mode ── */
        .form-select,
        .form-control,
        textarea.form-control {
            background-color: #ffffff !important;
            color: #212529 !important;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .form-select:focus,
        .form-control:focus {
            background-color: #ffffff !important;
            color: #212529 !important;
            border-color: #1a3a5c;
            box-shadow: 0 0 0 0.18rem rgba(26, 58, 92, 0.18);
            outline: none;
        }

        .form-select:disabled,
        .form-control:disabled,
        .form-select[readonly],
        .form-control[readonly] {
            background-color: #f8f9fa !important;
            color: #6c757d !important;
        }

        /* Placeholder / empty option */
        .form-select option[value=""],
        .form-select option.placeholder {
            color: #6c757d;
            font-style: italic;
        }

        /* All options — override dark-mode OS rendering */
        .form-select option {
            background-color: #ffffff;
            color: #212529;
        }

        /* No-data option */
        .form-select option.no-data {
            color: #adb5bd;
            font-style: italic;
        }

        /* Labels */
        .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #344054;
            margin-bottom: 0.35rem;
        }

        /* ── Custom searchable select ── */
        .cs-wrapper { position: relative; }
        .cs-trigger {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.45rem 0.85rem; background: #fff; border: 1px solid #ced4da;
            border-radius: 6px; cursor: pointer; font-size: 0.9rem; color: #212529;
            min-height: 38px; user-select: none; transition: border-color 0.15s, box-shadow 0.15s;
        }
        .cs-trigger:hover { border-color: #adb5bd; }
        .cs-wrapper.open .cs-trigger {
            border-color: #1a3a5c; border-bottom-left-radius: 0; border-bottom-right-radius: 0;
            box-shadow: 0 0 0 0.18rem rgba(26,58,92,0.18);
        }
        .cs-trigger.is-invalid { border-color: #dc3545 !important; }
        .cs-placeholder { color: #6c757d; font-style: italic; }
        .cs-arrow { font-size: 0.75rem; color: #6c757d; flex-shrink: 0; transition: transform 0.15s; }
        .cs-wrapper.open .cs-arrow { transform: rotate(180deg); }
        .cs-panel {
            display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 1050;
            background: #fff; border: 1px solid #1a3a5c; border-top: none;
            border-bottom-left-radius: 6px; border-bottom-right-radius: 6px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        .cs-wrapper.open .cs-panel { display: block; }
        .cs-search-wrap {
            display: flex; align-items: center; gap: 0.5rem;
            padding: 0.5rem 0.75rem; border-bottom: 1px solid #e9ecef;
        }
        .cs-search-wrap i { color: #9ca3af; font-size: 0.85rem; }
        .cs-search { border: none; outline: none; flex: 1; font-size: 0.9rem; background: transparent; color: #212529; }
        .cs-search::placeholder { color: #9ca3af; }
        .cs-list { max-height: 210px; overflow-y: auto; }
        .cs-item { padding: 0.55rem 0.85rem; cursor: pointer; font-size: 0.9rem; color: #212529; transition: background 0.1s; }
        .cs-item:hover { background: #f0f4f8; }
        .cs-item.selected { background: #e8f0fe; color: #1a3a5c; font-weight: 500; }
        .cs-item.no-data { color: #6c757d; font-style: italic; cursor: default; text-align: center; padding: 0.9rem 0.85rem; }
        .cs-item.no-data:hover { background: transparent; }
        .cs-footer { border-top: 1px solid #e9ecef; padding: 0.3rem 0.4rem; }
        .cs-add-btn {
            display: flex; align-items: center; gap: 0.4rem; width: 100%;
            background: none; border: none; padding: 0.45rem 0.5rem;
            font-size: 0.875rem; font-weight: 600; color: #1a3a5c;
            cursor: pointer; border-radius: 4px; text-align: left;
        }
        .cs-add-btn:hover { background: #f0f4f8; }
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
        .card { border-color: #e2e8f0; }
        .card-header { background-color: #f8fafc; border-bottom-color: #e2e8f0; }
        .table thead th { background-color: #f8fafc; color: #64748b; font-size: 0.75rem; letter-spacing: 0.05em; text-transform: uppercase; }

        /* ── Table link normalization: dark by default, brand blue on hover ── */
        .table td a, .table td a:visited { color: #374151; text-decoration: none; }
        .table td a:hover, .table td a:focus { color: #1a3a5c; text-decoration: underline; }

        /* ── Row action buttons ── */
        .btn-action {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0.22rem 0.48rem; font-size: 0.8rem; line-height: 1.4;
            border: 1px solid #e2e8f0; border-radius: 6px;
            background: transparent; color: #64748b; cursor: pointer;
            text-decoration: none !important;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }
        .btn-action:hover, .btn-action:focus { background: #f1f5f9; border-color: #cbd5e1; color: #1a3a5c; }
        .btn-action-danger { color: #dc2626 !important; border-color: #fecaca !important; }
        .btn-action-danger:hover, .btn-action-danger:focus { background: #fef2f2 !important; border-color: #fca5a5 !important; color: #b91c1c !important; }
        button.btn-action { background: transparent; }

        /* ── Shared KPI summary cards ── */
        .kpi-card {
            border-radius: 8px !important; border: 1px solid #e2e8f0 !important;
            border-left-width: 3px !important; box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
            transition: box-shadow 0.15s;
        }
        .kpi-card:hover { box-shadow: 0 3px 8px rgba(0,0,0,0.09) !important; }
        .kpi-icon {
            width: 40px; height: 40px; border-radius: 8px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
        }
        .kpi-label {
            font-size: 0.7rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.06em; color: #94a3b8; margin-bottom: 3px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .kpi-value {
            font-size: 1.05rem; font-weight: 700; color: #1a3a5c;
            font-variant-numeric: tabular-nums; letter-spacing: -0.01em;
        }

        /* ── Module search toolbar inside card-body ── */
        .search-bar { padding: 0.6rem 1rem; border-bottom: 1px solid #e2e8f0; background: #fafafa; }
        .search-bar .input-group-text { background: #fff; border-right: none; font-size: 0.85rem; }
        .search-bar .form-control { border-left: none; padding-left: 0; font-size: 0.875rem; }
        .search-bar .form-control:focus { border-left: none; }

        /* ── Global table refinement ── */
        /* Side breathing room: first and last cells get extra horizontal padding */
        .card .table > thead > tr > th:first-child,
        .card .table > tbody > tr > td:first-child,
        .card .table > tfoot > tr > td:first-child { padding-left: 1.25rem; }
        .card .table > thead > tr > th:last-child,
        .card .table > tbody > tr > td:last-child,
        .card .table > tfoot > tr > td:last-child  { padding-right: 1.25rem; }

        /* Vertical rhythm: consistent row height */
        .card .table > thead > tr > th { padding-top: 0.65rem; padding-bottom: 0.65rem; }
        .card .table > tbody > tr > td { padding-top: 0.7rem; padding-bottom: 0.7rem; vertical-align: middle; }

        /* Header: stronger separator, no double-border at top */
        .card .table > thead > tr > th {
            border-top: none;
            border-bottom: 2px solid #e2e8f0 !important;
        }

        /* Body rows: subtle divider, no Bootstrap default thick border */
        .card .table > tbody > tr > td { border-bottom: 1px solid #f1f5f9; }
        .card .table > tbody > tr:last-child > td { border-bottom: none; }

        /* Hover: softer tint */
        .card .table-hover > tbody > tr:hover > td { background-color: #f8fafc; }
    </style>
</head>
<body>

<div id="sidebar">
    <div class="sidebar-brand">
        <h5><i class="bi bi-building-gear me-2"></i>BuildTrack</h5>
        <small>Contractor Manager</small>
    </div>

    <nav class="mt-2">
        <div class="nav-section">Main</div>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <div class="nav-section">Work</div>
        <a href="{{ route('projects.index') }}" class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}">
            <i class="bi bi-kanban"></i> Projects
        </a>
        <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Customers
        </a>
        <a href="{{ route('locations.index') }}" class="nav-link {{ request()->routeIs('locations.*') ? 'active' : '' }}">
            <i class="bi bi-geo-alt"></i> Locations
        </a>

        <div class="nav-section">Inventory</div>
        <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
            <i class="bi bi-box-seam"></i> Products
        </a>

        <div class="nav-section">Finance</div>
        <a href="{{ route('purchases.index') }}" class="nav-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
            <i class="bi bi-cart3"></i> Purchases
        </a>
        <a href="{{ route('expenses.index') }}" class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
            <i class="bi bi-receipt"></i> Expenses
        </a>
        <a href="{{ route('payments.index') }}" class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}">
            <i class="bi bi-credit-card"></i> Payments
        </a>

        <div class="nav-section">Reports</div>
        <a href="{{ route('reports.outstanding') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <i class="bi bi-graph-up-arrow"></i> Outstanding
        </a>

        @if((session('auth_user')['role'] ?? '') === 'Admin')
        <div class="nav-section">Admin</div>
        <a href="{{ route('users.index') }}" class="nav-link {{ request()->is('users*') ? 'active' : '' }}">
            <i class="bi bi-people-fill"></i> Users
        </a>
        @endif
    </nav>
</div>

<div id="main-wrapper">
    <div id="topbar">
        <button class="btn btn-sm btn-outline-secondary btn-sidebar-toggle me-3" onclick="document.getElementById('sidebar').classList.toggle('show')">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="page-title me-auto">@yield('title', 'Dashboard')</h1>
        <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                    <i class="bi bi-person-circle"></i>
                    <span>{{ session('auth_user.fullName', session('auth_user.username', 'User')) }}</span>
                    <span class="badge bg-{{ (session('auth_user')['role'] ?? '') === 'Admin' ? 'primary' : 'success' }}">
                        {{ session('auth_user')['role'] ?? '' }}
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.change-password') }}">
                            <i class="bi bi-shield-lock me-2"></i>Change Password
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div id="main-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const csRegistry = {};
class CustomSelect {
    constructor(wrapperId) {
        this.id = wrapperId;
        this.wrapper = document.getElementById(wrapperId);
        if (!this.wrapper) return;
        this.input = this.wrapper.querySelector('input[type="hidden"]');
        this.trigger = this.wrapper.querySelector('.cs-trigger');
        this.valueSpan = this.wrapper.querySelector('.cs-value');
        this.list = this.wrapper.querySelector('.cs-list');
        this.searchInput = this.wrapper.querySelector('.cs-search');
        csRegistry[wrapperId] = this;
        this._initSelected();
        this._bindEvents();
    }
    _initSelected() {
        const val = this.input ? this.input.value : '';
        if (val) {
            const item = Array.from(this.list.querySelectorAll('.cs-item')).find(i => i.dataset.value === val);
            if (item) { item.classList.add('selected'); this.valueSpan.textContent = item.textContent.trim(); this.valueSpan.classList.remove('cs-placeholder'); }
        }
        this._updateSearch();
    }
    _updateSearch() {
        const searchWrap = this.wrapper.querySelector('.cs-search-wrap');
        if (!searchWrap) return;
        const hasReal = this.list.querySelectorAll('.cs-item:not(.no-data)').length > 0;
        searchWrap.style.display = hasReal ? '' : 'none';
    }
    _bindEvents() {
        this.trigger.addEventListener('click', (e) => { e.stopPropagation(); this.wrapper.classList.contains('open') ? this._close() : this._open(); });
        if (this.searchInput) { this.searchInput.addEventListener('input', () => this._filter(this.searchInput.value)); }
        this.list.addEventListener('click', (e) => {
            const item = e.target.closest('.cs-item');
            if (!item || item.classList.contains('no-data')) return;
            this._select(item.dataset.value, item.textContent.trim());
        });
        document.addEventListener('click', (e) => { if (!this.wrapper.contains(e.target)) this._close(); });
    }
    _open() {
        Object.values(csRegistry).forEach(cs => { if (cs !== this) cs._close(); });
        this.wrapper.classList.add('open');
        if (this.searchInput) { this.searchInput.value = ''; this._filter(''); setTimeout(() => this.searchInput.focus(), 10); }
    }
    _close() { this.wrapper.classList.remove('open'); }
    _select(value, label) {
        if (this.input) this.input.value = value;
        this.valueSpan.textContent = label;
        this.valueSpan.classList.remove('cs-placeholder');
        this.list.querySelectorAll('.cs-item').forEach(i => i.classList.remove('selected'));
        const item = Array.from(this.list.querySelectorAll('.cs-item')).find(i => i.dataset.value === String(value));
        if (item) item.classList.add('selected');
        this._close();
        if (this.input) this.input.dispatchEvent(new Event('change', { bubbles: true }));
    }
    _filter(query) {
        const q = query.toLowerCase().trim();
        this.list.querySelectorAll('.cs-item:not(.no-data)').forEach(item => {
            item.style.display = (!q || item.textContent.toLowerCase().includes(q)) ? '' : 'none';
        });
    }
    addOption(value, label, select = true) {
        const noData = this.list.querySelector('.no-data');
        if (noData) noData.remove();
        const item = document.createElement('div');
        item.className = 'cs-item';
        item.dataset.value = String(value);
        item.textContent = label;
        this.list.appendChild(item);
        this._updateSearch();
        if (select) this._select(String(value), label);
    }
}
document.addEventListener('wheel', function() {
    if (document.activeElement.type === 'number') document.activeElement.blur();
}, { passive: true });
</script>
<!-- Delete Confirmation Modal (global) -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2 text-danger">
                    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                    <h5 class="modal-title mb-0">Confirm Delete</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <p id="deleteConfirmMessage" class="mb-0 text-muted">Are you sure you want to delete this item? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="deleteConfirmBtn">
                    <i class="bi bi-trash me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    let _pendingForm = null;
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    const confirmBtn = document.getElementById('deleteConfirmBtn');
    const messageEl = document.getElementById('deleteConfirmMessage');

    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form.classList.contains('delete-form')) return;
        e.preventDefault();
        _pendingForm = form;
        messageEl.textContent = form.dataset.confirmMessage || 'Are you sure you want to delete this item? This action cannot be undone.';
        modal.show();
    });

    confirmBtn.addEventListener('click', function() {
        modal.hide();
        if (_pendingForm) { _pendingForm.submit(); _pendingForm = null; }
    });
})();
</script>
@yield('scripts')
</body>
</html>
