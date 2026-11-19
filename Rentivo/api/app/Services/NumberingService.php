<?php

namespace App\Services;

use App\Models\NumberingSequence;
use Illuminate\Support\Facades\DB;

class NumberingService
{
    public function next(string $module): string
    {
        return DB::transaction(function () use ($module) {
            $sequence = NumberingSequence::where('module', $module)
                ->lockForUpdate()
                ->firstOrCreate(
                    ['module' => $module],
                    ['prefix' => strtoupper(substr($module, 0, 3)), 'next_number' => 1, 'padding_length' => 6]
                );

            $number = str_pad((string) $sequence->next_number, $sequence->padding_length, '0', STR_PAD_LEFT);
            $formatted = $sequence->prefix . $number . $sequence->suffix;

            $sequence->increment('next_number');

            return $formatted;
        });
    }
}
