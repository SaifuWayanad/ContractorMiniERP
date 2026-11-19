<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductMovement extends Model
{
    protected $fillable = [
        'movement_number',
        'product_id',
        'movement_type',
        'source_type',
        'source_id',
        'source_detail_id',
        'from_location_type',
        'from_location_id',
        'to_location_type',
        'to_location_id',
        'quantity',
        'movement_date',
        'reference_dispatch_detail_id',
        'status',
        'remarks',
        'created_by',
        'posted_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'movement_date' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
