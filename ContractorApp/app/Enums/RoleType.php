<?php

namespace App\Enums;

enum RoleType: string
{
    case Admin = 'admin';
    case Contractor = 'contractor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin portal',
            self::Contractor => 'Contractor portal',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Admin => 'badge-soft-primary',
            self::Contractor => 'badge-soft-info',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }
}
