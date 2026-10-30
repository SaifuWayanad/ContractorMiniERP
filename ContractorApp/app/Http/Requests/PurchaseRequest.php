<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequest extends FormRequest
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
            'project_id'        => ['required', 'integer', Rule::exists('projects', 'id')->where('contractor_id', $contractorId)->whereNull('deleted_at')],
            'product_id'        => ['nullable', 'integer', Rule::exists('products', 'id')->where('contractor_id', $contractorId)->whereNull('deleted_at')],
            'purchase_date'     => ['required', 'date'],
            'quantity'          => ['nullable', 'numeric', 'min:0'],
            'unit_amount'       => ['nullable', 'numeric', 'min:0'],
            'line_total'        => ['nullable', 'numeric', 'min:0'],
            'supplier'          => ['nullable', 'string', 'max:200'],
            'invoice_reference' => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:5000'],
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
            'product_id.exists'   => 'Select a product from the list.',
        ];
    }
}
