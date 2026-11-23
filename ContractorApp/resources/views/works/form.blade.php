{{-- Shared work form. Expects $work, $projects, $assignees. --}}
@php
    $priorityOptions = App\Enums\WorkPriority::options();
    $statusOptions   = App\Enums\WorkStatus::options();
    $selectedPriority = old('priority', $work->priority instanceof \BackedEnum ? $work->priority->value : $work->priority);
    $selectedStatus   = old('status',   $work->status   instanceof \BackedEnum ? $work->status->value   : $work->status);
    $selectedProject  = old('project_id', $work->project_id);
@endphp

<div class="card form-card">
    <div class="card-body">
        <x-form.section title="Work Information" :first="true">
            <x-form.input name="title" label="Title" :value="$work->title" :required="true" maxlength="150" col="col-md-12 col-xl-8" autofocus />

            {{-- Project CS-dropdown --}}
            <div class="col-md-6 col-xl-4">
                <label class="form-label fw-semibold">Project <span class="req">*</span></label>
                <div class="cs-wrapper" id="workProjectWrapper">
                    <div class="cs-trigger">
                        <span class="cs-value {{ $selectedProject ? '' : 'cs-placeholder' }}">
                            {{ $selectedProject ? ($projects[$selectedProject] ?? 'Select project') : 'Select project' }}
                        </span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                        <div class="cs-list">
                            <div class="cs-item" data-value="">Select project</div>
                            @foreach($projects as $id => $name)
                                <div class="cs-item" data-value="{{ $id }}">{{ $name }}</div>
                            @endforeach
                        </div>
                    </div>
                    <input type="hidden" name="project_id" id="workProjectInput" value="{{ $selectedProject ?? '' }}">
                </div>
                @error('project_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                <div class="form-text">The client is taken from the project.</div>
            </div>

            <x-form.textarea name="description" label="Description" :value="$work->description" rows="3" maxlength="5000" />
        </x-form.section>

        <x-form.section title="Schedule & Details">
            {{-- Priority CS-dropdown --}}
            <div class="col-md-6 col-xl-4">
                <label class="form-label fw-semibold">Priority</label>
                <div class="cs-wrapper" id="workPriorityWrapper">
                    <div class="cs-trigger">
                        <span class="cs-value {{ $selectedPriority ? '' : 'cs-placeholder' }}">
                            {{ $selectedPriority ? ($priorityOptions[$selectedPriority] ?? 'Select priority') : 'Select priority' }}
                        </span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                        <div class="cs-list">
                            @foreach($priorityOptions as $val => $label)
                                <div class="cs-item" data-value="{{ $val }}">{{ $label }}</div>
                            @endforeach
                        </div>
                    </div>
                    <input type="hidden" name="priority" id="workPriorityInput" value="{{ $selectedPriority ?? '' }}">
                </div>
                @error('priority')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            {{-- Status CS-dropdown --}}
            <div class="col-md-6 col-xl-4">
                <label class="form-label fw-semibold">Status</label>
                <div class="cs-wrapper" id="workStatusWrapper">
                    <div class="cs-trigger">
                        <span class="cs-value {{ $selectedStatus ? '' : 'cs-placeholder' }}">
                            {{ $selectedStatus ? ($statusOptions[$selectedStatus] ?? 'Select status') : 'Select status' }}
                        </span>
                        <i class="bi bi-chevron-down cs-arrow"></i>
                    </div>
                    <div class="cs-panel">
                        <div class="cs-search-wrap"><i class="bi bi-search"></i><input type="text" class="cs-search" placeholder="Search…"></div>
                        <div class="cs-list">
                            @foreach($statusOptions as $val => $label)
                                <div class="cs-item" data-value="{{ $val }}">{{ $label }}</div>
                            @endforeach
                        </div>
                    </div>
                    <input type="hidden" name="status" id="workStatusInput" value="{{ $selectedStatus ?? '' }}">
                </div>
                @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <x-form.input name="start_date" label="Start Date" type="date" :value="$work->start_date?->format('Y-m-d')" />
            <x-form.input name="due_date" label="Due Date" type="date" :value="$work->due_date?->format('Y-m-d')" />
        </x-form.section>

        <x-form.section title="Assign Employees">
            <div class="col-12">
                <label class="form-label fw-semibold">Assigned Employees <span class="text-muted fw-normal small">(optional)</span></label>
                <div class="d-flex flex-wrap gap-2" id="empAssignList">
                    @foreach($allEmployees as $emp)
                    @php $checked = in_array($emp->id, $selectedEmployeeIds); @endphp
                    <div class="form-check form-check-inline border rounded px-3 py-2" style="min-width:140px">
                        <input class="form-check-input" type="checkbox" name="employee_ids[]" value="{{ $emp->id }}" id="empCheck_{{ $emp->id }}" {{ $checked ? 'checked' : '' }}>
                        <label class="form-check-label" for="empCheck_{{ $emp->id }}">{{ $emp->name }}</label>
                        @if($emp->designation)<div class="text-muted" style="font-size:0.75rem">{{ $emp->designation }}</div>@endif
                    </div>
                    @endforeach
                    @if($allEmployees->isEmpty())
                        <div class="text-muted small">No active employees. <a href="{{ route('employees.create') }}">Add one</a>.</div>
                    @endif
                </div>
            </div>
        </x-form.section>
    </div>
</div>

@push('scripts')
<script>
(function () {
    function bindCs(wrapperId, inputId) {
        var cs    = new CustomSelect(wrapperId);
        var input = document.getElementById(inputId);
        var orig  = cs._select.bind(cs);
        cs._select = function(v, l) { orig(v, l); if (input) input.value = v; };
    }

    bindCs('workProjectWrapper',  'workProjectInput');
    bindCs('workPriorityWrapper', 'workPriorityInput');
    bindCs('workStatusWrapper',   'workStatusInput');
})();
</script>
@endpush
