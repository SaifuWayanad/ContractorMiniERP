<?php

namespace App\Http\Resources;

/**
 * Decimal columns come back from the database as strings. The .NET API sent them as JSON numbers,
 * so the frontend expects the same here.
 */
final class Money
{
    public static function of(null|int|float|string $value): ?float
    {
        return $value === null ? null : round((float) $value, 4);
    }
}
