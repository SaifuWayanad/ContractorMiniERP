<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'project_id', 'payment_date', 'amount_received', 'payment_method', 'receipt_reference', 'notes', 'is_voided',
])]
class CustomerPayment extends Model
{
    protected $table = 'customer_payments';

    protected function casts(): array
    {
        return [
            'payment_date' => 'datetime',
            'amount_received' => 'decimal:2',
            'is_voided' => 'boolean',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
