<?php

namespace App\Http\Requests;

use App\Enums\EmployeeStatus;
use App\Enums\SalaryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'designation'   => ['nullable', 'string', 'max:150'],
            'joining_date'  => ['nullable', 'date'],
            'salary_type'   => ['required', Rule::enum(SalaryType::class)],
            'salary_amount' => ['required', 'numeric', 'min:0'],
            'status'        => ['required', Rule::enum(EmployeeStatus::class)],
            'notes'         => ['nullable', 'string', 'max:5000'],
        ];
    }
}
