@if ($activity->isEmpty())
    <div class="empty-inline">No activity recorded yet.</div>
@else
    <ul class="feed">
        @foreach ($activity as $entry)
            <li class="feed-item">
                <span class="feed-icon"><i class="bi {{ $entry->icon() }}"></i></span>
                <div class="min-w-0">
                    <div class="feed-text">{{ $entry->description }}</div>
                    <div class="feed-meta" title="{{ $entry->created_at->format('d M Y, h:i A') }}">
                        {{ $entry->created_at->diffForHumans() }}
                        @if (($showContractor ?? false) && $entry->contractor) · {{ $entry->contractor->company_name }} @endif
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
@endif
