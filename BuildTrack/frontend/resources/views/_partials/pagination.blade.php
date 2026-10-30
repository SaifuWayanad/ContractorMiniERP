@php
    $currentPage = $pagination['currentPage'] ?? $pagination['page'] ?? $pagination['current_page'] ?? 1;
    $totalPages  = $pagination['totalPages']  ?? $pagination['total_pages']  ?? $pagination['lastPage'] ?? $pagination['last_page'] ?? 1;
    $pageSize    = $pagination['pageSize']    ?? $pagination['page_size']    ?? $pagination['perPage']  ?? $pagination['per_page']  ?? 20;
    $total       = $pagination['total']       ?? $pagination['totalItems']   ?? null;

    $rangeStart  = $total !== null ? (($currentPage - 1) * $pageSize) + 1 : null;
    $rangeEnd    = $total !== null ? min($currentPage * $pageSize, $total)  : null;
    $localCount  = $localCount ?? null;
@endphp

<div class="d-flex justify-content-between align-items-center px-4 py-2 border-top" style="background:#fafafa;">
    <small class="text-muted" style="font-size:0.72rem;">
        @if($total !== null && $rangeEnd !== null)
            @if($totalPages <= 1)
                Showing {{ $total }} {{ $total == 1 ? 'record' : 'records' }}
            @else
                Showing {{ $rangeStart }}–{{ $rangeEnd }} of {{ $total }}
            @endif
        @elseif($localCount !== null)
            {{ $localCount }} {{ $localCount == 1 ? 'record' : 'records' }}
        @endif
    </small>

    @if($totalPages > 1)
    <nav>
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item {{ $currentPage <= 1 ? 'disabled' : '' }}">
                <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $currentPage - 1]) }}">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            @for($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++)
                <li class="page-item {{ $p == $currentPage ? 'active' : '' }}">
                    <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $p]) }}">{{ $p }}</a>
                </li>
            @endfor
            <li class="page-item {{ $currentPage >= $totalPages ? 'disabled' : '' }}">
                <a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $currentPage + 1]) }}">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    @endif
</div>
