<?php

namespace App\Models;

use App\Models\Concerns\BelongsToContractor;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['project_id', 'product_id', 'purchase_date', 'quantity', 'unit_amount', 'line_total', 'supplier', 'invoice_reference', 'notes'])]
class Purchase extends Model
{
    use BelongsToContractor, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'quantity'      => 'decimal:4',
            'unit_amount'   => 'decimal:2',
            'line_total'    => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Purchase $purchase): void {
            // If line_total explicitly provided (and qty/rate are null), keep it; otherwise auto-compute
            if ($purchase->quantity !== null && $purchase->unit_amount !== null) {
                $purchase->line_total = round((float) $purchase->quantity * (float) $purchase->unit_amount, 2);
            } elseif ($purchase->line_total === null) {
                $purchase->line_total = 0;
            }
        });
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function activityLabel(): string
    {
        return $this->product?->name ?? 'Purchase #'.$this->id;
    }
}
