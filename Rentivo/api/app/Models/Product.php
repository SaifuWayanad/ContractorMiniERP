<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'code',
        'name',
        'description',
        'unit_of_measure',
        'tracking_method',
        'minimum_rental_days',
        'minimum_rental_amount',
        'addon_amount_per_day',
        'default_tax_rate_id',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'minimum_rental_days' => 'decimal:2',
            'minimum_rental_amount' => 'decimal:2',
            'addon_amount_per_day' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function defaultTaxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'default_tax_rate_id');
    }

    public function dispatchDetails(): HasMany
    {
        return $this->hasMany(DispatchDetail::class);
    }

    public function rentalPeriods(): HasMany
    {
        return $this->hasMany(RentalPeriod::class);
    }

    public function inventoryBalances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
