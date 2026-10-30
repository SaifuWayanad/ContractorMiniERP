<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['product_name', 'unit_of_measure', 'default_unit_rate', 'description', 'is_active'])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'default_unit_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Purchase, $this> */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
}
