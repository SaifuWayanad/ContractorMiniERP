{{-- Shared work form. Expects $work, $projects (id => name), $assignees (id => name). --}}
<div class="card form-card">
    <div class="card-body">
        <x-form.section title="Work Information" :first="true">
            <x-form.input name="title" label="Title" :value="$work->title" :required="true" maxlength="150" col="col-md-12 col-xl-8" autofocus />
            <x-form.select name="project_id" label="Project" :options="$projects" :value="$work->project_id" placeholder="Select project" :required="true" help="The client is taken from the project." />
            <x-form.textarea name="description" label="Description" :value="$work->description" rows="3" maxlength="5000" />
        </x-form.section>

        <x-form.section title="Schedule & Details">
            <x-form.select name="priority" label="Priority" :options="App\Enums\WorkPriority::options()" :value="$work->priority" :required="true" />
            <x-form.select name="status" label="Status" :options="App\Enums\WorkStatus::options()" :value="$work->status" :required="true" />
            <x-form.input name="start_date" label="Start Date" type="date" :value="$work->start_date?->format('Y-m-d')" />
            <x-form.input name="due_date" label="Due Date" type="date" :value="$work->due_date?->format('Y-m-d')" />
            <x-form.input name="amount" label="Amount (₹)" type="number" step="0.01" min="0" :value="$work->amount ?? ''" col="col-md-4" />
        </x-form.section>
    </div>
</div>
