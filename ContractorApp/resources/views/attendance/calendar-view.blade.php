@extends('layouts.app')

@section('title', 'Attendance Calendar')

@section('content')
<x-page-header title="Attendance Calendar" subtitle="Monthly overview of attendance records.">
    <x-slot:actions>
        @unless ($isEmployeePortal)
            <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-list-ul me-1"></i>Daily List
            </a>
            @can('create', App\Models\Attendance::class)
                <a href="{{ route('attendance.mark', ['date' => today()->format('Y-m-d')]) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-calendar-check me-1"></i>Mark Attendance
                </a>
            @endcan
        @endunless
    </x-slot:actions>
</x-page-header>

<x-card>
    {{-- Calendar header: month nav + legend --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <button type="button" class="btn btn-sm btn-light border" id="attCalPrev" style="width:36px;height:36px;padding:0;border-radius:8px">
                <i class="bi bi-chevron-left"></i>
            </button>
            <h5 class="mb-0 fw-bold" id="attCalTitle" style="min-width:200px;text-align:center;letter-spacing:-.3px;font-size:1.15rem">Loading…</h5>
            <button type="button" class="btn btn-sm btn-light border" id="attCalNext" style="width:36px;height:36px;padding:0;border-radius:8px">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="att-legend-pill" style="background:#d1fae5;color:#065f46"><span style="background:#10b981;width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:5px"></span>Present</span>
            <span class="att-legend-pill" style="background:#fef3c7;color:#92400e"><span style="background:#f59e0b;width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:5px"></span>Half Day</span>
            <span class="att-legend-pill" style="background:#ede9fe;color:#4c1d95"><span style="background:#7c3aed;width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:5px"></span>Leave</span>
        </div>
    </div>

    <div id="attCalGrid" class="att-cal-grid"></div>
</x-card>

<style>
.att-legend-pill { display:inline-flex; align-items:center; font-size:.75rem; font-weight:600; padding:3px 10px; border-radius:20px; }

.att-cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:6px; }

.att-cal-hdr {
    text-align:center; font-size:.7rem; font-weight:700;
    color:#94a3b8; padding:6px 0 10px; text-transform:uppercase; letter-spacing:.06em;
}

.att-cal-cell {
    border:1.5px solid #f1f5f9; border-radius:10px; padding:8px 10px;
    min-height:84px; cursor:pointer;
    transition:border-color .15s, box-shadow .15s, background .15s;
    position:relative; background:#fafafa;
}
.att-cal-cell:hover { border-color:#cbd5e1; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,.07); }

.att-cal-today {
    border-color:#3b82f6 !important; background:#eff6ff !important;
    box-shadow:0 0 0 3px rgba(59,130,246,.12) !important;
}
.att-cal-today .att-cal-num { color:#2563eb; }

.att-cal-has-data { background:#fff; border-color:#e2e8f0; }

.att-cal-empty { border-color:transparent !important; cursor:default; background:transparent !important; min-height:0 !important; }

.att-cal-num {
    display:block; text-align:right; font-size:.8rem;
    font-weight:700; color:#374151; margin-bottom:6px;
}

.att-cal-counts { display:flex; flex-direction:column; gap:3px; }

.att-cnt {
    display:inline-flex; align-items:center; gap:4px;
    font-size:.7rem; font-weight:600; line-height:1; padding:2px 6px;
    border-radius:20px; width:fit-content;
}
.att-cnt-p { background:#d1fae5; color:#065f46; }
.att-cnt-h { background:#fef3c7; color:#92400e; }
.att-cnt-l { background:#ede9fe; color:#4c1d95; }
</style>

@push('scripts')
<script>
(function () {
    var calGrid        = document.getElementById('attCalGrid');
    var calTitle       = document.getElementById('attCalTitle');
    var csrfToken      = (document.querySelector('meta[name=csrf-token]') || {}).content || '';
    var indexUrl       = '{{ route('attendance.index', [], false) }}';
    var calDataUrl     = '{{ route('attendance.calendar', [], false) }}';
    var isEmployeeMode = {{ $isEmployeePortal ? 'true' : 'false' }};
    var portalEmpId    = {{ $linkedEmployeeId ?? 'null' }};
    var calData        = {};
    var today          = new Date();
    var curYear        = today.getFullYear();
    var curMonth       = today.getMonth() + 1;

    var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var DAYS   = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    function loadMonth(year, month) {
        var monthStr = year + '-' + pad(month);
        var url = calDataUrl + '?month=' + monthStr;
        if (isEmployeeMode && portalEmpId) url += '&employee_id=' + portalEmpId;
        calTitle.textContent = 'Loading…';
        fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } })
            .then(function (r) { return r.json(); })
            .then(function (data) { calData = data; renderCalendar(year, month); })
            .catch(function () { calTitle.textContent = 'Error loading data'; });
    }

    function renderCalendar(year, month) {
        calTitle.textContent = MONTHS[month - 1] + ' ' + year;

        var firstDow    = new Date(year, month - 1, 1).getDay();
        firstDow        = firstDow === 0 ? 6 : firstDow - 1;
        var daysInMonth = new Date(year, month, 0).getDate();
        var todayStr    = today.getFullYear() + '-' + pad(today.getMonth() + 1) + '-' + pad(today.getDate());

        var html = '';
        DAYS.forEach(function (d) { html += '<div class="att-cal-hdr">' + d + '</div>'; });
        for (var i = 0; i < firstDow; i++) {
            html += '<div class="att-cal-cell att-cal-empty"></div>';
        }

        for (var d = 1; d <= daysInMonth; d++) {
            var dateStr = year + '-' + pad(month) + '-' + pad(d);
            var isToday = dateStr === todayStr;
            var info    = calData[dateStr];
            var hasData = info && (info.p || info.h || info.l);
            var counts  = '';

            if (isEmployeeMode) {
                if (info && info.p) {
                    counts = '<span class="att-cnt att-cnt-p"><span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#10b981;margin-right:2px"></span>Present</span>';
                } else if (info && info.h) {
                    counts = '<span class="att-cnt att-cnt-h"><span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#f59e0b;margin-right:2px"></span>Half Day</span>';
                } else if (info && info.l) {
                    counts = '<span class="att-cnt att-cnt-l"><span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#7c3aed;margin-right:2px"></span>Leave</span>';
                }
            } else {
                if (info) {
                    if (info.p) counts += '<span class="att-cnt att-cnt-p"><span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#10b981;margin-right:2px"></span>' + info.p + ' Present</span>';
                    if (info.h) counts += '<span class="att-cnt att-cnt-h"><span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#f59e0b;margin-right:2px"></span>' + info.h + ' Half Day</span>';
                    if (info.l) counts += '<span class="att-cnt att-cnt-l"><span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#7c3aed;margin-right:2px"></span>' + info.l + ' Leave</span>';
                }
            }

            var cls = 'att-cal-cell' + (isToday ? ' att-cal-today' : '') + (hasData && !isToday ? ' att-cal-has-data' : '');
            html +=
                '<div class="' + cls + '" data-date="' + dateStr + '">' +
                    '<span class="att-cal-num">' + d + '</span>' +
                    '<div class="att-cal-counts">' + counts + '</div>' +
                '</div>';
        }

        calGrid.innerHTML = html;

        calGrid.querySelectorAll('.att-cal-cell[data-date]').forEach(function (cell) {
            cell.addEventListener('click', function () {
                var url = indexUrl + '?date=' + this.dataset.date;
                if (isEmployeeMode && portalEmpId) url += '&employee_id=' + portalEmpId;
                window.location.href = url;
            });
        });
    }

    document.getElementById('attCalPrev').addEventListener('click', function () {
        curMonth--;
        if (curMonth < 1) { curMonth = 12; curYear--; }
        loadMonth(curYear, curMonth);
    });
    document.getElementById('attCalNext').addEventListener('click', function () {
        curMonth++;
        if (curMonth > 12) { curMonth = 1; curYear++; }
        loadMonth(curYear, curMonth);
    });

    loadMonth(curYear, curMonth);
})();
</script>
@endpush
@endsection
