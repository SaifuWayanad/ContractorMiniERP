<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NumberingSequence extends Model
{
    protected $fillable = [
        'module',
        'prefix',
        'next_number',
        'padding_length',
        'suffix',
    ];

    public static function nextNumber(string $module): string
    {
        return DB::transaction(function () use ($module) {
            $sequence = static::where('module', $module)->lockForUpdate()->first();

            if (!$sequence) {
                $sequence = static::create([
                    'module' => $module,
                    'prefix' => strtoupper(substr($module, 0, 3)) . '-',
                    'next_number' => 1,
                    'padding_length' => 6,
                    'suffix' => '',
                ]);
            }

            $number = str_pad((string) $sequence->next_number, $sequence->padding_length, '0', STR_PAD_LEFT);
            $formatted = $sequence->prefix . $number . $sequence->suffix;

            $sequence->increment('next_number');

            return $formatted;
        });
    }
}
