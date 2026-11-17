<?php

namespace App\Enums;

enum SalaryPaymentStatus: string
{
    case Paid = 'paid';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Paid => 'Paid',
            self::Pending => 'Pending',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Paid => 'badge-soft-success',
            self::Pending => 'badge-soft-warning',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c): array => [$c->value => $c->label()])->all();
    }
}
