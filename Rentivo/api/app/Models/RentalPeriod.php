<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalPeriod extends Model
{
    protected $fillable = [
        'source_dispatch_detail_id',
        'customer_id',
        'project_id',
        'product_id',
        'quantity',
        'rental_start_date',
        'rental_end_date',
        'end_reason',
        'parent_rental_period_id',
        'minimum_rental_days_snapshot',
        'minimum_rental_amount_snapshot',
        'addon_amount_per_day_snapshot',
        'tax_rate_snapshot',
        'status',
        'closed_at',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'rental_start_date' => 'date',
            'rental_end_date' => 'date',
            'minimum_rental_days_snapshot' => 'decimal:2',
            'minimum_rental_amount_snapshot' => 'decimal:2',
            'addon_amount_per_day_snapshot' => 'decimal:2',
            'tax_rate_snapshot' => 'decimal:4',
            'closed_at' => 'datetime',
        ];
    }

    public function sourceDispatchDetail(): BelongsTo
    {
        return $this->belongsTo(DispatchDetail::class, 'source_dispatch_detail_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function parentRentalPeriod(): BelongsTo
    {
        return $this->belongsTo(RentalPeriod::class, 'parent_rental_period_id');
    }

    public function childRentalPeriods(): HasMany
    {
        return $this->hasMany(RentalPeriod::class, 'parent_rental_period_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
