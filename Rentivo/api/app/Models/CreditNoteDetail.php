<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteDetail extends Model
{
    protected $fillable = [
        'credit_note_id',
        'invoice_detail_id',
        'product_id',
        'description',
        'quantity',
        'unit_amount',
        'line_amount',
        'tax_rate_snapshot',
        'tax_amount',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_amount' => 'decimal:2',
            'line_amount' => 'decimal:2',
            'tax_rate_snapshot' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function invoiceDetail(): BelongsTo
    {
        return $this->belongsTo(InvoiceDetail::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
