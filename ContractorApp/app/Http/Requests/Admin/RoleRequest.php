<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleType;
use App\Models\Role;
use App\Services\PermissionCatalogue;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    /**
     * Authorization is done in the controller with the RolePolicy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The type can only be chosen when creating a role; permissions must be valid for that type,
     * so a contractor role can never be given admin-portal permissions.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(PermissionCatalogue $catalogue): array
    {
        /** @var Role|null $role */
        $role = $this->route('role');
        $type = $role?->type ?? RoleType::tryFrom($this->string('type')->value());

        return [
            'name' => ['required', 'string', 'max:60', Rule::unique('roles', 'name')->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:250'],
            'type' => [$role === null ? 'required' : 'prohibited', Rule::enum(RoleType::class)],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in($type !== null ? $catalogue->forRoleType($type) : [])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'permissions.*.in' => 'One of the selected permissions is not available for this type of role.',
            'type.prohibited' => 'A role\'s portal cannot be changed after it is created.',
        ];
    }
}
