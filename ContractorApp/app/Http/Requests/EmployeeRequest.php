<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:200'],
            'employee_code' => ['nullable', 'string', 'max:50'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'email'         => ['nullable', 'email', 'max:200'],
            'designation'   => ['nullable', 'string', 'max:150'],
            'joining_date'  => ['nullable', 'date'],
            'salary_type'   => ['nullable', 'string', 'max:100'],
            'salary_amount' => ['nullable', 'numeric', 'min:0'],
            'notes'         => ['nullable', 'string', 'max:5000'],
        ];
    }
}
