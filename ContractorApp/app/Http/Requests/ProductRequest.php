<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
        return [
            'name'         => ['required', 'string', 'max:150'],
            'unit'         => ['nullable', 'string', 'max:50'],
            'default_rate' => ['nullable', 'numeric', 'min:0'],
            'description'  => ['nullable', 'string', 'max:5000'],
        ];
    }
}
