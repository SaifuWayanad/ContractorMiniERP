<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceAllocation extends Model
{
    protected $fillable = [
        'return_detail_id',
        'invoice_detail_id',
        'quantity_invoiced',
        'amount_allocated',
    ];

    protected function casts(): array
    {
        return [
            'quantity_invoiced' => 'decimal:3',
            'amount_allocated' => 'decimal:2',
        ];
    }

    public function returnDetail(): BelongsTo
    {
        return $this->belongsTo(ReturnDetail::class);
    }

    public function invoiceDetail(): BelongsTo
    {
        return $this->belongsTo(InvoiceDetail::class);
    }
}
