<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\Contractor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 *
 * The built-in roles must be seeded (AccessControlSeeder) before using the role states.
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state. By default the user is the owner of a new contractor.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contractor_id' => Contractor::factory(),
            'role_id' => fn (): int => Role::where('slug', Role::CONTRACTOR_OWNER)->value('id'),
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '98'.fake()->numerify('########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => UserStatus::Inactive]);
    }

    /**
     * Owner of the given contractor (full contractor permissions).
     */
    public function ownerOf(Contractor $contractor): static
    {
        return $this->state(fn (): array => [
            'contractor_id' => $contractor->id,
            'role_id' => Role::where('slug', Role::CONTRACTOR_OWNER)->value('id'),
        ]);
    }

    /**
     * Staff member of the given contractor (cannot delete).
     */
    public function staffOf(Contractor $contractor): static
    {
        return $this->state(fn (): array => [
            'contractor_id' => $contractor->id,
            'role_id' => Role::where('slug', Role::CONTRACTOR_STAFF)->value('id'),
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (): array => [
            'contractor_id' => null,
            'role_id' => Role::where('slug', Role::SUPER_ADMIN)->value('id'),
        ]);
    }

    /**
     * Management user with the default (view-only) Admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (): array => [
            'contractor_id' => null,
            'role_id' => Role::where('slug', Role::ADMIN)->value('id'),
        ]);
    }

    public function withRole(Role $role): static
    {
        return $this->state(fn (): array => ['role_id' => $role->id]);
    }
}
