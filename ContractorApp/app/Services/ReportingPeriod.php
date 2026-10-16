<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * A date range chosen with the dashboard's period filter.
 */
final class ReportingPeriod
{
    public const OPTIONS = [
        'today' => 'Today',
        'week' => 'This Week',
        'month' => 'This Month',
        '3m' => 'Last 3 Months',
        '6m' => 'Last 6 Months',
        'year' => 'This Year',
        'custom' => 'Custom Range',
    ];

    private function __construct(
        public readonly string $key,
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $key = $request->string('period', 'month')->value();
        $today = CarbonImmutable::today();

        if ($key === 'custom') {
            $from = self::parseDate($request->string('from')->value());
            $to = self::parseDate($request->string('to')->value());

            if ($from !== null && $to !== null && $from->lte($to)) {
                return new self('custom', $from->startOfDay(), $to->endOfDay());
            }

            $key = 'month';
        }

        return match ($key) {
            'today' => new self($key, $today, $today->endOfDay()),
            'week' => new self($key, $today->startOfWeek(), $today->endOfDay()),
            '3m' => new self($key, $today->subMonthsNoOverflow(3)->addDay(), $today->endOfDay()),
            '6m' => new self($key, $today->subMonthsNoOverflow(6)->addDay(), $today->endOfDay()),
            'year' => new self($key, $today->startOfYear(), $today->endOfDay()),
            default => new self('month', $today->startOfMonth(), $today->endOfDay()),
        };
    }

    public function label(): string
    {
        return $this->key === 'custom'
            ? $this->start->format('d M Y').' – '.$this->end->format('d M Y')
            : self::OPTIONS[$this->key];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(): array
    {
        return [$this->start, $this->end];
    }

    private static function parseDate(string $value): ?CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d', $value)?->startOfDay() ?: null;
    }
}
