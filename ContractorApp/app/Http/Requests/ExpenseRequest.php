<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
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

        return [
            'project_id'       => ['required', 'integer', Rule::exists('projects', 'id')->where('contractor_id', $contractorId)->whereNull('deleted_at')],
            'heading'          => ['required', 'string', 'max:200'],
            'category'         => ['nullable', 'string', 'max:200'],
            'expense_date'     => ['required', 'date'],
            'amount'           => ['required', 'numeric', 'min:0'],
            'payment_method'   => ['nullable', 'string', 'max:100'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'project_id.required' => 'Select a project.',
            'project_id.exists'   => 'Select a project from the list.',
        ];
    }
}
