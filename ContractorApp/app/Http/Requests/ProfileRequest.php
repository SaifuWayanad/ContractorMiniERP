<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateProfile', $this->user()->contractor);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'contact_person' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'regex:'.ClientRequest::PHONE_PATTERN],
            'alternate_phone' => ['nullable', 'string', 'regex:'.ClientRequest::PHONE_PATTERN],
            'address' => ['nullable', 'string', 'max:250'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'country' => ['required', 'string', 'max:80'],
            'postal_code' => ['nullable', 'string', 'max:12'],
            'website' => ['nullable', 'url:http,https', 'max:200'],
            'tax_number' => ['nullable', 'string', 'max:30'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024', 'dimensions:max_width=2000,max_height=2000'],
            'remove_logo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid phone number.',
            'alternate_phone.regex' => 'Enter a valid phone number.',
            'logo.max' => 'The logo must be 1 MB or smaller.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'company_name' => 'company name',
            'tax_number' => 'tax / GST number',
        ];
    }
}
