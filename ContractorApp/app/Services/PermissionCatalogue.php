<?php

namespace App\Services;

use App\Enums\RoleType;
use Illuminate\Container\Attributes\Singleton;

/**
 * Reads the permission catalogue in config/permissions.php.
 */
#[Singleton]
class PermissionCatalogue
{
    /**
     * @return array<string, array{label: string, abilities: list<string>, portals: list<string>}>
     */
    public function groups(): array
    {
        return config('permissions.groups');
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return collect($this->groups())
            ->flatMap(fn (array $group, string $key): array => array_map(fn (string $ability): string => "{$key}.{$ability}", $group['abilities']))
            ->values()
            ->all();
    }

    /**
     * Permissions that roles of the given type may hold.
     *
     * @return list<string>
     */
    public function forRoleType(RoleType $type): array
    {
        return collect($this->groups())
            ->filter(fn (array $group): bool => in_array($type->value, $group['portals'], true))
            ->flatMap(fn (array $group, string $key): array => array_map(fn (string $ability): string => "{$key}.{$ability}", $group['abilities']))
            ->values()
            ->all();
    }

    public function exists(string $permission): bool
    {
        return in_array($permission, $this->all(), true);
    }
}
