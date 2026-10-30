<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'project_id', 'product_id', 'purchase_date', 'quantity', 'unit_amount', 'line_total',
    'supplier', 'invoice_reference', 'notes', 'is_archived',
])]
class Purchase extends Model
{
    protected function casts(): array
    {
        return [
            'purchase_date' => 'datetime',
            'quantity' => 'decimal:4',
            'unit_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'is_archived' => 'boolean',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
