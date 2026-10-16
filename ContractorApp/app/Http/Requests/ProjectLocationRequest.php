<?php

namespace App\Http\Requests;

use App\Services\LocationService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProjectLocationRequest extends FormRequest
{
    /**
     * Authorization is done in the controller with the ProjectLocationPolicy. The project comes from
     * the route (resolved within the signed-in contractor), never from the form.
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
            'address' => ['required', 'string', 'max:250'],
            'city' => ['required', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'state' => ['required', 'string', 'max:80'],
            'country' => ['required', 'string', 'max:80'],
            'postal_code' => ['nullable', 'string', 'max:12'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'regex:'.ClientRequest::PHONE_PATTERN],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * For locations in India: the state must be a real state, the district must belong to it,
     * and the postal code must be a 6-digit PIN.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(LocationService $locations): array
    {
        return [
            function (Validator $validator) use ($locations): void {
                if (strcasecmp(trim($this->string('country')->value()), 'India') !== 0 || $validator->errors()->has('state')) {
                    return;
                }

                $state = $this->string('state')->value();
                if (! in_array($state, $locations->states(), true)) {
                    $validator->errors()->add('state', 'Select a state from the list.');

                    return;
                }

                $district = $this->string('district')->value();
                if ($district !== '' && ! $locations->isValidDistrict($state, $district)) {
                    $validator->errors()->add('district', 'Select a district in the chosen state.');
                }

                $postalCode = $this->string('postal_code')->value();
                if ($postalCode !== '' && preg_match('/^[1-9]\d{5}$/', $postalCode) !== 1) {
                    $validator->errors()->add('postal_code', 'Enter a valid 6-digit PIN code.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contact_phone.regex' => 'Enter a valid phone number.',
            'latitude.required_with' => 'Enter both latitude and longitude, or neither.',
            'longitude.required_with' => 'Enter both latitude and longitude, or neither.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'location name',
            'postal_code' => 'postal code',
            'contact_person' => 'contact person',
            'contact_phone' => 'contact phone',
        ];
    }
}
