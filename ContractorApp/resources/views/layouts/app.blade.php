<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title') - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}" />
</head>
<body>
    <div class="app-shell">
        <x-sidebar />

        <div class="main-area">
            <x-navbar />

            <main role="main" class="content">
                @yield('content')
            </main>
        </div>
    </div>

    <x-flash />
    <x-confirm-modal />

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}"></script>

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

    @stack('scripts')
    <script>
    // Auto-filter: native <select> submits immediately on change;
    // text inputs submit after 500 ms when value is empty or ≥ 3 chars.
    (function () {
        var timer;
        document.querySelectorAll('[data-auto-filter]').forEach(function (form) {
            form.querySelectorAll('select').forEach(function (sel) {
                sel.addEventListener('change', function () { form.submit(); });
            });
            form.querySelectorAll('input[type="text"], input[type="search"]').forEach(function (inp) {
                inp.addEventListener('input', function () {
                    clearTimeout(timer);
                    var v = inp.value.trim();
                    if (v.length === 0 || v.length >= 3) {
                        timer = setTimeout(function () { form.submit(); }, 500);
                    }
                });
            });
        });
    })();
    </script>
</body>
</html>
