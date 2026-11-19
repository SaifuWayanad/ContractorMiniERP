<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceDetail extends Model
{
    protected $fillable = [
        'invoice_id',
        'return_detail_id',
        'rental_period_id',
        'dispatch_detail_id',
        'product_id',
        'description_snapshot',
        'quantity',
        'rental_days',
        'minimum_days_snapshot',
        'minimum_amount_snapshot',
        'addon_per_day_snapshot',
        'unit_amount',
        'line_amount',
        'discount_amount',
        'tax_rate_snapshot',
        'tax_amount',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'minimum_days_snapshot' => 'decimal:2',
            'minimum_amount_snapshot' => 'decimal:2',
            'addon_per_day_snapshot' => 'decimal:2',
            'unit_amount' => 'decimal:2',
            'line_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate_snapshot' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function returnDetail(): BelongsTo
    {
        return $this->belongsTo(ReturnDetail::class);
    }

    public function rentalPeriod(): BelongsTo
    {
        return $this->belongsTo(RentalPeriod::class);
    }

    public function dispatchDetail(): BelongsTo
    {
        return $this->belongsTo(DispatchDetail::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function invoiceAllocations(): HasMany
    {
        return $this->hasMany(InvoiceAllocation::class);
    }
}
