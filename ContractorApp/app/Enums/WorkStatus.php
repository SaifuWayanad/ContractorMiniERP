<?php

namespace App\Enums;

enum WorkStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case OnHold = 'on_hold';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::OnHold => 'On Hold',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'badge-soft-warning',
            self::InProgress => 'badge-soft-info',
            self::Completed => 'badge-soft-success',
            self::OnHold => 'badge-soft-secondary',
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
     * Statuses of work that still needs doing.
     *
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Pending, self::InProgress, self::OnHold];
    }
}
