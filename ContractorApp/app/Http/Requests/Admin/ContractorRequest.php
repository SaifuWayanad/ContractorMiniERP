<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContractorStatus;
use App\Http\Requests\ClientRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ContractorRequest extends FormRequest
{
    /**
     * Authorization is done in the controller with the ContractorPolicy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * On create, an initial owner login can optionally be created at the same time.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $creating = $this->route('contractor') === null;
        $withUser = $creating && $this->boolean('create_user');

        return [
            'company_name' => ['required', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'regex:'.ClientRequest::PHONE_PATTERN],
            'alternate_phone' => ['nullable', 'string', 'regex:'.ClientRequest::PHONE_PATTERN],
            'address' => ['nullable', 'string', 'max:250'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'postal_code' => ['nullable', 'string', 'max:12'],
            'website' => ['nullable', 'url:http,https', 'max:200'],
            'tax_number' => ['nullable', 'string', 'max:30'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'status' => [$creating ? 'nullable' : 'required', Rule::enum(ContractorStatus::class)],
            'create_user' => ['boolean'],
            'user_name' => [Rule::requiredIf($withUser), 'nullable', 'string', 'max:100'],
            'user_password' => [Rule::requiredIf($withUser), 'nullable', 'confirmed', Password::defaults()],
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'company_name' => 'company name',
            'contact_person' => 'contact person',
            'user_name' => 'user name',
            'user_password' => 'password',
        ];
    }
}
