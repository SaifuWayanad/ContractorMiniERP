<?php

namespace App\Http\Requests;

use App\Enums\UserStatus;
use App\Enums\WorkPriority;
use App\Enums\WorkStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkRequest extends FormRequest
{
    /**
     * Authorization is done in the controller with the WorkPolicy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The project and assignee must belong to the signed-in contractor; anything else is rejected
     * as if it did not exist. The client is not accepted from input (it is copied from the project).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $contractorId = $this->user()->contractor_id;

        return [
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')->where('contractor_id', $contractorId)->whereNull('deleted_at')],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where('contractor_id', $contractorId)->where('status', UserStatus::Active->value)->whereNull('deleted_at')],
            'priority' => ['required', Rule::enum(WorkPriority::class)],
            'status' => ['required', Rule::enum(WorkStatus::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'project_id.required' => 'Select a project.',
            'project_id.exists' => 'Select a project from the list.',
            'assigned_to.exists' => 'Select a team member from the list.',
            'due_date.after_or_equal' => 'The due date must be on or after the start date.',
        ];
    }
}
