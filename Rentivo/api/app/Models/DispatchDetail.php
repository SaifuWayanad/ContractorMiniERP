<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DispatchDetail extends Model
{
    protected $fillable = [
        'dispatch_id',
        'product_id',
        'quantity',
        'minimum_rental_days_snapshot',
        'minimum_rental_amount_snapshot',
        'addon_amount_per_day_snapshot',
        'unit_of_measure_snapshot',
        'tax_rate_snapshot',
        'expected_return_date',
        'rate_override_reason',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'minimum_rental_days_snapshot' => 'decimal:2',
            'minimum_rental_amount_snapshot' => 'decimal:2',
            'addon_amount_per_day_snapshot' => 'decimal:2',
            'tax_rate_snapshot' => 'decimal:4',
            'expected_return_date' => 'date',
        ];
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function returnDetails(): HasMany
    {
        return $this->hasMany(ReturnDetail::class);
    }

    public function rentalPeriods(): HasMany
    {
        return $this->hasMany(RentalPeriod::class, 'source_dispatch_detail_id');
    }

    public function transferDetails(): HasMany
    {
        return $this->hasMany(TransferDetail::class, 'source_dispatch_detail_id');
    }

    public function lossDetails(): HasMany
    {
        return $this->hasMany(LossDetail::class, 'source_dispatch_detail_id');
    }
}
