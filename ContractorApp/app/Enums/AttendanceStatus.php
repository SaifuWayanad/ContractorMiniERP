<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case HalfDay = 'half_day';
    case Leave = 'leave';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Absent => 'Absent',
            self::HalfDay => 'Half Day',
            self::Leave => 'Leave',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Present => 'badge-soft-success',
            self::Absent => 'badge-soft-danger',
            self::HalfDay => 'badge-soft-warning',
            self::Leave => 'badge-soft-info',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c): array => [$c->value => $c->label()])->all();
    }
}
