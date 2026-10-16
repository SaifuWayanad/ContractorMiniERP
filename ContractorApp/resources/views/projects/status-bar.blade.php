{{--
    One-click status switcher for the project page. Expects $project.
    Users without edit permission see the current status only.
--}}
@php
    $canUpdate = auth()->user()->can('update', $project);
    $icons = ['planning' => 'bi-pencil-square', 'active' => 'bi-play-circle', 'on_hold' => 'bi-pause-circle', 'completed' => 'bi-check2-circle', 'cancelled' => 'bi-x-circle'];
    $confirm = [
        'completed' => 'Mark "'.$project->name.'" as completed?'.($project->actual_end_date ? '' : ' Today will be saved as the actual end date.'),
        'cancelled' => 'Cancel "'.$project->name.'"? You can change the status again later.',
    ];
@endphp

{{-- Mobile: compact status changer (hidden sm+) --}}
@if ($canUpdate)
<div class="d-sm-none mb-3">
    <div class="card">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h2 class="card-title mb-0">Project Status</h2>
                <x-status-badge :status="$project->status" />
            </div>
            <form method="post" action="{{ route('projects.status', $project) }}" id="mobileStatusForm">
                @csrf
                @method('PATCH')
                <div class="d-flex gap-2">
                    <select name="status" id="mobileStatusSelect" class="form-select form-select-sm">
                        @foreach (App\Enums\ProjectStatus::cases() as $status)
                            <option value="{{ $status->value }}"
                                @selected($status === $project->status)
                                data-confirm="{{ $confirm[$status->value] ?? '' }}"
                                data-confirm-title="{{ isset($confirm[$status->value]) ? 'Change status to '.$status->label().'?' : '' }}"
                                data-confirm-button="{{ isset($confirm[$status->value]) ? 'Yes, '.mb_strtolower($status->label()) : '' }}"
                                data-confirm-variant="{{ $status->value === 'cancelled' ? 'danger' : 'success' }}">
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm text-nowrap" data-loading-text="Updating…">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Desktop: pill-button bar (hidden xs) --}}
<div class="card status-bar-card mb-3 d-none d-sm-block">
    <div class="card-body">
        <div class="status-bar-head">
            <div>
                <h2 class="card-title mb-1">Project Status</h2>
                <p class="small text-muted mb-0">
                    @if ($canUpdate)
                        Currently <strong>{{ $project->status->label() }}</strong>. Click a status to update it.
                    @else
                        Currently <strong>{{ $project->status->label() }}</strong>.
                    @endif
                </p>
            </div>
        </div>

        <div class="status-bar" role="group" aria-label="Project status">
            @foreach (App\Enums\ProjectStatus::cases() as $status)
                @php($isCurrent = $status === $project->status)
                @if ($isCurrent || ! $canUpdate)
                    <span @class(['status-step', 'status-step-'.$status->value, 'is-current' => $isCurrent]) @if ($isCurrent) aria-current="true" @endif>
                        <i class="bi {{ $icons[$status->value] }}" aria-hidden="true"></i>{{ $status->label() }}
                        @if ($isCurrent) <span class="visually-hidden">(current)</span> @endif
                    </span>
                @else
                    <form method="post" action="{{ route('projects.status', $project) }}" class="status-step-form"
                        @isset($confirm[$status->value])
                            data-confirm="{{ $confirm[$status->value] }}" data-confirm-title="Change status to {{ $status->label() }}?" data-confirm-button="Yes, {{ mb_strtolower($status->label()) }}" data-confirm-variant="{{ $status->value === 'cancelled' ? 'danger' : 'success' }}"
                        @endisset>
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $status->value }}" />
                        <button type="submit" class="status-step status-step-{{ $status->value }}" data-loading-text="Updating…">
                            <i class="bi {{ $icons[$status->value] }}" aria-hidden="true"></i>{{ $status->label() }}
                        </button>
                    </form>
                @endif
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var form = document.getElementById('mobileStatusForm');
    var select = document.getElementById('mobileStatusSelect');
    if (!form || !select) return;

    function syncConfirm() {
        var opt = select.options[select.selectedIndex];
        var msg = opt.dataset.confirm;
        if (msg) {
            form.dataset.confirm = msg;
            form.dataset.confirmTitle = opt.dataset.confirmTitle;
            form.dataset.confirmButton = opt.dataset.confirmButton;
            form.dataset.confirmVariant = opt.dataset.confirmVariant || 'success';
        } else {
            delete form.dataset.confirm;
            delete form.dataset.confirmTitle;
            delete form.dataset.confirmButton;
            delete form.dataset.confirmVariant;
        }
    }

    select.addEventListener('change', syncConfirm);
    syncConfirm();
})();
</script>
@endpush
