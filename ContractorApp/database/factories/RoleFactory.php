<?php

namespace Database\Factories;

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'type' => RoleType::Contractor,
            'description' => null,
            'is_system' => false,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (): array => ['type' => RoleType::Admin]);
    }

    /**
     * Grant the given permission names to the role after it is created.
     *
     * @param  list<string>  $permissions
     */
    public function withPermissions(array $permissions): static
    {
        return $this->afterCreating(function (Role $role) use ($permissions): void {
            $role->permissions()->sync(Permission::whereIn('name', $permissions)->pluck('id'));
        });
    }
}
