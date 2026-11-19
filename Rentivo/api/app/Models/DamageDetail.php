<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DamageDetail extends Model
{
    protected $fillable = [
        'damage_id',
        'source_dispatch_detail_id',
        'product_id',
        'quantity',
        'damage_description',
        'estimated_repair_cost',
        'disposition',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'estimated_repair_cost' => 'decimal:2',
        ];
    }

    public function damage(): BelongsTo
    {
        return $this->belongsTo(Damage::class);
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
