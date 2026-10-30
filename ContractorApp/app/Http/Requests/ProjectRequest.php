<?php

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    /**
     * Authorization is done in the controller with the ProjectPolicy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $contractorId = $this->user()->contractor_id;
        /** @var Project|null $project */
        $project = $this->route('project');

        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where('contractor_id', $contractorId)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:150'],
            'project_number' => [
                'nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9\-\/_.]+$/',
                Rule::unique('projects', 'project_number')->where('contractor_id', $contractorId)->whereNull('deleted_at')->ignore($project?->id),
            ],
            'type_of_work' => ['nullable', 'string', 'max:80'],
            'location' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['nullable', 'date'],
            'expected_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'actual_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', Rule::enum(ProjectStatus::class)],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'brand_preferred' => ['nullable', 'string', 'max:80'],
            'expected_qty' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'specification_details' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'next_meeting_at' => ['nullable', 'date'],
            'next_meeting_notes' => ['nullable', 'string', 'max:250'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Select a client.',
            'client_id.exists' => 'Select a client from the list.',
            'project_number.unique' => 'Another project already uses this number.',
            'project_number.regex' => 'Use only letters, numbers, dashes, slashes, dots or underscores.',
            'expected_end_date.after_or_equal' => 'The expected end date must be on or after the start date.',
            'actual_end_date.after_or_equal' => 'The actual end date must be on or after the start date.',
            'expected_qty.min' => 'Enter a quantity of 0 or more.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'project name',
            'project_number' => 'project number',
            'type_of_work' => 'type of work',
            'expected_end_date' => 'expected end date',
            'actual_end_date' => 'actual end date',
            'brand_preferred' => 'brand preferred',
            'expected_qty' => 'expected qty',
            'specification_details' => 'details for specification',
        ];
    }
}
