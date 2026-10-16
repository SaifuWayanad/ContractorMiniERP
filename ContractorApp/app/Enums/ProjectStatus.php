<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Planning = 'planning';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planning => 'Planning',
            self::Active => 'Active',
            self::OnHold => 'On Hold',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Planning => 'badge-soft-info',
            self::Active => 'badge-soft-success',
            self::OnHold => 'badge-soft-warning',
            self::Completed => 'badge-soft-primary',
            self::Cancelled => 'badge-soft-secondary',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }

    /**
     * Statuses that still count towards upcoming deadlines.
     *
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Planning, self::Active, self::OnHold];
    }
}
