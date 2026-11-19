<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnDetail extends Model
{
    protected $fillable = [
        'return_id',
        'dispatch_detail_id',
        'product_id',
        'quantity_returned',
        'condition',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity_returned' => 'decimal:3',
        ];
    }

    public function equipmentReturn(): BelongsTo
    {
        return $this->belongsTo(EquipmentReturn::class, 'return_id');
    }

    public function dispatchDetail(): BelongsTo
    {
        return $this->belongsTo(DispatchDetail::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function invoiceAllocations(): HasMany
    {
        return $this->hasMany(InvoiceAllocation::class);
    }

    protected function uninvoicedQuantity(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                $invoiced = $this->invoiceAllocations()->sum('quantity_invoiced');
                return (float) $this->quantity_returned - (float) $invoiced;
            }
        );
    }
}
