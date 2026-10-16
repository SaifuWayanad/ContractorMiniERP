{{-- "Change status" dropdown for a work item. Expects $work. --}}
<div class="dropdown d-inline-block">
    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Change status</button>
    <ul class="dropdown-menu dropdown-menu-end">
        @foreach (App\Enums\WorkStatus::cases() as $status)
            @continue($status === $work->status)
            <li>
                <form method="post" action="{{ route('works.status', $work) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $status->value }}" />
                    <button type="submit" class="dropdown-item">{{ $status->label() }}</button>
                </form>
            </li>
        @endforeach
    </ul>
</div>
