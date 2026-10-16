<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleType;
use App\Enums\UserStatus;
use App\Http\Requests\ClientRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UserRequest extends FormRequest
{
    /**
     * Authorization is done in the controller with the UserPolicy.
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
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9._-]+$/', Rule::unique('users', 'username')->ignore($user?->id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'regex:'.ClientRequest::PHONE_PATTERN],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'status' => [$user === null ? 'nullable' : 'required', Rule::enum(UserStatus::class)],
            'password' => [$user === null ? 'required' : 'nullable', 'string', 'confirmed'],
            'company_name' => ['nullable', 'string', 'max:150'],
        ];
    }

    /**
     * Role/contractor consistency and protection against privilege escalation or self-lockout.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['role_id', 'status'])) {
                    return;
                }

                $role = Role::find($this->integer('role_id'));
                $actor = $this->user();
                /** @var User|null $editing */
                $editing = $this->route('user');

                if ($role->isSuperAdmin() && ! $actor->isSuperAdmin()) {
                    $validator->errors()->add('role_id', 'Only a Super Admin can assign the Super Admin role.');
                }

                if ($editing === null && $role->type === RoleType::Contractor && ! $this->filled('company_name')) {
                    $validator->errors()->add('company_name', 'Company name is required for contractor roles.');
                }

                if ($editing !== null && $editing->is($actor)) {
                    if ($editing->role_id !== $role->id) {
                        $validator->errors()->add('role_id', 'You cannot change your own role.');
                    }
                    if ($this->string('status')->value() !== UserStatus::Active->value) {
                        $validator->errors()->add('status', 'You cannot deactivate your own account.');
                    }
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
            'phone.regex' => 'Enter a valid phone number.',
            'role_id.required' => 'Select a role.',
            'username.regex' => 'Use only lowercase letters, numbers, dots, underscores or hyphens.',
            'username.unique' => 'This username is already taken.',
        ];
    }
}
