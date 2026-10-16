<?php

namespace App\Http\Requests;

use App\Enums\ClientStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    /**
     * Phone numbers: optional leading +, then 7–19 digits, spaces or dashes.
     */
    public const PHONE_PATTERN = '/^\+?[0-9][0-9\s\-]{6,18}$/';

    /**
     * Authorization is done in the controller with the ClientPolicy.
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
            'company' => ['nullable', 'string', 'max:150'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'regex:'.self::PHONE_PATTERN],
            'address' => ['nullable', 'string', 'max:250'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:80'],
            'postal_code' => ['nullable', 'string', 'max:12'],
            'status' => ['nullable', Rule::enum(ClientStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
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

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'client name',
            'contact_person' => 'contact person',
            'postal_code' => 'postal code',
        ];
    }
}
