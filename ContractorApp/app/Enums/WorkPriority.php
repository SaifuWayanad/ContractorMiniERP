<?php

namespace App\Enums;

enum WorkPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Urgent => 'Urgent',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Low => 'badge-soft-secondary',
            self::Medium => 'badge-soft-info',
            self::High => 'badge-soft-warning',
            self::Urgent => 'badge-soft-danger',
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
