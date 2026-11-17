<?php

namespace App\Http\Requests;

use App\Enums\SalaryPaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalaryPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'employee_id'    => ['required', 'integer', Rule::exists('employees', 'id')->whereNull('deleted_at')],
            'period_month'   => ['required', 'integer', 'min:1', 'max:12'],
            'period_year'    => ['required', 'integer', 'min:2000', 'max:2100'],
            'amount'         => ['required', 'numeric', 'min:0'],
            'payment_date'   => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'status'         => ['required', Rule::enum(SalaryPaymentStatus::class)],
            'notes'          => ['nullable', 'string', 'max:5000'],
        ];
    }
}
