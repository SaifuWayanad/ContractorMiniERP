<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferDetail extends Model
{
    protected $fillable = [
        'transfer_id',
        'source_dispatch_detail_id',
        'product_id',
        'quantity_transferred',
        'minimum_rental_days_snapshot',
        'minimum_rental_amount_snapshot',
        'addon_amount_per_day_snapshot',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity_transferred' => 'decimal:3',
            'minimum_rental_days_snapshot' => 'decimal:2',
            'minimum_rental_amount_snapshot' => 'decimal:2',
            'addon_amount_per_day_snapshot' => 'decimal:2',
        ];
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    public function sourceDispatchDetail(): BelongsTo
    {
        return $this->belongsTo(DispatchDetail::class, 'source_dispatch_detail_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
