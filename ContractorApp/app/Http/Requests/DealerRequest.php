<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DealerRequest extends FormRequest
{
    /**
     * Authorization is done in the controller with the DealerPolicy.
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
        return [
            'name' => ['required', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'regex:'.ClientRequest::PHONE_PATTERN],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid phone number.',
        ];
    }
}
