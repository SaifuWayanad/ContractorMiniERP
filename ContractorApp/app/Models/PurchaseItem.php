<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = ['purchase_id', 'project_id', 'product_id', 'unit', 'supplier', 'quantity', 'unit_amount', 'line_total'];

    protected function casts(): array
    {
        return [
            'quantity'    => 'decimal:4',
            'unit_amount' => 'decimal:2',
            'line_total'  => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PurchaseItem $item): void {
            if ($item->quantity !== null && $item->unit_amount !== null) {
                $item->line_total = round((float) $item->quantity * (float) $item->unit_amount, 2);
            } elseif ($item->line_total === null) {
                $item->line_total = 0;
            }
        });
    }

    /** @return BelongsTo<Purchase, $this> */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }
}
