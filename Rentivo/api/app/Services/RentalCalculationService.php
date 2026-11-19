<?php

namespace App\Services;

use App\Models\CompanyProfile;
use App\Models\RentalPeriod;
use Carbon\Carbon;

class RentalCalculationService
{
    public function calculateDays(string $startDate, string $endDate): int
    {
        $config = CompanyProfile::first();
        $convention = $config?->day_count_convention ?? 'INCLUSIVE';

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        return match ($convention) {
            'EXCLUSIVE_START' => (int) $start->diffInDays($end),
            default => (int) $start->diffInDays($end) + 1, // INCLUSIVE
        };
    }

    public function calculateUnitAmount(float $days, float $minimumDays, float $minimumAmount, float $addonPerDay): float
    {
        if ($days <= $minimumDays) {
            return $minimumAmount;
        }

        return round($minimumAmount + ($days - $minimumDays) * $addonPerDay, 2);
    }

    public function calculateRentalPeriod(RentalPeriod $period): array
    {
        if (!$period->rental_end_date) {
            return ['days' => null, 'unit_amount' => null, 'line_amount' => null];
        }

        $days = $this->calculateDays($period->rental_start_date, $period->rental_end_date);

        $unitAmount = $this->calculateUnitAmount(
            $days,
            (float) $period->minimum_rental_days_snapshot,
            (float) $period->minimum_rental_amount_snapshot,
            (float) $period->addon_amount_per_day_snapshot,
        );

        $lineAmount = round($period->quantity * $unitAmount, 2);

        $taxRate = (float) ($period->tax_rate_snapshot ?? 0);
        $taxAmount = round($lineAmount * $taxRate / 100, 2);

        return [
            'days' => $days,
            'unit_amount' => $unitAmount,
            'line_amount' => $lineAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $lineAmount + $taxAmount,
        ];
    }
}
