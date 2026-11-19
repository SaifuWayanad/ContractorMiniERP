<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LossDetail extends Model
{
    protected $fillable = [
        'loss_id',
        'source_dispatch_detail_id',
        'product_id',
        'quantity_lost',
        'recovery_amount',
        'recovery_tax_rate_id',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity_lost' => 'decimal:3',
            'recovery_amount' => 'decimal:2',
        ];
    }

    public function loss(): BelongsTo
    {
        return $this->belongsTo(Loss::class);
    }

    public function sourceDispatchDetail(): BelongsTo
    {
        return $this->belongsTo(DispatchDetail::class, 'source_dispatch_detail_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function recoveryTaxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'recovery_tax_rate_id');
    }
}
